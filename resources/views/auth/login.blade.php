<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Procure-to-Pay Portal</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 2rem 1rem;
        }
        .login-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }
        .header-title {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header-title h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
            color: #0f172a;
        }
        .header-title p {
            margin: 0;
            color: #64748b;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: #ffffff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: #334155;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 0.95rem;
        }
        input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .btn-submit {
            width: 100%;
            background-color: #2563eb;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease-in-out;
        }
        .btn-submit:hover {
            background-color: #1d4ed8;
        }
        .error-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        th, td {
            padding: 0.5rem 0.6rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            background-color: #e2e8f0;
            color: #1e293b;
            font-size: 0.75rem;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
        }
        .use-btn {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            text-decoration: none;
        }
        .use-btn:hover {
            background-color: #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="header-title">
            <h1>Procure-to-Pay (P2P) Back-Office</h1>
            <p>Sign in to access your company dashboard</p>
        </div>

        <div class="grid">
            <!-- Login Form -->
            <div class="card">
                <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem;">Sign In</h2>

                @if ($errors->any())
                    <div class="error-box">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', 'admin.mum@acme.test') }}" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" value="password" required>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember" style="margin-bottom: 0; cursor: pointer; font-size: 0.85rem;">Remember me</label>
                    </div>

                    <button type="submit" class="btn-submit">Sign In</button>
                </form>
            </div>

            <!-- Demo Accounts Table -->
            <div class="card">
                <h2 style="margin-top: 0; font-size: 1.2rem; color: #0f172a;">Demo Accounts (All password: <code>password</code>)</h2>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: -0.5rem; margin-bottom: 1rem;">
                    Click <strong>Fill</strong> to quickly populate the email address.
                </p>

                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Role(s)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($demoUsers as $demo)
                            <tr>
                                <td><code>{{ $demo['email'] }}</code></td>
                                <td><span class="badge">{{ $demo['company'] }}</span></td>
                                <td>{{ $demo['role'] }}</td>
                                <td>
                                    <button type="button" class="use-btn" onclick="document.getElementById('email').value='{{ $demo['email'] }}'; document.getElementById('password').value='password';">Fill</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
