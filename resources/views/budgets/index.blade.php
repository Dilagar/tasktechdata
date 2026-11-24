@extends('layouts.app', ['title' => 'Cost Centre Budgets'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Cost Centre Budgets</h1>
            <p style="color: #64748b; margin: 0;">Financial year allocations, commitments, spend and live availability.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">S.No</th>
                <th>Cost Centre</th>
                <th>Financial Year</th>
                <th>Total Budget (₹)</th>
                <th>Committed (₹)</th>
                <th>Spent (₹)</th>
                <th>Available (₹)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($budgets as $budget)
                @php
                    $available = $budget->availableAmount();
                    $isLow = bccomp($available, '0.00', 2) <= 0;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $budget->costCentre?->code }}</strong>
                        <div style="font-size: 0.8rem; color: #64748b;">{{ $budget->costCentre?->name }}</div>
                    </td>
                    <td><span class="badge" style="background-color: #dbeafe; color: #1e40af;">FY {{ $budget->financial_year }}</span></td>
                    <td><strong>₹ {{ number_format((float) $budget->amount, 2) }}</strong></td>
                    <td style="color: #92400e;">₹ {{ number_format((float) $budget->committed, 2) }}</td>
                    <td style="color: #475569;">₹ {{ number_format((float) $budget->spent, 2) }}</td>
                    <td>
                        <strong style="color: {{ $isLow ? '#dc2626' : '#166534' }};">
                            ₹ {{ number_format((float) $available, 2) }}
                        </strong>
                    </td>
                    <td>
                        <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                            View Ledger
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2rem;">No budgets configured for your company.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
