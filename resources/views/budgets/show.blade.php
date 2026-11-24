@extends('layouts.app', ['title' => 'Budget Ledger - ' . $budget->costCentre?->code])

@section('content')
@php
    $available = $budget->availableAmount();
    $isLow = bccomp($available, '0.00', 2) <= 0;
@endphp

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 style="margin: 0;">Budget: {{ $budget->costCentre?->name }} ({{ $budget->costCentre?->code }})</h1>
                <span class="badge" style="background-color: #dbeafe; color: #1e40af; font-size: 0.9rem;">FY {{ $budget->financial_year }}</span>
            </div>
            <p style="color: #64748b; margin: 0.25rem 0 0 0;">
                Company: {{ $budget->company->name }}
            </p>
        </div>
        <a href="{{ route('budgets.index') }}" class="btn btn-secondary">← Back to Budgets</a>
    </div>

    <!-- Overview Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Allocated Budget</div>
            <div style="font-size: 1.25rem; font-weight: 700; margin-top: 0.25rem;">
                ₹ {{ number_format((float) $budget->amount, 2) }}
            </div>
        </div>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Committed</div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #92400e; margin-top: 0.25rem;">
                ₹ {{ number_format((float) $budget->committed, 2) }}
            </div>
        </div>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Spent</div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #475569; margin-top: 0.25rem;">
                ₹ {{ number_format((float) $budget->spent, 2) }}
            </div>
        </div>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Available Balance</div>
            <div style="font-size: 1.25rem; font-weight: 700; color: {{ $isLow ? '#dc2626' : '#166534' }}; margin-top: 0.25rem;">
                ₹ {{ number_format((float) $available, 2) }}
            </div>
        </div>
    </div>

    <h3>Append-Only Budget Transaction Ledger</h3>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Type</th>
                <th>PO Number</th>
                <th>Transaction Amount</th>
                <th>Balance Committed</th>
                <th>Balance Spent</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($budget->transactions as $txn)
                <tr>
                    <td>{{ $txn->created_at->format('M d, Y H:i:s') }}</td>
                    <td>
                        @if ($txn->type === 'COMMIT')
                            <span class="badge" style="background-color: #fef3c7; color: #92400e;">COMMIT</span>
                        @elseif ($txn->type === 'RELEASE')
                            <span class="badge" style="background-color: #dcfce7; color: #166534;">RELEASE</span>
                        @elseif ($txn->type === 'SPEND')
                            <span class="badge" style="background-color: #e0e7ff; color: #3730a3;">SPEND</span>
                        @else
                            <span class="badge">{{ $txn->type }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($txn->purchaseOrder)
                            <a href="{{ route('po.show', $txn->purchaseOrder) }}" style="color: #2563eb; text-decoration: underline;">
                                {{ $txn->purchaseOrder->po_number }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td><strong>₹ {{ number_format((float) $txn->amount, 2) }}</strong></td>
                    <td>₹ {{ number_format((float) $txn->balance_after_committed, 2) }}</td>
                    <td>₹ {{ number_format((float) $txn->balance_after_spent, 2) }}</td>
                    <td>{{ $txn->user?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2rem;">No budget transactions logged yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
