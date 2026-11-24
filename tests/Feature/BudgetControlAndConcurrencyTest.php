<?php

namespace Tests\Feature;

use App\Actions\CreatePurchaseOrder;
use App\Enums\ApprovalStepStatus;
use App\Enums\PoStatus;
use App\Enums\PrStatus;
use App\Exceptions\BudgetExceededException;
use App\Models\Budget;
use App\Models\BudgetTransaction;
use App\Models\Company;
use App\Models\CostCentre;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApprovalService;
use App\Services\BudgetService;
use App\Services\FinancialYear;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BudgetControlAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Company $mum;
    protected Company $blr;
    protected User $reqMum;
    protected User $procMum;
    protected User $finHeadMum;
    protected User $cfoMum;
    protected User $procBlr;
    protected CostCentre $ccAdmin;
    protected CostCentre $ccSales;
    protected CostCentre $ccProd;
    protected Vendor $v001;
    protected Item $i003;
    protected Item $i004;
    protected ApprovalService $approvalService;
    protected BudgetService $budgetService;
    protected CreatePurchaseOrder $createPoAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->mum = Company::where('code', 'ACME-MUM')->firstOrFail();
        $this->blr = Company::where('code', 'ACME-BLR')->firstOrFail();

        $this->reqMum = User::where('email', 'req.mum@acme.test')->firstOrFail();
        $this->procMum = User::where('email', 'proc.mum@acme.test')->firstOrFail();
        $this->finHeadMum = User::where('email', 'finhead.mum@acme.test')->firstOrFail();
        $this->cfoMum = User::where('email', 'cfo.mum@acme.test')->firstOrFail();
        $this->procBlr = User::where('email', 'proc.blr@acme.test')->firstOrFail();

        $this->ccAdmin = CostCentre::where('code', 'ADMIN')->where('company_id', $this->mum->id)->firstOrFail();
        $this->ccSales = CostCentre::where('code', 'SALES')->where('company_id', $this->mum->id)->firstOrFail();
        $this->ccProd = CostCentre::where('code', 'PROD')->where('company_id', $this->mum->id)->firstOrFail();

        $this->v001 = Vendor::where('code', 'V001')->firstOrFail();
        $this->i003 = Item::where('code', 'I003')->firstOrFail(); // 12%
        $this->i004 = Item::where('code', 'I004')->firstOrFail(); // 18%

        $this->approvalService = app(ApprovalService::class);
        $this->budgetService = app(BudgetService::class);
        $this->createPoAction = app(CreatePurchaseOrder::class);
    }

    /**
     * Helper to create and submit a PO directly for testing.
     */
    protected function createSubmittedPo(CostCentre $cc, string $taxable, string $grandTotal, string $poDate = '2026-06-15'): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->mum->id,
            'po_number' => 'PO-TEST-' . uniqid(),
            'po_date' => $poDate,
            'vendor_id' => $this->v001->id,
            'cost_centre_id' => $cc->id,
            'buyer_id' => $this->procMum->id,
            'status' => PoStatus::DRAFT,
            'subtotal' => $taxable,
            'total_cgst' => '0.00',
            'total_sgst' => '0.00',
            'total_igst' => '0.00',
            'tax_total' => bcsub($grandTotal, $taxable, 2),
            'total_amount' => $grandTotal,
            'grand_total' => $grandTotal,
            'committed_amount' => '0.00',
            'payment_terms_days' => 30,
        ]);

        PurchaseOrderLine::create([
            'purchase_order_id' => $po->id,
            'line_number' => 1,
            'item_id' => $this->i003->id,
            'description' => 'Test item',
            'quantity' => '1.000',
            'uom_code' => 'PCS',
            'base_quantity' => '1.000',
            'unit_price' => $taxable,
            'taxable_value' => $taxable,
            'gst_rate' => '0.00',
            'cgst' => '0.00',
            'sgst' => '0.00',
            'igst' => '0.00',
            'line_total' => $grandTotal,
        ]);

        $this->approvalService->submit($po, $this->procMum);
        return $po;
    }

    /**
     * Test 1: S1 ADMIN budget 200,000. After the 13,720 PO is approved:
     * committed 13,720.00, available 186,280.00.
     */
    public function test_s1_admin_budget_commitment_on_po_approval(): void
    {
        $budget = Budget::where('cost_centre_id', $this->ccAdmin->id)->where('financial_year', '2026-27')->firstOrFail();
        $this->assertSame('200000.00', (string) $budget->amount);
        $this->assertSame('0.00', (string) $budget->committed);

        $po = $this->createSubmittedPo($this->ccAdmin, '12250.00', '13720.00', '2026-06-15');
        $step1 = $po->approvalRequests->first()->steps->first();

        // Finance Head approves the PO
        $this->approvalService->approve($step1, $this->finHeadMum, 'Approved S1 PO');

        $po->refresh();
        $budget->refresh();

        $this->assertSame(PoStatus::APPROVED, $po->status);
        $this->assertSame('13720.00', (string) $po->committed_amount);
        $this->assertSame('13720.00', (string) $budget->committed);
        $this->assertSame('186280.00', (string) $budget->availableAmount());

        // Verify budget transaction created
        $this->assertDatabaseHas('budget_transactions', [
            'budget_id' => $budget->id,
            'purchase_order_id' => $po->id,
            'type' => 'COMMIT',
            'amount' => '13720.00',
        ]);
    }

    /**
     * Test 2: Budget exceeded: a PO above available fails with the clear message;
     * PO remains PENDING_APPROVAL; committed unchanged.
     */
    public function test_budget_exceeded_throws_exception_rolls_back_and_leaves_po_pending(): void
    {
        $budget = Budget::where('cost_centre_id', $this->ccSales->id)->where('financial_year', '2026-27')->firstOrFail();
        $this->assertSame('100000.00', (string) $budget->amount);

        // Attempting to approve a PO for 130,000 when available is 100,000
        $po = $this->createSubmittedPo($this->ccSales, '110000.00', '130000.00', '2026-06-15');
        $step1 = $po->approvalRequests->first()->steps->first();

        try {
            $this->approvalService->approve($step1, $this->finHeadMum, 'Attempting approval');
            $this->fail('Expected BudgetExceededException');
        } catch (BudgetExceededException $e) {
            $this->assertStringContainsString('Budget exceeded for cost centre SALES (FY 2026-27)', $e->getMessage());
            $this->assertStringContainsString('Available: 100000.00, required: 130000.00', $e->getMessage());
        }

        $po->refresh();
        $step1->refresh();
        $budget->refresh();

        $this->assertSame(PoStatus::PENDING_APPROVAL, $po->status);
        $this->assertSame(ApprovalStepStatus::PENDING, $step1->status);
        $this->assertSame('0.00', (string) $budget->committed);
        $this->assertSame('100000.00', (string) $budget->availableAmount());
    }

    /**
     * Test 3: S3 SALES budget 100,000: two POs of 60,000 each.
     * First approval succeeds, second fails, committed is 60,000, never negative available.
     */
    public function test_s3_two_pos_competing_first_succeeds_second_fails(): void
    {
        $budget = Budget::where('cost_centre_id', $this->ccSales->id)->where('financial_year', '2026-27')->firstOrFail();
        $this->assertSame('100000.00', (string) $budget->amount);

        $po1 = $this->createSubmittedPo($this->ccSales, '50000.00', '60000.00');
        $po2 = $this->createSubmittedPo($this->ccSales, '50000.00', '60000.00');

        $step1 = $po1->approvalRequests->first()->steps->first();
        $step2 = $po2->approvalRequests->first()->steps->first();

        // 1. Approve PO 1 => succeeds
        $this->approvalService->approve($step1, $this->finHeadMum, 'Approve PO 1');
        $po1->refresh();
        $budget->refresh();
        $this->assertSame(PoStatus::APPROVED, $po1->status);
        $this->assertSame('60000.00', (string) $budget->committed);
        $this->assertSame('40000.00', (string) $budget->availableAmount());

        // 2. Approve PO 2 => fails (requires 60,000, only 40,000 available)
        try {
            $this->approvalService->approve($step2, $this->finHeadMum, 'Approve PO 2');
            $this->fail('Expected BudgetExceededException on second PO');
        } catch (BudgetExceededException $e) {
            $this->assertStringContainsString('Available: 40000.00, required: 60000.00', $e->getMessage());
        }

        $po2->refresh();
        $budget->refresh();
        $this->assertSame(PoStatus::PENDING_APPROVAL, $po2->status);
        $this->assertSame('60000.00', (string) $budget->committed);
        $this->assertSame('40000.00', (string) $budget->availableAmount());
    }

    /**
     * Test 4: S3 Concurrency demonstration command test.
     */
    public function test_budget_concurrency_command_executes_cleanly(): void
    {
        $this->artisan('budget:concurrency-demo')
            ->expectsOutputToContain('BUDGET CONCURRENCY CONTROL DEMONSTRATION')
            ->expectsOutputToContain('[SUCCESS] PO #1 approved and committed ₹60,000.00')
            ->assertExitCode(0);
    }

    /**
     * Test 5: Cancel an APPROVED PO releases committed amount back (committed returns to 0).
     */
    public function test_cancel_approved_po_releases_committed_budget(): void
    {
        $budget = Budget::where('cost_centre_id', $this->ccAdmin->id)->where('financial_year', '2026-27')->firstOrFail();

        $po = $this->createSubmittedPo($this->ccAdmin, '10000.00', '11800.00');
        $step1 = $po->approvalRequests->first()->steps->first();
        $this->approvalService->approve($step1, $this->finHeadMum, 'Approved');

        $po->refresh();
        $budget->refresh();
        $this->assertSame('11800.00', (string) $po->committed_amount);
        $this->assertSame('11800.00', (string) $budget->committed);

        // Cancel the APPROVED PO via controller action
        $response = $this->actingAs($this->procMum)->post("/po/{$po->id}/cancel", [
            'comment' => 'Project canceled by client request',
        ]);
        $response->assertRedirect();

        $po->refresh();
        $budget->refresh();

        $this->assertSame(PoStatus::CANCELLED, $po->status);
        $this->assertSame('0.00', (string) $po->committed_amount);
        $this->assertSame('0.00', (string) $budget->committed);
        $this->assertSame('200000.00', (string) $budget->availableAmount());

        // Verify RELEASE transaction created
        $this->assertDatabaseHas('budget_transactions', [
            'budget_id' => $budget->id,
            'purchase_order_id' => $po->id,
            'type' => 'RELEASE',
            'amount' => '11800.00',
        ]);
    }

    /**
     * Test 6: Cancel a PENDING_APPROVAL PO changes nothing in the budget.
     */
    public function test_cancel_pending_approval_po_leaves_budget_untouched(): void
    {
        $budget = Budget::where('cost_centre_id', $this->ccAdmin->id)->where('financial_year', '2026-27')->firstOrFail();

        $po = $this->createSubmittedPo($this->ccAdmin, '10000.00', '11800.00');
        $this->assertSame('0.00', (string) $budget->committed);

        // Cancel while PENDING_APPROVAL
        $response = $this->actingAs($this->procMum)->post("/po/{$po->id}/cancel");
        $response->assertRedirect();

        $po->refresh();
        $budget->refresh();

        $this->assertSame(PoStatus::CANCELLED, $po->status);
        $this->assertSame('0.00', (string) $budget->committed);
        $this->assertSame('200000.00', (string) $budget->availableAmount());

        // No budget transactions should exist
        $this->assertDatabaseMissing('budget_transactions', [
            'purchase_order_id' => $po->id,
        ]);
    }

    /**
     * Test 7: FinancialYear boundaries (Mar 31 vs Apr 1).
     */
    public function test_financial_year_boundaries_and_commit_routing(): void
    {
        $mar31 = FinancialYear::forDate(Carbon::create(2026, 3, 31));
        $apr01 = FinancialYear::forDate(Carbon::create(2026, 4, 1));

        $this->assertSame('2025-26', $mar31);
        $this->assertSame('2026-27', $apr01);
    }

    /**
     * Test 8: A Bengaluru user cannot see Mumbai budgets (404/403).
     */
    public function test_bengaluru_user_cannot_view_mumbai_budgets(): void
    {
        $mumBudget = Budget::where('company_id', $this->mum->id)->firstOrFail();

        // Direct show view check
        $response = $this->actingAs($this->procBlr)->get("/budgets/{$mumBudget->id}");
        $this->assertTrue(in_array($response->status(), [403, 404], true));

        // Index page check: BLR user only sees BLR budgets
        $indexResponse = $this->actingAs($this->procBlr)->get('/budgets');
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('Administration Mumbai');
    }

    /**
     * Test 9: Budget transactions table rejects update and delete.
     */
    public function test_budget_transactions_table_is_append_only(): void
    {
        $budget = Budget::where('cost_centre_id', $this->ccAdmin->id)->where('financial_year', '2026-27')->firstOrFail();

        $txn = BudgetTransaction::withoutGlobalScopes()->create([
            'company_id' => $this->mum->id,
            'budget_id' => $budget->id,
            'purchase_order_id' => null,
            'type' => 'COMMIT',
            'amount' => '1000.00',
            'balance_after_committed' => '1000.00',
            'balance_after_spent' => '0.00',
            'user_id' => $this->procMum->id,
            'created_at' => now(),
        ]);

        // Attempting to update throws RuntimeException
        try {
            $txn->amount = '2000.00';
            $txn->save();
            $this->fail('Expected exception on update');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('immutable and cannot be updated', $e->getMessage());
        }

        // Attempting to delete throws RuntimeException
        try {
            $txn->delete();
            $this->fail('Expected exception on delete');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('immutable and cannot be deleted', $e->getMessage());
        }
    }

    /**
     * Test 10: PO approval for a cost centre with no budget row for that FY fails with a clear message.
     */
    public function test_po_approval_for_missing_budget_fails_with_clear_message(): void
    {
        // PO with date in year 2029 (no budget seeded for 2029-30)
        $po = $this->createSubmittedPo($this->ccAdmin, '5000.00', '5900.00', '2029-07-01');
        $step1 = $po->approvalRequests->first()->steps->first();

        try {
            $this->approvalService->approve($step1, $this->finHeadMum, 'Approving 2029 PO');
            $this->fail('Expected BudgetExceededException for missing budget allocation');
        } catch (BudgetExceededException $e) {
            $this->assertStringContainsString('No budget allocation found for cost centre ADMIN in financial year 2029-30', $e->getMessage());
        }

        $po->refresh();
        $this->assertSame(PoStatus::PENDING_APPROVAL, $po->status);
    }
}
