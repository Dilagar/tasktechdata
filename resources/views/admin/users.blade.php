@extends('layouts.app', ['title' => 'Company Users - Admin'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Company Users</h1>
            <p style="color: #64748b; margin: 0;">Showing employees for <strong>{{ Auth::user()->company->name }} ({{ Auth::user()->company->code }})</strong></p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">S.No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Roles</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $u)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td><code>{{ $u->email }}</code></td>
                    <td>
                        @foreach ($u->roles as $role)
                            <span class="badge">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td>{{ $u->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8;">No users found for this company.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
