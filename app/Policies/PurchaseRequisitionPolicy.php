<?php

namespace App\Policies;

use App\Enums\PrStatus;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseRequisitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseRequisition $pr): bool
    {
        // Must belong to same company
        return $user->company_id === $pr->company_id;
    }

    public function create(User $user): bool
    {
        // Any user in the company can raise a requisition
        return true;
    }

    public function update(User $user, PurchaseRequisition $pr): bool
    {
        // Only creator can edit, must belong to same company, and status must be DRAFT
        $status = is_object($pr->status) ? $pr->status->value : (string) $pr->status;

        return (int) $user->id === (int) $pr->requester_id &&
            $user->company_id === $pr->company_id &&
            $status === 'DRAFT';
    }

    public function submit(User $user, PurchaseRequisition $pr): bool
    {
        $status = is_object($pr->status) ? $pr->status->value : (string) $pr->status;

        return (int) $user->id === (int) $pr->requester_id &&
            $user->company_id === $pr->company_id &&
            $status === 'DRAFT';
    }

    public function delete(User $user, PurchaseRequisition $pr): bool
    {
        $status = is_object($pr->status) ? $pr->status->value : (string) $pr->status;

        return (int) $user->id === (int) $pr->requester_id &&
            $user->company_id === $pr->company_id &&
            $status === 'DRAFT';
    }
}
