@extends('layouts.app', ['title' => 'Purchase Orders'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Purchase Orders (PO)</h1>
            <p style="color: #64748b; margin: 0;">Manage vendor procurement orders created from approved requisition lines.</p>
        </div>
        @if (Auth::user()->hasRole('Procurement Officer'))
            <a href="{{ route('po.create-from-pr') }}" class="btn" style="background-color: #2563eb;">
                + Create PO from PR Lines
            </a>
        @endif
    </div>

    @if (session('success'))
        <div style="background-color: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            {{ session('success') }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">S.No</th>
                <th>PO Number</th>
                <th>Vendor</th>
                <th>Buyer</th>
                <th>Cost Centre</th>
                <th>Grand Total</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchaseOrders as $po)
                @php
                    $statusStr = is_object($po->status) ? $po->status->value : $po->status;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $po->po_number }}</strong></td>
                    <td>{{ $po->vendor?->name }} ({{ $po->vendor?->state }})</td>
                    <td>{{ $po->buyer?->name }}</td>
                    <td>{{ $po->costCentre?->code ?? '-' }}</td>
                    <td><strong>₹ {{ number_format((float) ($po->grand_total ?? $po->total_amount), 2) }}</strong></td>
                    <td>
                        @if ($statusStr === 'APPROVED')
                            <span class="badge" style="background-color: #dcfce7; color: #166534;">APPROVED</span>
                        @elseif ($statusStr === 'PENDING_APPROVAL')
                            <span class="badge" style="background-color: #fef3c7; color: #92400e;">PENDING APPROVAL</span>
                        @elseif ($statusStr === 'CANCELLED')
                            <span class="badge" style="background-color: #fee2e2; color: #991b1b;">CANCELLED</span>
                        @else
                            <span class="badge" style="background-color: #f1f5f9; color: #475569;">DRAFT</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('po.show', $po) }}" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2rem;">No purchase orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        {{ $purchaseOrders->links() }}
    </div>
</div>
@endsection
