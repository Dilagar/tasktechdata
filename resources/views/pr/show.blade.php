@extends('layouts.app', ['title' => 'PR - ' . $pr->pr_number])

@section('content')
@php
    $statusStr = is_object($pr->status) ? $pr->status->value : $pr->status;
    $approvalRequest = $pr->approvalRequests->first();
@endphp

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 style="margin: 0;">{{ $pr->pr_number }}</h1>
                @if ($statusStr === 'APPROVED')
                    <span class="badge" style="background-color: #dcfce7; color: #166534; font-size: 0.9rem;">APPROVED</span>
                @elseif ($statusStr === 'REJECTED')
                    <span class="badge" style="background-color: #fee2e2; color: #991b1b; font-size: 0.9rem;">REJECTED</span>
                @elseif ($statusStr === 'SUBMITTED')
                    <span class="badge" style="background-color: #fef3c7; color: #92400e; font-size: 0.9rem;">SUBMITTED (IN APPROVAL)</span>
                @else
                    <span class="badge" style="background-color: #f1f5f9; color: #475569; font-size: 0.9rem;">DRAFT</span>
                @endif
            </div>
            <p style="color: #64748b; margin: 0.25rem 0 0 0;">
                Created by <strong>{{ $pr->requester?->name }}</strong> on {{ $pr->created_at->format('M d, Y H:i') }}
            </p>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            @if ($statusStr === 'DRAFT' && Auth::id() === $pr->requester_id)
                <a href="{{ route('pr.edit', $pr) }}" class="btn btn-secondary">Edit Draft</a>
                <form id="submitPrForm" method="POST" action="{{ route('pr.submit', $pr) }}">
                    @csrf
                    <button type="button" class="btn" style="background-color: #16a34a;" onclick="confirmSubmitPr()">Submit for Approval</button>
                </form>
            @endif
            <a href="{{ route('pr.index') }}" class="btn btn-secondary">Back to Requisitions</a>
        </div>
    </div>

    @if (session('success'))
        <div style="background-color: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            {{ session('success') }}
        </div>
    @endif

    <!-- Metadata Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Cost Centre</div>
            <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.25rem;">{{ $pr->costCentre?->code }} - {{ $pr->costCentre?->name }}</div>
        </div>
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Total Estimated Amount</div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #0284c7; margin-top: 0.25rem;">₹ {{ number_format((float) $pr->total_amount, 2) }}</div>
        </div>
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Entity</div>
            <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.25rem;">{{ Auth::user()->company->name }} ({{ Auth::user()->company->code }})</div>
        </div>
    </div>

    @if ($pr->notes)
        <div style="background-color: #fffbeb; border: 1px solid #fde68a; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem;">
            <strong>Requisition Notes:</strong> {{ $pr->notes }}
        </div>
    @endif

    <!-- Line Items Table -->
    <h3 style="margin-top: 1.5rem; margin-bottom: 0.5rem;">Requisition Line Items</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Category</th>
                <th>Item Code & Name</th>
                <th>Quantity</th>
                <th>UOM</th>
                <th>Base Qty</th>
                <th>Est. Unit Price</th>
                <th>Est. Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pr->lines as $line)
                <tr>
                    <td>{{ $line->line_number }}</td>
                    <td><span class="badge">{{ $line->item?->category?->value ?? $line->item?->category }}</span></td>
                    <td>
                        <strong>{{ $line->item?->code }}</strong> - {{ $line->item?->name }}
                        @if ($line->description)
                            <div style="color: #64748b; font-size: 0.8rem;">{{ $line->description }}</div>
                        @endif
                    </td>
                    <td>{{ number_format((float) $line->quantity, 3) }}</td>
                    <td>{{ $line->uom_code }}</td>
                    <td>{{ number_format((float) $line->base_quantity, 3) }}</td>
                    <td>₹ {{ number_format((float) $line->estimated_unit_price, 2) }}</td>
                    <td><strong>₹ {{ number_format((float) $line->estimated_total, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Frozen Approval Snapshot Chain Section -->
@if ($approvalRequest)
<div class="card">
    <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Approval Chain (Snapshot)</h2>
    <p style="color: #64748b; font-size: 0.85rem; margin-top: 0;">
        Immutable snapshot frozen on submission. Status: <strong>{{ $approvalRequest->status }}</strong> (Current Step: Sequence {{ $approvalRequest->current_step }})
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Seq</th>
                <th>Required Role</th>
                <th>Step Status</th>
                <th>Acted By</th>
                <th>Acted At</th>
                <th>Approver Comments</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($approvalRequest->steps as $step)
                @php
                    $isCurrentStep = ($approvalRequest->status === 'PENDING' && $step->sequence === $approvalRequest->current_step);
                    $stepStatusStr = is_object($step->status) ? $step->status->value : $step->status;
                @endphp
                <tr style="{{ $isCurrentStep ? 'background-color: #f0fdf4;' : '' }}">
                    <td>
                        <strong>Step {{ $step->sequence }}</strong>
                        @if ($isCurrentStep)
                            <span class="badge" style="background-color: #bbf7d0; color: #166534; font-size: 0.7rem;">CURRENT</span>
                        @endif
                    </td>
                    <td><strong>{{ $step->role?->name }}</strong></td>
                    <td>
                        @if ($stepStatusStr === 'APPROVED')
                            <span class="badge" style="background-color: #dcfce7; color: #166534;">APPROVED</span>
                        @elseif ($stepStatusStr === 'REJECTED')
                            <span class="badge" style="background-color: #fee2e2; color: #991b1b;">REJECTED</span>
                        @else
                            <span class="badge" style="background-color: #f1f5f9; color: #475569;">PENDING</span>
                        @endif
                    </td>
                    <td>{{ $step->actor?->name ?? '-' }}</td>
                    <td>{{ $step->acted_at?->format('M d, Y H:i') ?? '-' }}</td>
                    <td>{{ $step->comment ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Interactive Approval / Rejection Box if current logged-in user is eligible --}}
    @php
        $currentPendingStep = $approvalRequest->steps
            ->where('status', \App\Enums\ApprovalStepStatus::PENDING)
            ->where('sequence', $approvalRequest->current_step)
            ->first();

        $canAct = $currentPendingStep &&
                  Auth::user()->hasRole($currentPendingStep->role?->name) &&
                  (int)Auth::id() !== (int)$pr->requester_id &&
                  Auth::user()->company_id === $pr->company_id;
    @endphp

    @if ($canAct)
        <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 1.25rem; margin-top: 1.5rem;">
            <h3 style="margin-top: 0; color: #1e40af; font-size: 1rem;">
                Action Required: You are eligible to act on Step {{ $currentPendingStep->sequence }} as {{ $currentPendingStep->role->name }}
            </h3>

            <div style="display: flex; gap: 2rem; margin-top: 1rem; flex-wrap: wrap;">
                <!-- Approve Form -->
                <form id="approveStepForm" method="POST" action="{{ route('approvals.approve', $currentPendingStep) }}" style="flex: 1; min-width: 280px;">
                    @csrf
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem;">Approval Remarks (Optional)</label>
                    <input type="text" name="comment" placeholder="e.g. Budget verified, approved." style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 0.75rem;">
                    <button type="button" class="btn" style="background-color: #16a34a;" onclick="confirmApproveStep()">
                        ✓ Approve Step
                    </button>
                </form>

                <!-- Reject Form -->
                <form id="rejectStepForm" method="POST" action="{{ route('approvals.reject', $currentPendingStep) }}" style="flex: 1; min-width: 280px;">
                    @csrf
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: #b91c1c;">Rejection Reason (Mandatory) *</label>
                    <input type="text" id="rejectCommentInput" name="comment" required placeholder="e.g. Price exceeds quotation benchmark" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 0.75rem;">
                    <button type="button" class="btn" style="background-color: #dc2626;" onclick="confirmRejectStep()">
                        ✕ Reject Requisition
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endif

<!-- Audit Trail Section -->
<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 0.5rem;">Append-Only Audit Trail</h3>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Transition</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pr->auditLogs as $log)
                <tr>
                    <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                    <td><strong>{{ $log->user?->name ?? 'System' }}</strong></td>
                    <td>
                        <code>{{ $log->from_status ?? 'START' }}</code> → <strong>{{ $log->to_status }}</strong>
                    </td>
                    <td>{{ $log->comment ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="color: #94a3b8; text-align: center;">No audit logs recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    async function confirmSubmitPr() {
        const ok = await showModernConfirm({
            title: 'Submit Requisition',
            message: 'Are you sure you want to submit this PR for approval? A frozen snapshot of the approval chain will be generated.',
            confirmText: 'Submit PR',
            confirmColor: '#16a34a',
            iconType: 'success'
        });
        if (ok) {
            document.getElementById('submitPrForm').submit();
        }
    }

    async function confirmApproveStep() {
        const ok = await showModernConfirm({
            title: 'Approve Step',
            message: 'Are you sure you want to approve this requisition step?',
            confirmText: 'Approve',
            confirmColor: '#16a34a',
            iconType: 'success'
        });
        if (ok) {
            document.getElementById('approveStepForm').submit();
        }
    }

    async function confirmRejectStep() {
        const comment = document.getElementById('rejectCommentInput').value.trim();
        if (!comment) {
            await showModernConfirm({
                title: 'Rejection Reason Required',
                message: 'Please provide a valid mandatory rejection reason before rejecting.',
                confirmText: 'Got it',
                confirmColor: '#dc2626',
                iconType: 'danger'
            });
            document.getElementById('rejectCommentInput').focus();
            return;
        }

        const ok = await showModernConfirm({
            title: 'Reject Requisition',
            message: 'Are you sure you want to reject this requisition? It will be returned to the requester.',
            confirmText: 'Reject',
            confirmColor: '#dc2626',
            iconType: 'danger'
        });
        if (ok) {
            document.getElementById('rejectStepForm').submit();
        }
    }
</script>
@endsection
