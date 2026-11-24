<?php

namespace App\Services;

use App\Exceptions\BudgetExceededException;
use App\Models\Budget;
use App\Models\BudgetTransaction;
use App\Models\CostCentre;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BudgetService
{
    /**
     * Commit budget for a Purchase Order in a database transaction with pessimistic locking.
     *
     * @param CostCentre|int $costCentre Cost centre instance or ID
     * @param string|float|int $amount Amount to commit (string decimal)
     * @param PurchaseOrder $po The Purchase Order being committed
     * @param User|null $user The user triggering the commitment (or null)
     * @return Budget
     * @throws BudgetExceededException
     */
    public function commit(CostCentre|int $costCentre, string|float|int $amount, PurchaseOrder $po, ?User $user = null): Budget
    {
        $this->ensureInTransaction();

        $costCentreId = $costCentre instanceof CostCentre ? $costCentre->id : $costCentre;
        $costCentreModel = $costCentre instanceof CostCentre ? $costCentre : CostCentre::findOrFail($costCentreId);

        $amountStr = number_format((float) $amount, 2, '.', '');
        if (bccomp($amountStr, '0.00', 2) <= 0) {
            throw new InvalidArgumentException("Commit amount must be greater than zero. Received: {$amountStr}");
        }

        // Determine Financial Year from PO date (po_date), fallback to now()
        $fy = FinancialYear::forDate($po->po_date ?? $po->created_at ?? now());

        // Lock the budget row for (company, cost_centre, financial year)
        $budget = Budget::withoutGlobalScopes()
            ->where('company_id', $po->company_id)
            ->where('cost_centre_id', $costCentreId)
            ->where('financial_year', $fy)
            ->lockForUpdate()
            ->first();

        if (! $budget) {
            throw new BudgetExceededException(
                "No budget allocation found for cost centre {$costCentreModel->code} in financial year {$fy}."
            );
        }

        // available = amount - committed - spent
        $used = bcadd((string) $budget->committed, (string) $budget->spent, 2);
        $available = bcsub((string) $budget->amount, $used, 2);

        // If available < amount to commit => throw BudgetExceededException
        if (bccomp($available, $amountStr, 2) < 0) {
            throw new BudgetExceededException(
                "Budget exceeded for cost centre {$costCentreModel->code} (FY {$fy}). Available: {$available}, required: {$amountStr}"
            );
        }

        // Increase committed
        $newCommitted = bcadd((string) $budget->committed, $amountStr, 2);
        $budget->committed = $newCommitted;
        $budget->save();

        // Update PO committed_amount
        $po->committed_amount = $amountStr;
        $po->save();

        // Write append-only budget transaction
        $userId = $user?->id ?? Auth::id();
        BudgetTransaction::withoutGlobalScopes()->create([
            'company_id' => $budget->company_id,
            'budget_id' => $budget->id,
            'purchase_order_id' => $po->id,
            'type' => 'COMMIT',
            'amount' => $amountStr,
            'balance_after_committed' => $budget->committed,
            'balance_after_spent' => $budget->spent,
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        return $budget;
    }

    /**
     * Release committed budget for a Purchase Order (e.g. when cancelled).
     *
     * @param PurchaseOrder $po The Purchase Order being released
     * @param User|null $user The user triggering the release
     * @return Budget|null
     */
    public function release(PurchaseOrder $po, ?User $user = null): ?Budget
    {
        $this->ensureInTransaction();

        $amountToRelease = (string) $po->committed_amount;
        if (bccomp($amountToRelease, '0.00', 2) <= 0) {
            return null; // Nothing committed to release
        }

        $fy = FinancialYear::forDate($po->po_date ?? $po->created_at ?? now());

        $budget = Budget::withoutGlobalScopes()
            ->where('company_id', $po->company_id)
            ->where('cost_centre_id', $po->cost_centre_id)
            ->where('financial_year', $fy)
            ->lockForUpdate()
            ->first();

        if (! $budget) {
            return null;
        }

        // Reduce committed by the PO's remaining committed amount, never below zero
        $newCommitted = bcsub((string) $budget->committed, $amountToRelease, 2);
        if (bccomp($newCommitted, '0.00', 2) < 0) {
            $newCommitted = '0.00';
        }

        $budget->committed = $newCommitted;
        $budget->save();

        // Set PO committed_amount to 0
        $po->committed_amount = '0.00';
        $po->save();

        // Write append-only budget transaction
        $userId = $user?->id ?? Auth::id();
        BudgetTransaction::withoutGlobalScopes()->create([
            'company_id' => $budget->company_id,
            'budget_id' => $budget->id,
            'purchase_order_id' => $po->id,
            'type' => 'RELEASE',
            'amount' => $amountToRelease,
            'balance_after_committed' => $budget->committed,
            'balance_after_spent' => $budget->spent,
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        return $budget;
    }

    /**
     * Post an invoice against a Purchase Order: moves amount from committed to spent.
     * Prepared for later invoice integration, guarded with lockForUpdate.
     *
     * @param PurchaseOrder $po The Purchase Order being invoiced
     * @param string|float|int $amount Amount to spend
     * @param User|null $user The user triggering the spend
     * @return Budget
     */
    public function postInvoice(PurchaseOrder $po, string|float|int $amount, ?User $user = null): Budget
    {
        $this->ensureInTransaction();

        $amountStr = number_format((float) $amount, 2, '.', '');
        if (bccomp($amountStr, '0.00', 2) <= 0) {
            throw new InvalidArgumentException("Spend amount must be greater than zero. Received: {$amountStr}");
        }

        $fy = FinancialYear::forDate($po->po_date ?? $po->created_at ?? now());

        $budget = Budget::withoutGlobalScopes()
            ->where('company_id', $po->company_id)
            ->where('cost_centre_id', $po->cost_centre_id)
            ->where('financial_year', $fy)
            ->lockForUpdate()
            ->firstOrFail();

        // Deduct from committed, add to spent
        $newCommitted = bcsub((string) $budget->committed, $amountStr, 2);
        if (bccomp($newCommitted, '0.00', 2) < 0) {
            $newCommitted = '0.00';
        }

        $newSpent = bcadd((string) $budget->spent, $amountStr, 2);

        $budget->committed = $newCommitted;
        $budget->spent = $newSpent;
        $budget->save();

        // Deduct from PO committed_amount
        $poRem = bcsub((string) $po->committed_amount, $amountStr, 2);
        $po->committed_amount = bccomp($poRem, '0.00', 2) < 0 ? '0.00' : $poRem;
        $po->save();

        // Write append-only budget transaction
        $userId = $user?->id ?? Auth::id();
        BudgetTransaction::withoutGlobalScopes()->create([
            'company_id' => $budget->company_id,
            'budget_id' => $budget->id,
            'purchase_order_id' => $po->id,
            'type' => 'SPEND',
            'amount' => $amountStr,
            'balance_after_committed' => $budget->committed,
            'balance_after_spent' => $budget->spent,
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        return $budget;
    }

    /**
     * Ensure the budget operation is executed within an active database transaction.
     */
    protected function ensureInTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('BudgetService operations must be executed inside a database transaction.');
        }
    }
}
