<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class BudgetController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display budget overview for the user's company.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Budget::class);

        $budgets = Budget::with('costCentre')
            ->orderBy('financial_year', 'desc')
            ->orderBy('cost_centre_id')
            ->get();

        return view('budgets.index', compact('budgets'));
    }

    /**
     * Display budget detail with full append-only transaction history.
     */
    public function show(Budget $budget): View
    {
        $this->authorize('view', $budget);

        $budget->load([
            'costCentre',
            'transactions.purchaseOrder',
            'transactions.user',
        ]);

        return view('budgets.show', compact('budget'));
    }
}
