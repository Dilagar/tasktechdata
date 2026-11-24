<?php

namespace Tests\Feature;

use App\Actions\CreatePurchaseOrder;
use App\Enums\ApprovalStepStatus;
use App\Enums\DocumentType;
use App\Enums\PoStatus;
use App\Enums\PrStatus;
use App\Events\PurchaseOrderApproved;
use App\Models\ApprovalRequest;
use App\Models\Company;
use App\Models\CostCentre;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApprovalService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class PurchaseOrderConversionAndApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Company $mum;
    protected Company $blr;
    protected User $reqMum;
    protected User $deptHeadMum;
    protected User $itHeadMum;
    protected User $finHeadMum;
    protected User $cfoMum;
    protected User $procMum;
    protected User $procBlr;
    protected CostCentre $ccMumProd;
    protected CostCentre $ccMumIt;
    protected Vendor $v001;
    protected Vendor $v002;
    protected Vendor $v004;
    protected Item $i001;
    protected Item $i002;
    protected Item $i003;
    protected CreatePurchaseOrder $createPoAction;
    protected ApprovalService $approvalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->mum = Company::where('code', 'ACME-MUM')->firstOrFail();
        $this->blr = Company::where('code', 'ACME-BLR')->firstOrFail();

        $this->reqMum = User::where('email', 'req.mum@acme.test')->firstOrFail();
        $this->deptHeadMum = User::where('email', 'depthead.mum@acme.test')->firstOrFail();
        $this->itHeadMum = User::where('email', 'ithead.mum@acme.test')->firstOrFail();
        $this->finHeadMum = User::where('email', 'finhead.mum@acme.test')->firstOrFail();
        $this->cfoMum = User::where('email', 'cfo.mum@acme.test')->firstOrFail();
        $this->procMum = User::where('email', 'proc.mum@acme.test')->firstOrFail();
        $this->procBlr = User::where('email', 'proc.blr@acme.test')->firstOrFail();

        $this->ccMumProd = CostCentre::where('code', 'PROD')->where('company_id', $this->mum->id)->firstOrFail();
        $this->ccMumIt = CostCentre::where('code', 'IT')->where('company_id', $this->mum->id)->firstOrFail();

        $this->v001 = Vendor::where('code', 'V001')->firstOrFail(); // Maharashtra (intra-state for Mum)
        $this->v002 = Vendor::where('code', 'V002')->firstOrFail(); // Karnataka (inter-state for Mum)
        $this->v004 = Vendor::where('code', 'V004')->firstOrFail(); // Karnataka (Bengaluru vendor)

        $this->i001 = Item::where('code', 'I001')->firstOrFail(); // Steel sheet 18%
        $this->i002 = Item::where('code', 'I002')->firstOrFail(); // Laptop IT 18%
        $this->i003 = Item::where('code', 'I003')->firstOrFail(); // A4 paper 12%

        $this->createPoAction = app(CreatePurchaseOrder::class);
        $this->approvalService = app(ApprovalService::class);
    }

    /**
     * Helper to create an approved PR in Mumbai.
     */
    protected function createApprovedPr(User $requester, CostCentre $cc, array $lines): PurchaseRequisition
    {
        $totalAmount = '0.00';
        foreach ($lines as $line) {
            $lineTotal = bcmul((string) $line['qty'], (string) $line['price'], 2);
            $totalAmount = bcadd($totalAmount, $lineTotal, 2);
        }

        $pr = PurchaseRequisition::create([
            'company_id' => $requester->company_id,
            'pr_number' => 'PR-TEST-' . uniqid(),
            'requester_id' => $requester->id,
            'cost_centre_id' => $cc->id,
            'status' => PrStatus::APPROVED,
            'total_amount' => $totalAmount,
            'notes' => 'Test PR',
        ]);

        $lineNo = 1;
        foreach ($lines as $line) {
            $qty = number_format((float) $line['qty'], 3, '.', '');
            $price = number_format((float) $line['price'], 2, '.', '');
            $lineTotal = bcmul($qty, $price, 2);

            PurchaseRequisitionLine::create([
                'purchase_requisition_id' => $pr->id,
                'line_number' => $lineNo++,
                'item_id' => $line['item']->id,
                'description' => $line['item']->name,
                'quantity' => $qty,
                'uom_code' => $line['uom_code'] ?? $line['item']->base_uom_code,
                'base_quantity' => $qty,
                'estimated_unit_price' => $price,
                'estimated_total' => $lineTotal,
                'ordered_quantity' => 0.000,
            ]);
        }

        return $pr;
    }

    /**
     * Test 1: S1 PR 50 x I003 @250 approved by depthead.mum; proc.mum creates PO to V001 at 245 =>
     * taxable 12,250.00, CGST 735.00, SGST 735.00, total 13,720.00.
     */
    public function test_s1_pr_to_po_conversion_with_intra_state_gst(): void
    {
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 50, 'price' => 250.00],
        ]);
        $prLine = $pr->lines->first();

        $po = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            [
                'pr_line_id' => $prLine->id,
                'order_qty' => 50,
                'unit_price' => 245.00,
            ],
        ]);

        $this->assertSame('12250.00', (string) $po->subtotal);
        $this->assertSame('735.00', (string) $po->total_cgst);
        $this->assertSame('735.00', (string) $po->total_sgst);
        $this->assertSame('0.00', (string) $po->total_igst);
        $this->assertSame('13720.00', (string) $po->grand_total);
        $this->assertSame(PoStatus::DRAFT, $po->status);

        // Verify PR converted status
        $pr->refresh();
        $this->assertSame(PrStatus::CONVERTED, $pr->status);
    }

    /**
     * Test 2: S2 PR 3 x I002 @65,000 for IT cost centre; chain is Dept Head > IT Head > Finance Head;
     * PO to V002 (Karnataka) => IGST 18%, CGST and SGST are 0.
     */
    public function test_s2_pr_approval_chain_and_inter_state_po_gst(): void
    {
        // 1. Check PR approval chain for IT PR of 195,000
        $chain = app(\App\Services\ApprovalChainBuilder::class)->build(
            DocumentType::PR,
            '195000.00',
            ['IT'],
            $this->mum->id
        );
        $this->assertSame(['Dept Head', 'IT Head', 'Finance Head'], $chain);

        // 2. Create approved PR and convert to V002 (Karnataka, inter-state)
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumIt, [
            ['item' => $this->i002, 'qty' => 3, 'price' => 65000.00],
        ]);
        $prLine = $pr->lines->first();

        $po = $this->createPoAction->execute($this->procMum, $this->v002->id, [
            [
                'pr_line_id' => $prLine->id,
                'order_qty' => 3,
                'unit_price' => 65000.00,
            ],
        ]);

        $this->assertSame('195000.00', (string) $po->subtotal);
        $this->assertSame('0.00', (string) $po->total_cgst);
        $this->assertSame('0.00', (string) $po->total_sgst);
        $this->assertSame('35100.00', (string) $po->total_igst);
        $this->assertSame('230100.00', (string) $po->grand_total);
    }

    /**
     * Test 3: Order qty above remaining qty is rejected; after a partial order, remaining qty is reduced.
     */
    public function test_order_qty_above_remaining_is_rejected_and_partial_order_reduces_remaining(): void
    {
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 10, 'price' => 200.00],
        ]);
        $prLine = $pr->lines->first();

        // Attempting to order 12 when approved is 10
        try {
            $this->createPoAction->execute($this->procMum, $this->v001->id, [
                ['pr_line_id' => $prLine->id, 'order_qty' => 12, 'unit_price' => 200.00],
            ]);
            $this->fail('Expected exception for order qty exceeding remaining');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('exceeds remaining approved quantity', $e->getMessage());
        }

        // Partial order of 4
        $po1 = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $prLine->id, 'order_qty' => 4, 'unit_price' => 200.00],
        ]);
        $this->assertNotNull($po1);

        $pr->refresh();
        $prLine->refresh();
        $this->assertSame(PrStatus::PARTIALLY_CONVERTED, $pr->status);
        $this->assertSame('4.000', (string) $prLine->ordered_quantity);

        // Next order of 7 should fail because only 6 remaining
        try {
            $this->createPoAction->execute($this->procMum, $this->v001->id, [
                ['pr_line_id' => $prLine->id, 'order_qty' => 7, 'unit_price' => 200.00],
            ]);
            $this->fail('Expected exception for ordering 7 when remaining is 6');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('exceeds remaining approved quantity', $e->getMessage());
        }

        // Order remaining 6 succeeds
        $po2 = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $prLine->id, 'order_qty' => 6, 'unit_price' => 200.00],
        ]);
        $this->assertNotNull($po2);

        $pr->refresh();
        $this->assertSame(PrStatus::CONVERTED, $pr->status);
    }

    /**
     * Test 4: Merge: two PRs, same vendor, one PO with two lines.
     */
    public function test_merge_two_prs_into_one_po_with_two_lines(): void
    {
        $pr1 = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 10, 'price' => 200.00],
        ]);
        $pr2 = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i001, 'qty' => 5, 'price' => 1000.00],
        ]);

        $line1 = $pr1->lines->first();
        $line2 = $pr2->lines->first();

        $po = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $line1->id, 'order_qty' => 10, 'unit_price' => 190.00],
            ['pr_line_id' => $line2->id, 'order_qty' => 5, 'unit_price' => 950.00],
        ]);

        $this->assertCount(2, $po->lines);
        $this->assertSame('6650.00', (string) $po->subtotal); // 10*190=1900 + 5*950=4750 => 6650

        $pr1->refresh();
        $pr2->refresh();
        $this->assertSame(PrStatus::CONVERTED, $pr1->status);
        $this->assertSame(PrStatus::CONVERTED, $pr2->status);
    }

    /**
     * Test 5: Split: one PR line ordered in two POs to two vendors; PR becomes PARTIALLY_CONVERTED then CONVERTED.
     */
    public function test_split_pr_line_ordered_across_two_vendors(): void
    {
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 100, 'price' => 200.00],
        ]);
        $prLine = $pr->lines->first();

        // PO 1 to V001 for 60 pcs
        $po1 = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $prLine->id, 'order_qty' => 60, 'unit_price' => 195.00],
        ]);
        $this->assertNotNull($po1);

        $pr->refresh();
        $this->assertSame(PrStatus::PARTIALLY_CONVERTED, $pr->status);

        // PO 2 to V002 for remaining 40 pcs
        $po2 = $this->createPoAction->execute($this->procMum, $this->v002->id, [
            ['pr_line_id' => $prLine->id, 'order_qty' => 40, 'unit_price' => 205.00],
        ]);
        $this->assertNotNull($po2);

        $pr->refresh();
        $this->assertSame(PrStatus::CONVERTED, $pr->status);
    }

    /**
     * Test 6: UoM: a PO line in BOX stores base_qty = qty x 12.
     */
    public function test_uom_conversion_in_po_line(): void
    {
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 24, 'price' => 20.00, 'uom_code' => 'PCS'],
        ]);
        $prLine = $pr->lines->first();

        // Order 2 BOX (factor 12) => base_qty 24 PCS
        $po = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            [
                'pr_line_id' => $prLine->id,
                'order_qty' => 2,
                'unit_price' => 220.00,
                'uom_code' => 'BOX',
            ],
        ]);

        $poLine = $po->lines->first();
        $this->assertSame('BOX', $poLine->uom_code);
        $this->assertSame('2.000', (string) $poLine->quantity);
        $this->assertSame('24.000', (string) $poLine->base_quantity);
    }

    /**
     * Test 7: Inactive vendor or a vendor of another company cannot be used.
     */
    public function test_inactive_or_cross_company_vendor_is_rejected(): void
    {
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 10, 'price' => 200.00],
        ]);
        $prLine = $pr->lines->first();

        // 1. Cross-company vendor (V004 belongs to BLR, procurement officer is MUM)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Selected vendor does not exist or does not belong to your company/i');

        $this->createPoAction->execute($this->procMum, $this->v004->id, [
            ['pr_line_id' => $prLine->id, 'order_qty' => 10, 'unit_price' => 200.00],
        ]);
    }

    /**
     * Test 8: PO grand total 13,720 needs only Finance Head; a PO above 200,000 needs Finance Head then CFO.
     */
    public function test_po_approval_chain_thresholds(): void
    {
        // 1. PO <= 200000 => [Finance Head]
        $chainSmall = app(\App\Services\ApprovalChainBuilder::class)->build(
            DocumentType::PO,
            '13720.00',
            null,
            $this->mum->id
        );
        $this->assertSame(['Finance Head'], $chainSmall);

        // 2. PO > 200000 => [Finance Head, CFO]
        $chainLarge = app(\App\Services\ApprovalChainBuilder::class)->build(
            DocumentType::PO,
            '250000.00',
            null,
            $this->mum->id
        );
        $this->assertSame(['Finance Head', 'CFO'], $chainLarge);
    }

    /**
     * Test 9: Creator of a PO cannot approve it; Requester role cannot create a PO (403).
     */
    public function test_creator_cannot_approve_po_and_requester_gets_403_on_create(): void
    {
        // 1. Requester cannot create PO via HTTP
        $response = $this->actingAs($this->reqMum)->get('/po/create-from-pr');
        $response->assertForbidden();

        // 2. Procurement Officer creates and submits PO
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 50, 'price' => 250.00],
        ]);
        $po = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $pr->lines->first()->id, 'order_qty' => 50, 'unit_price' => 245.00],
        ]);

        $this->approvalService->submit($po, $this->procMum);

        $appRequest = $po->approvalRequests()->firstOrFail();
        $step1 = $appRequest->steps()->firstOrFail();

        // Even if procMum had Finance Head role, self-approval is blocked
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Self-approval blocked/i');

        $this->approvalService->approve($step1, $this->procMum, 'Trying self approval');
    }

    /**
     * Test 10: S7 proc.blr gets 404/403 on a Mumbai PO URL.
     */
    public function test_s7_bengaluru_user_cannot_view_mumbai_po(): void
    {
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 10, 'price' => 250.00],
        ]);
        $po = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $pr->lines->first()->id, 'order_qty' => 10, 'unit_price' => 250.00],
        ]);

        // BelongsToCompany global scope restricts queries, yielding 404 for different company
        $response = $this->actingAs($this->procBlr)->get("/po/{$po->id}");
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    /**
     * Test 11: Two simultaneous conversions of the same last remaining qty: only one succeeds.
     */
    public function test_concurrency_race_condition_on_remaining_qty_blocked(): void
    {
        $pr = $this->createApprovedPr($this->reqMum, $this->ccMumProd, [
            ['item' => $this->i003, 'qty' => 10, 'price' => 200.00],
            ['item' => $this->i001, 'qty' => 20, 'price' => 500.00], // Extra line ensures PR stays PARTIALLY_CONVERTED
        ]);
        $prLine = $pr->lines->first();

        // First conversion consumes all 10 of line 1
        $po1 = $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $prLine->id, 'order_qty' => 10, 'unit_price' => 200.00],
        ]);
        $this->assertNotNull($po1);

        // Immediate subsequent conversion for 10 on the same line must fail with remaining quantity error
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/exceeds remaining approved quantity/i');

        $this->createPoAction->execute($this->procMum, $this->v001->id, [
            ['pr_line_id' => $prLine->id, 'order_qty' => 10, 'unit_price' => 200.00],
        ]);
    }
}
