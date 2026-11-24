<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use App\Policies\PurchaseRequisitionPolicy;
use App\Policies\VendorPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'PR' => PurchaseRequisition::class,
            'PO' => PurchaseOrder::class,
        ]);

        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(PurchaseRequisition::class, PurchaseRequisitionPolicy::class);

        // Gate: manage-users (Admin only)
        Gate::define('manage-users', function (User $user) {
            return $user->hasRole('Admin');
        });

        // Gate: manage-vendors (Admin or Procurement Officer)
        Gate::define('manage-vendors', function (User $user) {
            return $user->hasAnyRole(['Admin', 'Procurement Officer']);
        });

        // Gate: manage-masters (Admin or Procurement Officer)
        Gate::define('manage-masters', function (User $user) {
            return $user->hasAnyRole(['Admin', 'Procurement Officer']);
        });
    }
}
