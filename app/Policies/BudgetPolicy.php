<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    /**
     * Determine whether the user can view budgets.
     * Admin, Finance Head, CFO, Procurement Officer can view.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Finance Head', 'CFO', 'Procurement Officer']);
    }

    /**
     * Determine whether the user can view the specific budget.
     */
    public function view(User $user, Budget $budget): bool
    {
        if ((int) $user->company_id !== (int) $budget->company_id) {
            return false;
        }

        return $user->hasAnyRole(['Admin', 'Finance Head', 'CFO', 'Procurement Officer']);
    }
}
