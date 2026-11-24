<?php

namespace App\Http\Controllers;

use App\Enums\PrStatus;
use App\Http\Requests\StorePurchaseRequisitionRequest;
use App\Http\Requests\UpdatePurchaseRequisitionRequest;
use App\Models\CostCentre;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\UnitOfMeasure;
use App\Services\ApprovalService;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseRequisitionController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        protected ApprovalService $approvalService
    ) {
    }

    /**
     * List user's requisitions.
     */
    public function index(): View
    {
        $requisitions = PurchaseRequisition::with(['costCentre', 'requester'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('pr.index', compact('requisitions'));
    }

    /**
     * Show form to create a new PR.
     */
    public function create(): View
    {
        $costCentres = CostCentre::orderBy('name')->get();
        $items = Item::with('baseUom')->orderBy('name')->get();
        $uoms = UnitOfMeasure::all();

        return view('pr.create', compact('costCentres', 'items', 'uoms'));
    }

    /**
     * Store a newly created PR in DRAFT status.
     */
    public function store(StorePurchaseRequisitionRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $pr = DB::transaction(function () use ($request, $user) {
            $totalAmount = '0.00';
            $uomMap = UnitOfMeasure::all()->keyBy('code');

            // Generate PR number: PR-YYYYMMDD-XXXX
            $prNumber = 'PR-' . date('Ymd') . '-' . str_pad((string) (PurchaseRequisition::withoutGlobalScopes()->count() + 1), 4, '0', STR_PAD_LEFT);

            $pr = PurchaseRequisition::create([
                'company_id' => $user->company_id,
                'pr_number' => $prNumber,
                'requester_id' => $user->id,
                'cost_centre_id' => $request->validated('cost_centre_id'),
                'status' => PrStatus::DRAFT,
                'total_amount' => 0.00,
                'notes' => $request->validated('notes'),
            ]);

            $lineNumber = 1;
            foreach ($request->validated('lines') as $lineData) {
                $qty = number_format((float) $lineData['quantity'], 3, '.', '');
                $unitPrice = number_format((float) $lineData['estimated_unit_price'], 2, '.', '');
                $lineTotal = bcmul($qty, $unitPrice, 2);
                $totalAmount = bcadd($totalAmount, $lineTotal, 2);

                // Base quantity conversion factor
                $factor = $uomMap[$lineData['uom_code']]->factor ?? 1.000000;
                $baseQty = bcmul($qty, (string) $factor, 3);

                PurchaseRequisitionLine::create([
                    'purchase_requisition_id' => $pr->id,
                    'line_number' => $lineNumber++,
                    'item_id' => $lineData['item_id'],
                    'description' => $lineData['description'] ?? null,
                    'quantity' => $qty,
                    'uom_code' => $lineData['uom_code'],
                    'base_quantity' => $baseQty,
                    'estimated_unit_price' => $unitPrice,
                    'estimated_total' => $lineTotal,
                    'ordered_quantity' => 0.000,
                ]);
            }

            $pr->total_amount = $totalAmount;
            $pr->save();

            AuditLogger::log($pr, null, 'DRAFT', 'Purchase Requisition drafted', $user->id);

            return $pr;
        });

        return redirect()->route('pr.show', $pr)->with('success', 'Purchase Requisition drafted successfully.');
    }

    /**
     * Show details of a PR including approval snapshot chain and audit trail.
     */
    public function show(PurchaseRequisition $pr): View
    {
        $this->authorize('view', $pr);

        $pr->load([
            'requester',
            'costCentre',
            'lines.item',
            'lines.uom',
            'approvalRequests.steps.role',
            'approvalRequests.steps.actor',
            'auditLogs.user',
        ]);

        return view('pr.show', compact('pr'));
    }

    /**
     * Show form to edit an existing DRAFT PR.
     */
    public function edit(PurchaseRequisition $pr): View
    {
        $this->authorize('update', $pr);

        $pr->load(['lines.item']);
        $costCentres = CostCentre::orderBy('name')->get();
        $items = Item::with('baseUom')->orderBy('name')->get();
        $uoms = UnitOfMeasure::all();

        return view('pr.edit', compact('pr', 'costCentres', 'items', 'uoms'));
    }

    /**
     * Update an existing DRAFT PR.
     */
    public function update(UpdatePurchaseRequisitionRequest $request, PurchaseRequisition $pr): RedirectResponse
    {
        $this->authorize('update', $pr);

        DB::transaction(function () use ($request, $pr) {
            $totalAmount = '0.00';
            $uomMap = UnitOfMeasure::all()->keyBy('code');

            $pr->cost_centre_id = $request->validated('cost_centre_id');
            $pr->notes = $request->validated('notes');

            // Recreate line items
            $pr->lines()->delete();

            $lineNumber = 1;
            foreach ($request->validated('lines') as $lineData) {
                $qty = number_format((float) $lineData['quantity'], 3, '.', '');
                $unitPrice = number_format((float) $lineData['estimated_unit_price'], 2, '.', '');
                $lineTotal = bcmul($qty, $unitPrice, 2);
                $totalAmount = bcadd($totalAmount, $lineTotal, 2);

                $factor = $uomMap[$lineData['uom_code']]->factor ?? 1.000000;
                $baseQty = bcmul($qty, (string) $factor, 3);

                PurchaseRequisitionLine::create([
                    'purchase_requisition_id' => $pr->id,
                    'line_number' => $lineNumber++,
                    'item_id' => $lineData['item_id'],
                    'description' => $lineData['description'] ?? null,
                    'quantity' => $qty,
                    'uom_code' => $lineData['uom_code'],
                    'base_quantity' => $baseQty,
                    'estimated_unit_price' => $unitPrice,
                    'estimated_total' => $lineTotal,
                    'ordered_quantity' => 0.000,
                ]);
            }

            $pr->total_amount = $totalAmount;
            $pr->save();

            AuditLogger::log($pr, 'DRAFT', 'DRAFT', 'Draft updated', Auth::id());
        });

        return redirect()->route('pr.show', $pr)->with('success', 'Purchase Requisition updated successfully.');
    }

    /**
     * Submit a DRAFT PR to trigger the snapshot workflow.
     */
    public function submit(PurchaseRequisition $pr): RedirectResponse
    {
        $this->authorize('submit', $pr);

        $this->approvalService->submit($pr, Auth::user());

        return redirect()->route('pr.show', $pr)->with('success', 'Purchase Requisition submitted for approval.');
    }
}
