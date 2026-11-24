<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's dashboard.
     */
    public function index(): View
    {
        $user = Auth::user()->load(['company', 'roles']);

        return view('dashboard', compact('user'));
    }
}
