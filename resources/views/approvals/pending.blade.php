@extends('layouts.app', ['title' => 'Pending My Approval'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Pending My Approval</h1>
            <p style="color: #64748b; margin: 0;">Documents waiting for action on a step matching your assigned roles.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Dashboard</a>
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
                <th>Doc Type</th>
                <th>Document #</th>
                <th>Requester</th>
                <th>Cost Centre</th>
                <th>Amount</th>
                <th>Current Role</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pendingSteps as $step)
                @php
                    $doc = $step->request->document;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><span class="badge" style="background-color: #dbeafe; color: #1e40af;">{{ $step->request->document_type->value }}</span></td>
                    <td><strong>{{ $doc->pr_number ?? $doc->po_number }}</strong></td>
                    <td>{{ $doc->requester?->name ?? $doc->buyer?->name }}</td>
                    <td>{{ $doc->costCentre?->code ?? '-' }}</td>
                    <td><strong>₹ {{ number_format((float) $doc->total_amount, 2) }}</strong></td>
                    <td><span class="badge" style="background-color: #fef3c7; color: #92400e;">Step {{ $step->sequence }}: {{ $step->role->name }}</span></td>
                    <td>
                        <a href="{{ route('pr.show', $doc) }}" class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">Review & Act</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2rem;">No documents currently pending your approval.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
