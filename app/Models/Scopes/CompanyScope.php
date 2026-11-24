<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Avoid infinite recursion when the User model itself is resolving during Auth::user()
        if (Auth::hasUser()) {
            $user = Auth::user();
            if ($user && $user->company_id) {
                $builder->where($model->qualifyColumn('company_id'), $user->company_id);
            }
        }
    }
}
