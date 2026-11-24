<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostCentre;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display users for the authenticated user's company.
     */
    public function users(): View
    {
        // BelongsToCompany automatically scopes User::query()
        $users = User::with('roles')->orderBy('name')->get();

        return view('admin.users', compact('users'));
    }

    /**
     * Display vendors for the authenticated user's company.
     */
    public function vendors(): View
    {
        // BelongsToCompany automatically scopes Vendor::query()
        $vendors = Vendor::orderBy('name')->get();

        return view('admin.vendors', compact('vendors'));
    }

    /**
     * Show a single vendor (verifies route-model binding company isolation & policy).
     */
    public function showVendor(Vendor $vendor): View
    {
        // Route-model binding automatically applies CompanyScope => returns 404 for another company's record
        // In addition, VendorPolicy can be authorized
        $this->authorize('view', $vendor);

        return view('admin.vendor_show', compact('vendor'));
    }

    /**
     * Display cost centres for the authenticated user's company.
     */
    public function costCentres(): View
    {
        // BelongsToCompany automatically scopes CostCentre::query()
        $costCentres = CostCentre::with('budgets')->orderBy('code')->get();

        return view('admin.cost_centres', compact('costCentres'));
    }
}
