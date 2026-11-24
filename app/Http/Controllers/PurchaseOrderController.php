<?php

namespace App\Http\Controllers;

use App\Actions\CreatePurchaseOrder;
use App\Enums\PoStatus;
use App\Http\Requests\ConvertPrToPoRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\Vendor;
use App\Services\ApprovalService;
use App\Services\AuditLogger;
use App\Services\GstCalculator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ApprovalService $approvalService,
        protected CreatePurchaseOrder $createPoAction,
        protected \App\Services\BudgetService $budgetService
    ) {
    }

    /**
     * List all purchase orders in user's company.
     */
    public function index(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $purchaseOrders = PurchaseOrder::with(['vendor', 'buyer', 'costCentre'])
            ->latest('id')
            ->paginate(15);

        return view('po.index', compact('purchaseOrders'));
    }

    /**
     * Screen 1: Select approved PR lines for conversion.
     */
    public function createFromPr(Request $request): View
    {
        $this->authorize('create', PurchaseOrder::class);

        $user = Auth::user();

        // Fetch all approved or partially converted PR lines in company with remaining qty
        $prLines = PurchaseRequisitionLine::whereHas('purchaseRequisition', function ($q) use ($user) {
            $q->where('company_id', $user->company_id)
                ->whereIn('status', ['APPROVED', 'PARTIALLY_CONVERTED']);
        })
            ->with(['purchaseRequisition.costCentre', 'item.baseUom', 'poPrLines'])
            ->get()
            ->filter(function ($line) {
                $ordered = (string) $line->poPrLines->sum('quantity');
                $remaining = bcsub((string) $line->quantity, $ordered, 3);
                return bccomp($remaining, '0.000', 3) > 0;
            });

        return view('po.create-from-pr', compact('prLines'));
    }

    /**
     * Screen 2: Configure vendor, unit prices, and order quantities for chosen PR lines.
     */
    public function configureConversion(Request $request): View|RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $selectedPrLineIds = $request->input('selected_lines', []);
        if (empty($selectedPrLineIds) || ! is_array($selectedPrLineIds)) {
            return redirect()->route('po.create-from-pr')
                ->withErrors(['error' => 'Please select at least one PR line to convert.']);
        }

        $user = Auth::user();

        $selectedLines = PurchaseRequisitionLine::whereIn('id', $selectedPrLineIds)
            ->whereHas('purchaseRequisition', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->with(['purchaseRequisition.costCentre', 'item.baseUom', 'poPrLines'])
            ->get();

        if ($selectedLines->isEmpty()) {
            return redirect()->route('po.create-from-pr')
                ->withErrors(['error' => 'No valid PR lines found for conversion.']);
        }

        // Active vendors in the user's company
        $vendors = Vendor::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('po.configure', compact('selectedLines', 'vendors'));
    }

    /**
     * Process PR to PO conversion.
     */
    public function store(ConvertPrToPoRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        try {
            $po = $this->createPoAction->execute(
                Auth::user(),
                (int) $request->validated('vendor_id'),
                $request->validated('lines'),
                $request->validated('notes')
            );

            return redirect()->route('po.show', $po)
                ->with('success', "Purchase Order {$po->po_number} generated successfully in DRAFT status.");
        } catch (\Throwable $e) {
            return redirect()->route('po.create-from-pr')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show PO detail with items, GST breakdown, frozen approval chain, and audit log.
     */
    public function show(PurchaseOrder $po): View
    {
        $this->authorize('view', $po);

        $po->load([
            'vendor',
            'buyer',
            'costCentre',
            'lines.item',
            'approvalRequests.steps.role',
            'approvalRequests.steps.actor',
        ]);

        $auditLogs = \App\Models\AuditLog::where('auditable_type', $po->getMorphClass())
            ->where('auditable_id', $po->id)
            ->with('user')
            ->latest('id')
            ->get();

        return view('po.show', compact('po', 'auditLogs'));
    }

    /**
     * Edit DRAFT PO.
     */
    public function edit(PurchaseOrder $po): View
    {
        $this->authorize('update', $po);

        $po->load(['lines.item', 'vendor']);

        return view('po.edit', compact('po'));
    }

    /**
     * Update DRAFT PO prices and notes.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $po): RedirectResponse
    {
        $this->authorize('update', $po);

        try {
            DB::transaction(function () use ($request, $po) {
                $company = Auth::user()->company;
                $vendor = $po->vendor;

                $poSubtotal = '0.00';
                $poTotalCgst = '0.00';
                $poTotalSgst = '0.00';
                $poTotalIgst = '0.00';
                $poGrandTotal = '0.00';

                $priceInputs = collect($request->validated('lines'))->keyBy('id');

                foreach ($po->lines as $line) {
                    $newPrice = isset($priceInputs[$line->id])
                        ? number_format((float) $priceInputs[$line->id]['unit_price'], 2, '.', '')
                        : $line->unit_price;

                    $taxable = bcmul((string) $line->quantity, $newPrice, 2);
                    $gstBreakdown = GstCalculator::calculate(
                        $taxable,
                        (string) $line->gst_rate,
                        $vendor->state,
                        $company->state
                    );

                    $line->unit_price = $newPrice;
                    $line->taxable_value = $taxable;
                    $line->cgst = $gstBreakdown['cgst'];
                    $line->sgst = $gstBreakdown['sgst'];
                    $line->igst = $gstBreakdown['igst'];
                    $line->line_total = $gstBreakdown['total'];
                    $line->save();

                    $poSubtotal = bcadd($poSubtotal, $taxable, 2);
                    $poTotalCgst = bcadd($poTotalCgst, $line->cgst, 2);
                    $poTotalSgst = bcadd($poTotalSgst, $line->sgst, 2);
                    $poTotalIgst = bcadd($poTotalIgst, $line->igst, 2);
                    $poGrandTotal = bcadd($poGrandTotal, $line->line_total, 2);
                }

                $taxTotal = bcadd(bcadd($poTotalCgst, $poTotalSgst, 2), $poTotalIgst, 2);

                $po->subtotal = $poSubtotal;
                $po->total_cgst = $poTotalCgst;
                $po->total_sgst = $poTotalSgst;
                $po->total_igst = $poTotalIgst;
                $po->tax_total = $taxTotal;
                $po->total_amount = $poGrandTotal;
                $po->grand_total = $poGrandTotal;
                $po->notes = $request->validated('notes');
                $po->save();

                AuditLogger::log($po, 'DRAFT', 'DRAFT', 'Purchase Order updated', Auth::id());
            });

            return redirect()->route('po.show', $po)->with('success', 'Purchase Order updated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Submit DRAFT PO for approval.
     */
    public function submit(PurchaseOrder $po): RedirectResponse
    {
        $this->authorize('submit', $po);

        try {
            $this->approvalService->submit($po, Auth::user());
            return redirect()->route('po.show', $po)
                ->with('success', 'Purchase Order submitted for approval.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a PO: allowed for DRAFT, PENDING_APPROVAL, APPROVED and PARTIALLY_RECEIVED.
     * For APPROVED and PARTIALLY_RECEIVED: comment is mandatory and releases remaining committed budget.
     */
    public function cancel(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $this->authorize('cancel', $po);

        $oldStatus = is_object($po->status) ? $po->status->value : (string) $po->status;
        $isApprovedOrReceived = in_array($oldStatus, ['APPROVED', 'PARTIALLY_RECEIVED'], true);

        if ($isApprovedOrReceived) {
            $request->validate([
                'comment' => ['required', 'string', 'min:3', 'max:1000'],
            ]);
        }

        $comment = $request->input('comment', 'Purchase Order cancelled by procurement officer');

        try {
            DB::transaction(function () use ($po, $oldStatus, $isApprovedOrReceived, $comment) {
                // If PO was APPROVED or PARTIALLY_RECEIVED, release the committed budget back
                if ($isApprovedOrReceived) {
                    $this->budgetService->release($po, Auth::user());
                }

                $po->status = PoStatus::CANCELLED;
                $po->save();

                // If it had an active approval request, mark request as CANCELLED
                if ($appReq = $po->approvalRequests()->first()) {
                    $appReq->status = 'CANCELLED';
                    $appReq->save();
                }

                AuditLogger::log($po, $oldStatus, 'CANCELLED', $comment, Auth::id());
            });

            return redirect()->route('po.show', $po)->with('success', 'Purchase Order has been cancelled successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
