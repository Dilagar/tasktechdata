<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form with demo accounts.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demoUsers = [
            // Mumbai
            ['email' => 'admin.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'Admin'],
            ['email' => 'req.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'Requester'],
            ['email' => 'depthead.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'Dept Head + Requester'],
            ['email' => 'ithead.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'IT Head'],
            ['email' => 'finhead.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'Finance Head'],
            ['email' => 'cfo.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'CFO'],
            ['email' => 'proc.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'Procurement Officer'],
            ['email' => 'store.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'Store Keeper'],
            ['email' => 'accounts.mum@acme.test', 'company' => 'ACME-MUM', 'role' => 'Accounts'],
            // Bengaluru
            ['email' => 'admin.blr@acme.test', 'company' => 'ACME-BLR', 'role' => 'Admin'],
            ['email' => 'req.blr@acme.test', 'company' => 'ACME-BLR', 'role' => 'Requester'],
            ['email' => 'depthead.blr@acme.test', 'company' => 'ACME-BLR', 'role' => 'Dept Head'],
            ['email' => 'proc.blr@acme.test', 'company' => 'ACME-BLR', 'role' => 'Procurement Officer'],
        ];

        return view('auth.login', compact('demoUsers'));
    }

    /**
     * Handle incoming authentication request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
