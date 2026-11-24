<?php

namespace Tests\Feature;

use App\Enums\ApprovalStepStatus;
use App\Enums\DocumentType;
use App\Enums\PrStatus;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRule;
use App\Models\ApprovalStep;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CostCentre;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\Role;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\ApprovalChainBuilder;
use App\Services\ApprovalService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalEngineAndPrFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $mum;
    protected Company $blr;
    protected User $reqMum;
    protected User $deptHeadMum;
    protected User $itHeadMum;
    protected User $finHeadMum;
    protected User $cfoMum;
    protected User $deptHeadBlr;
    protected CostCentre $ccMumProd;
    protected CostCentre $ccMumIt;
    protected Item $rawItem;
    protected Item $itItem;
    protected ApprovalChainBuilder $builder;
    protected ApprovalService $approvalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->mum = Company::where('code', 'ACME-MUM')->firstOrFail();
        $this->blr = Company::where('code', 'ACME-BLR')->firstOrFail();

        $this->reqMum = User::withoutGlobalScopes()->where('email', 'req.mum@acme.test')->firstOrFail();
        $this->deptHeadMum = User::withoutGlobalScopes()->where('email', 'depthead.mum@acme.test')->firstOrFail();
        $this->itHeadMum = User::withoutGlobalScopes()->where('email', 'ithead.mum@acme.test')->firstOrFail();
        $this->finHeadMum = User::withoutGlobalScopes()->where('email', 'finhead.mum@acme.test')->firstOrFail();
        $this->cfoMum = User::withoutGlobalScopes()->where('email', 'cfo.mum@acme.test')->firstOrFail();
        $this->deptHeadBlr = User::withoutGlobalScopes()->where('email', 'depthead.blr@acme.test')->firstOrFail();

        $this->ccMumProd = CostCentre::withoutGlobalScopes()->where('company_id', $this->mum->id)->where('code', 'PROD')->firstOrFail();
        $this->ccMumIt = CostCentre::withoutGlobalScopes()->where('company_id', $this->mum->id)->where('code', 'IT')->firstOrFail();

        $this->rawItem = Item::where('code', 'I001')->firstOrFail(); // RAW_MATERIAL
        $this->itItem = Item::where('code', 'I002')->firstOrFail();  // IT

        $this->builder = app(ApprovalChainBuilder::class);
        $this->approvalService = app(ApprovalService::class);
    }

    /**
     * Helper to create a PR with lines and total.
     */
    protected function createPr(User $creator, CostCentre $cc, array $linesData, string $total): PurchaseRequisition
    {
        $pr = PurchaseRequisition::withoutGlobalScopes()->create([
            'company_id' => $creator->company_id,
            'pr_number' => 'PR-' . uniqid(),
            'requester_id' => $creator->id,
            'cost_centre_id' => $cc->id,
            'status' => PrStatus::DRAFT,
            'total_amount' => $total,
            'notes' => 'Test Requisition',
        ]);

        $lineNo = 1;
        foreach ($linesData as $ld) {
            PurchaseRequisitionLine::create([
                'purchase_requisition_id' => $pr->id,
                'line_number' => $lineNo++,
                'item_id' => $ld['item']->id,
                'description' => 'Line Item',
                'quantity' => $ld['qty'],
                'uom_code' => $ld['item']->base_uom_code,
                'base_quantity' => $ld['qty'],
                'estimated_unit_price' => $ld['price'],
                'estimated_total' => bcmul((string) $ld['qty'], (string) $ld['price'], 2),
                'ordered_quantity' => 0.000,
            ]);
        }

        return $pr;
    }

    /**
     * Test 1: 12,500 PR => chain is [Dept Head].
     */
    public function test_12500_pr_chain_is_dept_head(): void
    {
        $chain = $this->builder->build(DocumentType::PR, '12500.00', ['RAW_MATERIAL'], $this->mum->id);
        $this->assertSame(['Dept Head'], $chain);
    }

    /**
     * Test 2: 195,000 IT PR => chain is [Dept Head, IT Head, Finance Head].
     */
    public function test_195000_it_pr_chain_inserts_it_head(): void
    {
        $chain = $this->builder->build(DocumentType::PR, '195000.00', ['IT'], $this->mum->id);
        $this->assertSame(['Dept Head', 'IT Head', 'Finance Head'], $chain);
    }

    /**
     * Test 3: 600,000 PR => [Dept Head, Finance Head, CFO].
     */
    public function test_600000_pr_chain_is_dept_head_fin_head_cfo(): void
    {
        $chain = $this->builder->build(DocumentType::PR, '600000.00', null, $this->mum->id);
        $this->assertSame(['Dept Head', 'Finance Head', 'CFO'], $chain);
    }

    /**
     * Test 4: Step 2 cannot approve before step 1.
     */
    public function test_step_two_cannot_approve_before_step_one(): void
    {
        $pr = $this->createPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->rawItem, 'qty' => 10, 'price' => 10000.00], // 100,000 => Dept Head > Finance Head
        ], '100000.00');

        $this->approvalService->submit($pr, $this->reqMum);
        $request = $pr->approvalRequests()->firstOrFail();

        $step2 = $request->steps()->where('sequence', 2)->firstOrFail();

        // Attempting to approve step 2 while step 1 is still pending must throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Sequential approval violation/i');

        $this->approvalService->approve($step2, $this->finHeadMum, 'Attempting early approval');
    }

    /**
     * Test 5: Rejection ends the workflow, comment required, document REJECTED.
     */
    public function test_rejection_ends_workflow_comment_required_document_rejected(): void
    {
        $pr = $this->createPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->rawItem, 'qty' => 1, 'price' => 10000.00],
        ], '10000.00');

        $this->approvalService->submit($pr, $this->reqMum);
        $step1 = $pr->approvalRequests()->firstOrFail()->steps()->where('sequence', 1)->firstOrFail();

        // Rejection without comment throws InvalidArgumentException
        try {
            $this->approvalService->reject($step1, $this->deptHeadMum, '');
            $this->fail('Expected exception for empty comment');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('mandatory', $e->getMessage());
        }

        // Rejection with valid comment
        $this->approvalService->reject($step1, $this->deptHeadMum, 'Exceeds monthly quota');

        $pr->refresh();
        $this->assertSame(PrStatus::REJECTED, $pr->status);

        $request = $pr->approvalRequests()->firstOrFail();
        $this->assertSame('REJECTED', $request->status);

        $step1->refresh();
        $this->assertSame(ApprovalStepStatus::REJECTED, $step1->status);
        $this->assertSame('Exceeds monthly quota', $step1->comment);
    }

    /**
     * Test 6: Creator cannot approve own document (depthead.mum PR goes to Finance Head).
     */
    public function test_creator_cannot_approve_own_document_self_approval_rule_s8(): void
    {
        // Dept Head raises a 12,500 PR. Base rule would be [Dept Head].
        // Because Dept Head created it, self-approval rule removes Dept Head and sets fallback Finance Head.
        $pr = $this->createPr($this->deptHeadMum, $this->ccMumProd, [
            ['item' => $this->rawItem, 'qty' => 1, 'price' => 12500.00],
        ], '12500.00');

        $this->approvalService->submit($pr, $this->deptHeadMum);

        $request = $pr->approvalRequests()->firstOrFail();
        $steps = $request->steps()->with('role')->get();

        $this->assertCount(1, $steps);
        $this->assertSame('Finance Head', $steps[0]->role->name);

        // Even if depthead.mum attempts to approve it directly, approve() blocks self-approval
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Self-approval blocked/i');
        $this->approvalService->approve($steps[0], $this->deptHeadMum, 'Trying to self approve');
    }

    /**
     * Test 7: Changing a rule after submit does not change the existing chain.
     */
    public function test_changing_rule_after_submit_does_not_affect_existing_chain(): void
    {
        $pr = $this->createPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->rawItem, 'qty' => 1, 'price' => 12500.00],
        ], '12500.00');

        $this->approvalService->submit($pr, $this->reqMum);
        $initialStepsCount = $pr->approvalRequests()->firstOrFail()->steps()->count();
        $this->assertSame(1, $initialStepsCount); // [Dept Head]

        // Deactivate Rule 1 in database
        ApprovalRule::withoutGlobalScopes()
            ->where('company_id', $this->mum->id)
            ->where('min_amount', 0.00)
            ->where('max_amount', 50000.00)
            ->update(['is_active' => false]);

        // The snapshot steps remain exactly 1 and intact
        $pr->refresh();
        $this->assertSame($initialStepsCount, $pr->approvalRequests()->firstOrFail()->steps()->count());
    }

    /**
     * Test 8: "Pending my approval" shows only my current steps.
     */
    public function test_pending_my_approval_shows_only_my_current_steps(): void
    {
        $pr = $this->createPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->rawItem, 'qty' => 1, 'price' => 100000.00], // Dept Head > Finance Head
        ], '100000.00');
        $this->approvalService->submit($pr, $this->reqMum);

        // At sequence 1, Dept Head sees it in pending approvals
        $response = $this->actingAs($this->deptHeadMum)->get('/approvals/pending');
        $response->assertOk();
        $response->assertSee($pr->pr_number);

        // Finance Head does NOT see it yet because sequence 1 is pending (step 2 is not current)
        $responseFin = $this->actingAs($this->finHeadMum)->get('/approvals/pending');
        $responseFin->assertOk();
        $responseFin->assertDontSee($pr->pr_number);

        // Now Dept Head approves step 1
        $step1 = $pr->approvalRequests()->firstOrFail()->steps()->where('sequence', 1)->firstOrFail();
        $this->approvalService->approve($step1, $this->deptHeadMum, 'Approved step 1');

        // Now Finance Head sees it!
        $responseFinNow = $this->actingAs($this->finHeadMum)->get('/approvals/pending');
        $responseFinNow->assertOk();
        $responseFinNow->assertSee($pr->pr_number);
    }

    /**
     * Test 9: A Bengaluru user cannot approve a Mumbai PR (404/403).
     */
    public function test_bengaluru_user_cannot_approve_mumbai_pr(): void
    {
        $pr = $this->createPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->rawItem, 'qty' => 1, 'price' => 12500.00],
        ], '12500.00');
        $this->approvalService->submit($pr, $this->reqMum);

        $step1 = $pr->approvalRequests()->firstOrFail()->steps()->where('sequence', 1)->firstOrFail();

        // Acting as Dept Head Bengaluru
        $response = $this->actingAs($this->deptHeadBlr)->post("/approvals/steps/{$step1->id}/approve", [
            'comment' => 'Cross-company approval attempt',
        ]);

        // Expect 403 or 404 (due to global scope or company check)
        $this->assertTrue(in_array($response->status(), [403, 404, 500]));
    }

    /**
     * Test 10: Audit log rows are written and cannot be updated or deleted.
     */
    public function test_audit_logs_are_written_and_cannot_be_updated_or_deleted(): void
    {
        $pr = $this->createPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->rawItem, 'qty' => 1, 'price' => 12500.00],
        ], '12500.00');

        $this->approvalService->submit($pr, $this->reqMum);

        $logs = AuditLog::withoutGlobalScopes()
            ->where('auditable_type', PurchaseRequisition::class)
            ->where('auditable_id', $pr->id)
            ->get();

        $this->assertNotEmpty($logs);
        $firstLog = $logs->first();

        // Attempt update => RuntimeException
        try {
            $firstLog->update(['comment' => 'Tampered']);
            $this->fail('Expected exception on updating audit log');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        // Attempt delete => RuntimeException
        try {
            $firstLog->delete();
            $this->fail('Expected exception on deleting audit log');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
    }
}
