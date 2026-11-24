@extends('layouts.app', ['title' => 'Company Vendors - Admin'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Vendors</h1>
            <p style="color: #64748b; margin: 0;">Showing active vendors registered under <strong>{{ Auth::user()->company->name }}</strong></p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Vendor Name</th>
                <th>State</th>
                <th>GSTIN</th>
                <th>Payment Terms</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vendors as $v)
                <tr>
                    <td><strong>{{ $v->code }}</strong></td>
                    <td>{{ $v->name }}</td>
                    <td>{{ $v->state }}</td>
                    <td><code>{{ $v->gstin ?? 'N/A' }}</code></td>
                    <td>{{ $v->payment_terms_days }} days</td>
                    <td>
                        @if ($v->is_active)
                            <span class="badge" style="background-color: #dcfce7; color: #15803d;">Active</span>
                        @else
                            <span class="badge" style="background-color: #fee2e2; color: #b91c1c;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.vendors.show', $v) }}" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8;">No vendors found for this company.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
