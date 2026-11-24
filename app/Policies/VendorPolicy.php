<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Procurement Officer']);
    }

    /**
     * Determine whether the user can view the model.
     * Checks both company isolation and role authorization.
     */
    public function view(User $user, Vendor $vendor): bool
    {
        return $user->company_id === $vendor->company_id &&
            $user->hasAnyRole(['Admin', 'Procurement Officer']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Procurement Officer']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vendor $vendor): bool
    {
        return $user->company_id === $vendor->company_id &&
            $user->hasAnyRole(['Admin', 'Procurement Officer']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->company_id === $vendor->company_id &&
            $user->hasRole('Admin');
    }
}
