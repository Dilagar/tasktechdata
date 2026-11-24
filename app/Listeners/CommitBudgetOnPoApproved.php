<?php

namespace App\Listeners;

use App\Events\PurchaseOrderApproved;
use App\Services\BudgetService;

class CommitBudgetOnPoApproved
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected BudgetService $budgetService
    ) {
    }

    /**
     * Handle the event.
     *
     * IMPORTANT: This listener is intentionally SYNCHRONOUS (not ShouldQueue).
     * It MUST run synchronously inside the caller's database transaction so that
     * if BudgetExceededException is thrown, the entire transaction rolls back:
     * the final approval step remains PENDING, the PO remains PENDING_APPROVAL,
     * and the approving user receives the clear budget exceeded error message.
     */
    public function handle(PurchaseOrderApproved $event): void
    {
        $po = $event->po;

        // Commit budget for the PO's cost centre using grand_total
        $this->budgetService->commit(
            $po->cost_centre_id,
            $po->grand_total ?? $po->total_amount,
            $po
        );
    }
}
