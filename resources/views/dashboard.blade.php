@extends('layouts.app', ['title' => 'Dashboard - P2P Portal'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Welcome, {{ $user->name }}</h1>
            <p style="color: #64748b; margin: 0;">{{ $user->email }}</p>
        </div>
        <span class="badge" style="font-size: 0.9rem; padding: 0.4rem 0.8rem; background-color: #dbeafe; color: #1e40af;">
            {{ $user->company->code }}
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
        <!-- Company Information -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem;">
            <h3 style="margin-top: 0; font-size: 1rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                Company Details
            </h3>
            <p style="margin: 0.5rem 0;"><strong>Company Name:</strong> {{ $user->company->name }}</p>
            <p style="margin: 0.5rem 0;"><strong>State:</strong> {{ $user->company->state }}</p>
            <p style="margin: 0.5rem 0;"><strong>GSTIN:</strong> {{ $user->company->gstin ?? 'N/A' }}</p>
        </div>

        <!-- User Roles -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem;">
            <h3 style="margin-top: 0; font-size: 1rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                Your Assigned Roles
            </h3>
            <div style="margin-top: 0.5rem;">
                @forelse ($user->roles as $role)
                    <span class="badge" style="background-color: #e0e7ff; color: #3730a3; padding: 0.35rem 0.75rem; font-size: 0.85rem; margin-bottom: 0.5rem;">
                        {{ $role->name }}
                    </span>
                @empty
                    <p style="color: #94a3b8; font-style: italic; margin: 0;">No roles assigned.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Admin Quick Navigation Section if user has Admin role --}}
@if ($user->hasRole('Admin'))
<div class="card">
    <h2 style="font-size: 1.2rem; margin-top: 0;">Administration Center ({{ $user->company->name }})</h2>
    <p style="color: #64748b; font-size: 0.9rem;">
        These administrative lists are strictly scoped to your entity (<strong>{{ $user->company->code }}</strong>) via the <code>BelongsToCompany</code> global scope.
    </p>

    <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
        <a href="{{ route('admin.users') }}" class="btn">View Company Users</a>
        <a href="{{ route('admin.vendors') }}" class="btn">View Company Vendors</a>
        <a href="{{ route('admin.cost_centres') }}" class="btn">View Cost Centres & Budgets</a>
    </div>
</div>
@endif
@endsection
