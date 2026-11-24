@extends('layouts.app', ['title' => 'Purchase Requisitions'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Purchase Requisitions</h1>
            <p style="color: #64748b; margin: 0;">Entity: <strong>{{ Auth::user()->company->name }}</strong></p>
        </div>
        <a href="{{ route('pr.create') }}" class="btn">+ Raise New PR</a>
    </div>

    @if (session('success'))
        <div style="background-color: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">S.No</th>
                <th>PR Number</th>
                <th>Requester</th>
                <th>Cost Centre</th>
                <th>Total Value</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requisitions as $pr)
                @php
                    $statusStr = is_object($pr->status) ? $pr->status->value : $pr->status;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $pr->pr_number }}</strong></td>
                    <td>{{ $pr->requester?->name }}</td>
                    <td>{{ $pr->costCentre?->code }}</td>
                    <td><strong>₹ {{ number_format((float) $pr->total_amount, 2) }}</strong></td>
                    <td>
                        @if ($statusStr === 'APPROVED')
                            <span class="badge" style="background-color: #dcfce7; color: #166534;">APPROVED</span>
                        @elseif ($statusStr === 'REJECTED')
                            <span class="badge" style="background-color: #fee2e2; color: #991b1b;">REJECTED</span>
                        @elseif ($statusStr === 'SUBMITTED')
                            <span class="badge" style="background-color: #fef3c7; color: #92400e;">SUBMITTED</span>
                        @else
                            <span class="badge" style="background-color: #f1f5f9; color: #475569;">DRAFT</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('pr.show', $pr) }}" class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2rem;">No requisitions created yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        {{ $requisitions->links() }}
    </div>
</div>
@endsection
