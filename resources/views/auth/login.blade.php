<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P2P Portal - Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --slate-950: #090d16;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-400: #94a3b8;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --emerald-600: #059669;
            --emerald-50: #ecfdf5;
            --amber-600: #d97706;
            --amber-50: #fffbeb;
            --sky-600: #0284c7;
            --sky-50: #f0f9ff;
            --purple-600: #7c3aed;
            --purple-50: #faf5ff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.08) 0%, transparent 50%),
                        #0b0f19;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-container {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
        }

        /* Brand Header */
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 1.1rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #c7d2fe;
            margin-bottom: 1rem;
            backdrop-filter: blur(8px);
        }

        .logo-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #6366f1;
            box-shadow: 0 0 12px #6366f1;
        }

        .brand-header h1 {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #ffffff;
            margin-bottom: 0.4rem;
        }

        .brand-header p {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        /* Main Card */
        .glass-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.03);
            overflow: hidden;
            position: relative;
        }

        /* Card Top Glow Accent */
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4, #10b981);
        }

        /* Segmented Navigation Switcher */
        .tab-switcher {
            display: flex;
            background: rgba(15, 23, 42, 0.7);
            padding: 0.5rem;
            margin: 1.5rem 1.75rem 0.5rem 1.75rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            gap: 0.5rem;
        }

        .tab-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 0.92rem;
            font-weight: 600;
            border-radius: 9px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.04);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
        }

        .tab-count {
            background: rgba(255, 255, 255, 0.15);
            font-size: 0.75rem;
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            font-weight: 700;
        }

        /* Tab Panes */
        .tab-content {
            padding: 1.75rem;
        }

        .pane {
            display: none;
            animation: fadeIn 0.25s ease-in-out;
        }

        .pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.88rem;
            color: #e2e8f0;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper svg.left-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            stroke: #64748b;
            pointer-events: none;
            transition: stroke 0.2s;
        }

        .toggle-password-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0.25rem;
            cursor: pointer;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color 0.15s, background-color 0.15s;
        }

        .toggle-password-btn:hover {
            color: #c7d2fe;
            background-color: rgba(255, 255, 255, 0.08);
        }

        .toggle-password-btn svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

        .input-wrapper input {
            width: 100% !important;
            padding: 0.75rem 2.75rem 0.75rem 2.6rem !important;
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid #334155 !important;
            border-radius: 10px !important;
            font-size: 0.95rem !important;
            color: #ffffff !important;
            font-family: inherit !important;
            transition: all 0.2s !important;
            box-sizing: border-box !important;
            line-height: 1.5 !important;
        }

        .input-wrapper input:focus {
            outline: none !important;
            border-color: #6366f1 !important;
            background: rgba(15, 23, 42, 0.9) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
        }

        .input-wrapper input:focus ~ svg.left-icon {
            stroke: #818cf8;
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: #94a3b8;
            user-select: none;
        }

        .checkbox-label input {
            accent-color: #4f46e5;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* Submit Button */
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: white;
            padding: 0.85rem 1.25rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Alert notifications */
        .error-alert {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.88rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .toast-msg {
            display: none;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 0.65rem 0.9rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            align-items: center;
            gap: 0.5rem;
        }

        /* Demo Accounts List Styling */
        .demo-search-box {
            margin-bottom: 1rem;
            position: relative;
        }

        .demo-search-input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid #334155;
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.85rem;
            font-family: inherit;
        }

        .demo-search-input:focus {
            outline: none;
            border-color: #6366f1;
        }

        .demo-scroll-wrapper {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
        }

        .demo-scroll-wrapper::-webkit-scrollbar {
            width: 6px;
        }
        .demo-scroll-wrapper::-webkit-scrollbar-thumb {
            background-color: #334155;
            border-radius: 3px;
        }

        .demo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(15, 23, 42, 0.4);
            transition: background 0.15s;
        }

        .demo-item:last-child {
            border-bottom: none;
        }

        .demo-item:hover {
            background: rgba(30, 41, 59, 0.6);
        }

        .demo-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .demo-email {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #f1f5f9;
            font-weight: 500;
        }

        .demo-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .entity-tag {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            background: #1e293b;
            color: #94a3b8;
            border: 1px solid #334155;
        }

        .entity-mum {
            background: rgba(59, 130, 246, 0.15);
            color: #93c5fd;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .entity-blr {
            background: rgba(168, 85, 247, 0.15);
            color: #d8b4fe;
            border-color: rgba(168, 85, 247, 0.3);
        }

        .role-badge {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            display: inline-block;
        }

        /* Role Badges Color Palette */
        .role-admin { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .role-depthead { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }
        .role-ithead { background: rgba(6, 182, 212, 0.15); color: #67e8f9; border: 1px solid rgba(6, 182, 212, 0.3); }
        .role-finhead { background: rgba(245, 158, 11, 0.15); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.3); }
        .role-cfo { background: rgba(168, 85, 247, 0.15); color: #e9d5ff; border: 1px solid rgba(168, 85, 247, 0.3); }
        .role-proc { background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
        .role-store { background: rgba(244, 63, 94, 0.15); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.3); }
        .role-accounts { background: rgba(20, 184, 166, 0.15); color: #5eead4; border: 1px solid rgba(20, 184, 166, 0.3); }
        .role-requester { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }

        .btn-fill {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-fill:hover {
            background: #4f46e5;
            border-color: #6366f1;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.4);
        }

        /* Responsive */
        @media (max-width: 640px) {
            body { padding: 1rem 0.75rem; }
            .brand-header h1 { font-size: 1.5rem; }
            .glass-card { border-radius: 16px; }
            .tab-switcher { margin: 1rem 1rem 0.5rem 1rem; }
            .tab-content { padding: 1.25rem 1rem; }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <!-- Brand Header -->
        <div class="brand-header">
            <div class="logo-badge">
                <span class="logo-dot"></span>
                Procure-to-Pay Enterprise
            </div>
            <h1>Back-Office Portal</h1>
            <p>Multi-company approval engine & procurement management</p>
        </div>

        <!-- Glass Card with Segmented Toggle -->
        <div class="glass-card">

            <!-- Switcher Tabs -->
            <div class="tab-switcher">
                <button type="button" class="tab-btn active" id="tabBtnLogin" onclick="switchTab('login')">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Sign In
                </button>
                <button type="button" class="tab-btn" id="tabBtnDemo" onclick="switchTab('demo')">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Demo Accounts
                    <span class="tab-count">{{ count($demoUsers) }}</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">

                <!-- Toast alert when credentials filled from demo tab -->
                <div id="fillToast" class="toast-msg">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="toastText">Account credentials filled! Ready to sign in.</span>
                </div>

                <!-- 1. LOGIN PANE -->
                <div id="paneLogin" class="pane active">

                    @if ($errors->any())
                        <div class="error-alert">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="flex-shrink: 0; margin-top: 1px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <strong style="display: block; margin-bottom: 0.2rem;">Authentication failed:</strong>
                                <ul style="margin: 0; padding-left: 1.1rem; line-height: 1.4;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                        @csrf

                        <div class="form-group">
                            <label for="email">Work Email</label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" value="{{ old('email', 'admin.mum@acme.test') }}" required autofocus autocomplete="username" placeholder="name@acme.test">
                                <svg class="left-icon" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" value="password" required autocomplete="current-password" placeholder="••••••••">
                                <svg class="left-icon" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <button type="button" class="toggle-password-btn" id="togglePasswordBtn" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility" title="Show/Hide password">
                                    <!-- Eye Open Icon (Default when hidden, click to show) -->
                                    <svg id="eyeOpenIcon" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <!-- Eye Slashed / Off Icon (Hidden initially, shown when password visible) -->
                                    <svg id="eyeSlashIcon" fill="none" viewBox="0 0 24 24" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" id="remember" name="remember">
                                Keep me signed in
                            </label>
                            <span style="color: #64748b; font-size: 0.8rem;">Acme Multi-Tenant</span>
                        </div>

                        <button type="submit" class="btn-primary" id="btnSignIn">
                            <span>Sign In to Dashboard</span>
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </form>

                    <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 1.25rem;">
                        <span style="font-size: 0.85rem; color: #64748b;">Testing approval rules or multi-tenancy?</span>
                        <a href="javascript:void(0)" onclick="switchTab('demo')" style="font-size: 0.85rem; color: #818cf8; text-decoration: none; font-weight: 600; margin-left: 0.35rem;">Browse Demo Users &rarr;</a>
                    </div>
                </div>

                <!-- 2. DEMO ACCOUNTS PANE -->
                <div id="paneDemo" class="pane">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.8rem;">
                        <p style="font-size: 0.85rem; color: #94a3b8; margin: 0;">
                            Pre-configured test personas with passwords pre-set to <code style="color: #67e8f9; background: rgba(6, 182, 212, 0.1); padding: 0.15rem 0.35rem; border-radius: 4px;">password</code>.
                        </p>
                    </div>

                    <div class="demo-search-box">
                        <input type="text" id="demoSearch" class="demo-search-input" placeholder="Quick filter by role or email..." oninput="filterDemoUsers()">
                    </div>

                    <div class="demo-scroll-wrapper">
                        @foreach ($demoUsers as $demo)
                            @php
                                $roleKey = strtolower(str_replace(' ', '', $demo['role']));
                                $badgeClass = 'role-requester';
                                if (str_contains($roleKey, 'admin')) $badgeClass = 'role-admin';
                                elseif (str_contains($roleKey, 'depthead')) $badgeClass = 'role-depthead';
                                elseif (str_contains($roleKey, 'ithead')) $badgeClass = 'role-ithead';
                                elseif (str_contains($roleKey, 'finance') || str_contains($roleKey, 'finhead')) $badgeClass = 'role-finhead';
                                elseif (str_contains($roleKey, 'cfo')) $badgeClass = 'role-cfo';
                                elseif (str_contains($roleKey, 'procurement') || str_contains($roleKey, 'proc')) $badgeClass = 'role-proc';
                                elseif (str_contains($roleKey, 'store')) $badgeClass = 'role-store';
                                elseif (str_contains($roleKey, 'accounts')) $badgeClass = 'role-accounts';

                                $entityClass = str_contains($demo['company'], 'MUM') ? 'entity-mum' : 'entity-blr';
                            @endphp
                            <div class="demo-item" data-search="{{ strtolower($demo['email'] . ' ' . $demo['company'] . ' ' . $demo['role']) }}">
                                <div class="demo-info">
                                    <div class="demo-email">{{ $demo['email'] }}</div>
                                    <div class="demo-meta">
                                        <span class="entity-tag {{ $entityClass }}">{{ $demo['company'] }}</span>
                                        <span class="role-badge {{ $badgeClass }}">{{ $demo['role'] }}</span>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn-fill" onclick="fillAndSwitch('{{ $demo['email'] }}', '{{ $demo['role'] }}')">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Fill &amp; Sign In
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top: 1.25rem; text-align: center;">
                        <button type="button" onclick="switchTab('login')" style="background: none; border: none; color: #94a3b8; font-size: 0.85rem; cursor: pointer; text-decoration: underline;">
                            &larr; Back to Sign In Form
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const loginPane = document.getElementById('paneLogin');
            const demoPane = document.getElementById('paneDemo');
            const tabBtnLogin = document.getElementById('tabBtnLogin');
            const tabBtnDemo = document.getElementById('tabBtnDemo');

            if (tab === 'login') {
                loginPane.classList.add('active');
                demoPane.classList.remove('active');
                tabBtnLogin.classList.add('active');
                tabBtnDemo.classList.remove('active');
                document.getElementById('email').focus();
            } else {
                loginPane.classList.remove('active');
                demoPane.classList.add('active');
                tabBtnLogin.classList.remove('active');
                tabBtnDemo.classList.add('active');
                document.getElementById('demoSearch').focus();
            }
        }

        function fillAndSwitch(email, role) {
            // Fill values
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';

            // Show toast message
            const toast = document.getElementById('fillToast');
            const toastText = document.getElementById('toastText');
            toastText.textContent = `Selected: ${email} (${role}). Click Sign In to continue.`;
            toast.style.display = 'flex';

            // Switch to login tab
            switchTab('login');

            // Focus submit button with a subtle pulse
            const submitBtn = document.getElementById('btnSignIn');
            submitBtn.focus();
        }

        function filterDemoUsers() {
            const query = document.getElementById('demoSearch').value.toLowerCase().trim();
            const items = document.querySelectorAll('.demo-item');

            items.forEach(item => {
                const searchData = item.getAttribute('data-search');
                if (searchData.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eyeOpenIcon');
            const eyeSlash = document.getElementById('eyeSlashIcon');

            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeOpen.style.display = 'none';
                eyeSlash.style.display = 'block';
            } else {
                pwdInput.type = 'password';
                eyeOpen.style.display = 'block';
                eyeSlash.style.display = 'none';
            }
        }
    </script>
</body>
</html>
