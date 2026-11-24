<?php

namespace App\Policies;

use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any POs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Procurement Officer', 'Finance Head', 'CFO', 'Admin', 'Store Keeper']);
    }

    /**
     * Determine whether the user can view the PO.
     */
    public function view(User $user, PurchaseOrder $po): bool
    {
        return (int) $user->company_id === (int) $po->company_id;
    }

    /**
     * Determine whether the user can create POs.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Procurement Officer');
    }

    /**
     * Determine whether the user can update the PO.
     */
    public function update(User $user, PurchaseOrder $po): bool
    {
        if ((int) $user->company_id !== (int) $po->company_id) {
            return false;
        }

        return $user->hasRole('Procurement Officer') && $po->status === PoStatus::DRAFT;
    }

    /**
     * Determine whether the user can submit the PO for approval.
     */
    public function submit(User $user, PurchaseOrder $po): bool
    {
        if ((int) $user->company_id !== (int) $po->company_id) {
            return false;
        }

        return $user->hasRole('Procurement Officer') && $po->status === PoStatus::DRAFT;
    }

    /**
     * Determine whether the user can cancel the PO.
     */
    public function cancel(User $user, PurchaseOrder $po): bool
    {
        if ((int) $user->company_id !== (int) $po->company_id) {
            return false;
        }

        return $user->hasRole('Procurement Officer') && in_array($po->status, [PoStatus::DRAFT, PoStatus::PENDING_APPROVAL], true);
    }
}
