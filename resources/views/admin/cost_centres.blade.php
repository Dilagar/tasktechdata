@extends('layouts.app', ['title' => 'Cost Centres & Budgets - Admin'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Cost Centres & Budgets</h1>
            <p style="color: #64748b; margin: 0;">Scoped to <strong>{{ Auth::user()->company->name }}</strong></p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>FY 2026-27 Allocated Amount</th>
                <th>Committed</th>
                <th>Spent</th>
                <th>Available</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($costCentres as $cc)
                @php
                    $budget = $cc->budgets->firstWhere('financial_year', '2026-27');
                @endphp
                <tr>
                    <td><strong>{{ $cc->code }}</strong></td>
                    <td>{{ $cc->name }}</td>
                    <td>₹ {{ $budget ? number_format((float) $budget->amount, 2) : '0.00' }}</td>
                    <td>₹ {{ $budget ? number_format((float) $budget->committed, 2) : '0.00' }}</td>
                    <td>₹ {{ $budget ? number_format((float) $budget->spent, 2) : '0.00' }}</td>
                    <td>
                        @if ($budget)
                            <strong>₹ {{ number_format($budget->availableAmount(), 2) }}</strong>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8;">No cost centres found for this company.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
