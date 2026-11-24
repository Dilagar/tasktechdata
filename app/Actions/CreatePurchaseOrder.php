<?php

namespace App\Actions;

use App\Enums\PoStatus;
use App\Enums\PrStatus;
use App\Models\CostCentre;
use App\Models\PoPrLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Services\GstCalculator;
use App\Services\UomConverter;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CreatePurchaseOrder
{
    /**
     * Execute PR to PO conversion inside a database transaction.
     *
     * @param User $buyer Authenticated Procurement Officer
     * @param int $vendorId Target vendor ID (must be active and same company)
     * @param array<int, array{pr_line_id: int, order_qty: string|float|int, unit_price: string|float|int, uom_code?: string|null}> $lines
     * @param string|null $notes Optional PO notes
     * @return PurchaseOrder
     */
    public function execute(User $buyer, int $vendorId, array $lines, ?string $notes = null): PurchaseOrder
    {
        if (empty($lines)) {
            throw new InvalidArgumentException('At least one PR line must be selected for conversion.');
        }

        return DB::transaction(function () use ($buyer, $vendorId, $lines, $notes) {
            // 1. Validate Vendor
            $vendor = Vendor::where('id', $vendorId)
                ->where('company_id', $buyer->company_id)
                ->first();

            if (! $vendor) {
                throw new InvalidArgumentException('Selected vendor does not exist or does not belong to your company.');
            }

            if (! $vendor->is_active) {
                throw new InvalidArgumentException("Vendor '{$vendor->name}' is inactive and cannot be used for new Purchase Orders.");
            }

            $company = $buyer->company;

            // 2. Lock and fetch PR lines with pessimistic lock (lockForUpdate)
            $prLineIds = array_column($lines, 'pr_line_id');
            $prLines = PurchaseRequisitionLine::whereIn('id', $prLineIds)
                ->with(['purchaseRequisition', 'item.baseUom', 'poPrLines'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($prLines->count() !== count($prLineIds)) {
                throw new InvalidArgumentException('One or more selected PR lines could not be found.');
            }

            // Verify company and approved / partially converted status
            $costCentreId = null;
            $affectedPrs = [];

            foreach ($lines as $lineInput) {
                $prLineId = $lineInput['pr_line_id'];
                $prLine = $prLines->get($prLineId);
                $pr = $prLine->purchaseRequisition;

                if ($pr->company_id !== $buyer->company_id) {
                    throw new RuntimeException('Forbidden. Cannot convert PR lines from another company.');
                }

                $prStatusStr = is_object($pr->status) ? $pr->status->value : (string) $pr->status;
                if (! in_array($prStatusStr, ['APPROVED', 'PARTIALLY_CONVERTED'], true)) {
                    throw new RuntimeException("PR #{$pr->pr_number} is in '{$prStatusStr}' status. Only APPROVED or PARTIALLY_CONVERTED PRs can be converted to PO.");
                }

                // Verify same cost centre assumption across merged PR lines
                if ($costCentreId === null) {
                    $costCentreId = $pr->cost_centre_id;
                } elseif ($costCentreId !== $pr->cost_centre_id) {
                    throw new InvalidArgumentException('All merged PR lines in a single Purchase Order must belong to the same Cost Centre.');
                }

                // Calculate remaining qty: approved quantity - sum(po_pr_line linked quantities)
                $alreadyOrdered = (string) $prLine->poPrLines()->sum('quantity');
                $approvedQty = (string) $prLine->quantity;
                $remainingQty = bcsub($approvedQty, $alreadyOrdered, 3);

                $orderQtyStr = number_format((float) $lineInput['order_qty'], 3, '.', '');

                if (bccomp($orderQtyStr, '0.000', 3) <= 0) {
                    throw new InvalidArgumentException("Order quantity for item '{$prLine->item->name}' must be greater than zero.");
                }

                if (bccomp($orderQtyStr, $remainingQty, 3) > 0) {
                    throw new InvalidArgumentException(
                        "Order quantity ({$orderQtyStr}) exceeds remaining approved quantity ({$remainingQty}) for line #{$prLine->line_number} of PR #{$pr->pr_number}."
                    );
                }

                $affectedPrs[$pr->id] = $pr;
            }

            // 3. Generate PO Number inside the transaction: PO-YYYYMMDD-0001 (company-scoped)
            $datePrefix = date('Ymd');
            $likePattern = "PO-{$datePrefix}-%";
            $todayCount = PurchaseOrder::withoutGlobalScopes()
                ->where('company_id', $buyer->company_id)
                ->where('po_number', 'LIKE', $likePattern)
                ->lockForUpdate()
                ->count();

            $sequenceNo = str_pad((string) ($todayCount + 1), 4, '0', STR_PAD_LEFT);
            $poNumber = "PO-{$datePrefix}-{$sequenceNo}";

            // 4. Create Purchase Order header
            $po = PurchaseOrder::create([
                'company_id' => $buyer->company_id,
                'po_number' => $poNumber,
                'vendor_id' => $vendor->id,
                'cost_centre_id' => $costCentreId,
                'buyer_id' => $buyer->id,
                'status' => PoStatus::DRAFT,
                'subtotal' => '0.00',
                'total_cgst' => '0.00',
                'total_sgst' => '0.00',
                'total_igst' => '0.00',
                'tax_total' => '0.00',
                'total_amount' => '0.00',
                'grand_total' => '0.00',
                'payment_terms_days' => $vendor->payment_terms_days ?? 30,
                'notes' => $notes,
            ]);

            // 5. Create PO Lines and po_pr_line links
            $poSubtotal = '0.00';
            $poTotalCgst = '0.00';
            $poTotalSgst = '0.00';
            $poTotalIgst = '0.00';
            $poGrandTotal = '0.00';
            $lineIndex = 1;

            foreach ($lines as $lineInput) {
                $prLine = $prLines->get($lineInput['pr_line_id']);
                $item = $prLine->item;

                $orderQty = number_format((float) $lineInput['order_qty'], 3, '.', '');
                $unitPrice = number_format((float) $lineInput['unit_price'], 2, '.', '');
                $uomCode = $lineInput['uom_code'] ?? $prLine->uom_code;

                // Base quantity conversion via UomConverter
                $baseQty = UomConverter::toBase($orderQty, $uomCode, $item);

                // Taxable value = orderQty * unitPrice
                $taxableValue = bcmul($orderQty, $unitPrice, 2);

                // GST breakdown via GstCalculator
                $gstRate = (string) $item->gst_rate;
                $gstBreakdown = GstCalculator::calculate(
                    $taxableValue,
                    $gstRate,
                    $vendor->state,
                    $company->state
                );

                $cgst = $gstBreakdown['cgst'];
                $sgst = $gstBreakdown['sgst'];
                $igst = $gstBreakdown['igst'];
                $lineTotal = $gstBreakdown['total'];

                // Create PO line
                $poLine = PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'line_number' => $lineIndex++,
                    'item_id' => $item->id,
                    'description' => $prLine->description,
                    'quantity' => $orderQty,
                    'uom_code' => $uomCode,
                    'base_quantity' => $baseQty,
                    'unit_price' => $unitPrice,
                    'taxable_value' => $taxableValue,
                    'gst_rate' => $gstRate,
                    'cgst' => $cgst,
                    'sgst' => $sgst,
                    'igst' => $igst,
                    'line_total' => $lineTotal,
                    'received_quantity' => 0.000,
                    'billed_quantity' => 0.000,
                ]);

                // Create po_pr_line row
                PoPrLine::create([
                    'purchase_order_line_id' => $poLine->id,
                    'purchase_requisition_line_id' => $prLine->id,
                    'quantity' => $orderQty,
                ]);

                // Update ordered_quantity on the PR line
                $prLine->ordered_quantity = bcadd((string) $prLine->ordered_quantity, $orderQty, 3);
                $prLine->save();

                // Accumulate totals
                $poSubtotal = bcadd($poSubtotal, $taxableValue, 2);
                $poTotalCgst = bcadd($poTotalCgst, $cgst, 2);
                $poTotalSgst = bcadd($poTotalSgst, $sgst, 2);
                $poTotalIgst = bcadd($poTotalIgst, $igst, 2);
                $poGrandTotal = bcadd($poGrandTotal, $lineTotal, 2);
            }

            $taxTotal = bcadd(bcadd($poTotalCgst, $poTotalSgst, 2), $poTotalIgst, 2);

            // Update PO header with final calculated totals
            $po->subtotal = $poSubtotal;
            $po->total_cgst = $poTotalCgst;
            $po->total_sgst = $poTotalSgst;
            $po->total_igst = $poTotalIgst;
            $po->tax_total = $taxTotal;
            $po->total_amount = $poGrandTotal;
            $po->grand_total = $poGrandTotal;
            $po->save();

            // 6. Update PR status (PARTIALLY_CONVERTED vs CONVERTED)
            foreach ($affectedPrs as $pr) {
                // Refresh all lines for this PR
                $allLines = $pr->lines()->get();
                $isFullyConverted = true;
                $hasAnyOrdered = false;

                foreach ($allLines as $line) {
                    $ordered = (string) $line->poPrLines()->sum('quantity');
                    $approved = (string) $line->quantity;
                    $rem = bcsub($approved, $ordered, 3);

                    if (bccomp($rem, '0.000', 3) > 0) {
                        $isFullyConverted = false;
                    }
                    if (bccomp($ordered, '0.000', 3) > 0) {
                        $hasAnyOrdered = true;
                    }
                }

                $newPrStatus = $isFullyConverted
                    ? PrStatus::CONVERTED
                    : ($hasAnyOrdered ? PrStatus::PARTIALLY_CONVERTED : $pr->status);

                if ($pr->status !== $newPrStatus) {
                    $oldStatus = is_object($pr->status) ? $pr->status->value : (string) $pr->status;
                    $pr->status = $newPrStatus;
                    $pr->save();

                    AuditLogger::log(
                        $pr,
                        $oldStatus,
                        $newPrStatus->value,
                        "Status updated via PO #{$po->po_number} creation",
                        $buyer->id
                    );
                }
            }

            // Write initial PO audit log
            AuditLogger::log(
                $po,
                'START',
                'DRAFT',
                "Purchase Order created from PR conversion",
                $buyer->id
            );

            return $po;
        });
    }
}
