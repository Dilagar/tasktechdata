@extends('layouts.app', ['title' => 'Vendor Details - Admin'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Vendor: {{ $vendor->name }}</h1>
            <p style="color: #64748b; margin: 0;">Code: <strong>{{ $vendor->code }}</strong> | Company: <strong>{{ $vendor->company->code }}</strong></p>
        </div>
        <a href="{{ route('admin.vendors') }}" class="btn btn-secondary">Back to Vendors</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
        <div style="background-color: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
            <strong>State:</strong> {{ $vendor->state }}
        </div>
        <div style="background-color: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
            <strong>GSTIN:</strong> <code>{{ $vendor->gstin ?? 'N/A' }}</code>
        </div>
        <div style="background-color: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
            <strong>Payment Terms:</strong> {{ $vendor->payment_terms_days }} Days
        </div>
        <div style="background-color: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
            <strong>Status:</strong> {{ $vendor->is_active ? 'Active' : 'Inactive' }}
        </div>
    </div>
</div>
@endsection
