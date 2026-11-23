<?php

namespace App\Traits;

use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait for a model.
     */
    public static function bootBelongsToCompany(): void
    {
        // Register global scope to restrict queries to authenticated user's company
        static::addGlobalScope(new CompanyScope());

        // Automatically set company_id on model creation if authenticated user has company_id
        static::creating(function ($model) {
            if (empty($model->company_id) && Auth::check() && Auth::user()->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    /**
     * Relationship to Company.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
