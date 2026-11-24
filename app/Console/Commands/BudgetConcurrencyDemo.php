<?php

namespace App\Console\Commands;

use App\Actions\CreatePurchaseOrder;
use App\Enums\PoStatus;
use App\Enums\PrStatus;
use App\Exceptions\BudgetExceededException;
use App\Models\Company;
use App\Models\CostCentre;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApprovalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BudgetConcurrencyDemo extends Command
{
    protected $signature = 'budget:concurrency-demo';
    protected $description = 'Demonstrate budget concurrency control with two competing PO approvals on SALES budget (₹100,000)';

    public function handle(ApprovalService $approvalService, CreatePurchaseOrder $createPoAction): int
    {
        $this->info('=== BUDGET CONCURRENCY CONTROL DEMONSTRATION ===');
        $this->line('Scenario: SALES cost centre has an allocated budget of ₹100,000.');
        $this->line('Two Purchase Orders of ₹60,000 each compete for approval.');
        $this->line('Due to DB transaction and lockForUpdate on the budget row,');
        $this->line('exactly ONE will succeed and the other will fail with BudgetExceededException.');
        $this->newLine();

        $mum = Company::where('code', 'ACME-MUM')->firstOrFail();
        $ccSales = CostCentre::where('code', 'SALES')->where('company_id', $mum->id)->firstOrFail();
        $budget = \App\Models\Budget::where('cost_centre_id', $ccSales->id)->where('financial_year', '2026-27')->firstOrFail();

        // Reset budget for clean demo run
        $budget->committed = '0.00';
        $budget->spent = '0.00';
        $budget->save();

        $reqMum = User::where('email', 'req.mum@acme.test')->firstOrFail();
        $procMum = User::where('email', 'proc.mum@acme.test')->firstOrFail();
        $finHeadMum = User::where('email', 'finhead.mum@acme.test')->firstOrFail();
        $v001 = Vendor::where('code', 'V001')->firstOrFail();
        $item = Item::where('code', 'I004')->firstOrFail(); // 18% service

        // Helper to create submitted PO of 60,000 grand total (taxable ~50,847.46)
        $createSubmittedPo = function ($idx) use ($mum, $ccSales, $reqMum, $procMum, $v001, $item, $approvalService) {
            $po = PurchaseOrder::create([
                'company_id' => $mum->id,
                'po_number' => 'PO-DEMO-' . $idx . '-' . uniqid(),
                'po_date' => '2026-06-15',
                'vendor_id' => $v001->id,
                'cost_centre_id' => $ccSales->id,
                'buyer_id' => $procMum->id,
                'status' => PoStatus::DRAFT,
                'subtotal' => '50847.46',
                'total_cgst' => '4576.27',
                'total_sgst' => '4576.27',
                'total_igst' => '0.00',
                'tax_total' => '9152.54',
                'total_amount' => '60000.00',
                'grand_total' => '60000.00',
                'payment_terms_days' => 30,
            ]);

            \App\Models\PurchaseOrderLine::create([
                'purchase_order_id' => $po->id,
                'line_number' => 1,
                'item_id' => $item->id,
                'quantity' => '1.000',
                'uom_code' => 'HRS',
                'base_quantity' => '1.000',
                'unit_price' => '50847.46',
                'taxable_value' => '50847.46',
                'gst_rate' => '18.00',
                'cgst' => '4576.27',
                'sgst' => '4576.27',
                'igst' => '0.00',
                'line_total' => '60000.00',
            ]);

            $approvalService->submit($po, $procMum);
            return $po;
        };

        $po1 = $createSubmittedPo(1);
        $po2 = $createSubmittedPo(2);

        $this->info("Generated PO #1: {$po1->po_number} (₹ 60,000.00)");
        $this->info("Generated PO #2: {$po2->po_number} (₹ 60,000.00)");
        $this->newLine();

        $step1 = $po1->approvalRequests->first()->steps->first();
        $step2 = $po2->approvalRequests->first()->steps->first();

        $results = [];

        // Attempt approving PO 1
        $this->line("Executing Approval 1 for PO #1...");
        try {
            $approvalService->approve($step1, $finHeadMum, 'Approved PO 1');
            $results[] = "[SUCCESS] PO #1 approved and committed ₹60,000.00";
            $this->info("-> PO #1 APPROVED successfully.");
        } catch (Throwable $e) {
            $results[] = "[FAILED] PO #1: " . $e->getMessage();
            $this->error("-> PO #1 FAILED: " . $e->getMessage());
        }

        // Attempt approving PO 2
        $this->line("Executing Approval 2 for PO #2...");
        try {
            $approvalService->approve($step2, $finHeadMum, 'Approved PO 2');
            $results[] = "[SUCCESS] PO #2 approved and committed ₹60,000.00";
            $this->info("-> PO #2 APPROVED successfully.");
        } catch (Throwable $e) {
            $results[] = "[FAILED] PO #2: " . $e->getMessage();
            $this->warn("-> PO #2 BLOCKED: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('=== DEMONSTRATION SUMMARY ===');
        foreach ($results as $res) {
            $this->line($res);
        }

        $budget->refresh();
        $this->newLine();
        $this->line("Final Budget State for Cost Centre SALES:");
        $this->line("  Allocated: ₹ " . number_format((float) $budget->amount, 2));
        $this->line("  Committed: ₹ " . number_format((float) $budget->committed, 2));
        $this->line("  Available: ₹ " . number_format((float) $budget->availableAmount(), 2));

        return Command::SUCCESS;
    }
}
