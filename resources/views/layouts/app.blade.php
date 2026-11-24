<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'P2P Portal' }}</title>
    <style>
        :root {
            --bg-color: #f8fafc;
            --text-color: #1e293b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --badge-bg: #e0e7ff;
            --badge-text: #3730a3;
            --sidebar-bg: #0f172a;
            --sidebar-text: #94a3b8;
            --sidebar-hover: #1e293b;
            --sidebar-active: #38bdf8;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.5;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        aside.sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid #1e293b;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid #1e293b;
        }
        .sidebar-brand h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #38bdf8;
            margin: 0;
        }
        .sidebar-brand span {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .sidebar-nav {
            padding: 1rem 0;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .nav-section-title {
            padding: 0.75rem 1.25rem 0.25rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 700;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }
        .sidebar-nav a:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }
        .sidebar-nav a.active {
            background-color: #1e293b;
            color: var(--sidebar-active);
            border-left: 3px solid var(--sidebar-active);
            font-weight: 600;
        }
        .sidebar-footer {
            padding: 1.25rem;
            border-top: 1px solid #1e293b;
            background-color: #0b1120;
        }
        .user-info {
            margin-bottom: 0.75rem;
        }
        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #f1f5f9;
        }
        .user-company {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .logout-btn {
            width: 100%;
            background-color: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .logout-btn:hover {
            background-color: #dc2626;
        }

        /* Main Content Area */
        main.main-content {
            flex-grow: 1;
            padding: 2rem 2.5rem;
            overflow-y: auto;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        h1, h2, h3 {
            margin-top: 0;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
        }
        th {
            background-color: #f1f5f9;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            background-color: var(--badge-bg);
            color: var(--badge-text);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            margin-right: 0.25rem;
        }
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn:hover {
            background-color: var(--primary-hover);
        }
        .btn-secondary {
            background-color: #64748b;
        }
        .btn-secondary:hover {
            background-color: #475569;
        }
    </style>
</head>
<body>
    @auth
    <!-- Left Side Navigation Bar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2>P2P Back-Office</h2>
            <span>Entity: <strong>{{ Auth::user()->company->code }}</strong></span>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-title">General</span>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            @if(Auth::user()->hasAnyRole(['Admin', 'Procurement Officer']))
                <span class="nav-section-title">Administration</span>

                @if(Auth::user()->hasRole('Admin'))
                    <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Users
                    </a>
                @endif

                <a href="{{ route('admin.vendors') }}" class="{{ request()->routeIs('admin.vendors*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Vendors
                </a>

                @if(Auth::user()->hasRole('Admin'))
                    <a href="{{ route('admin.cost_centres') }}" class="{{ request()->routeIs('admin.cost_centres*') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Cost Centres
                    </a>
                @endif
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-name">{{ Auth::user()->name }}</div>
                <div class="user-company">{{ Auth::user()->company->name }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">Log Out</button>
            </form>
        </div>
    </aside>
    @endauth

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="container">
            @yield('content')
        </div>
    </main>
</body>
</html>
