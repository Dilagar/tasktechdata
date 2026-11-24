@extends('layouts.app', ['title' => 'PO - ' . $po->po_number])

@section('content')
@php
    $statusStr = is_object($po->status) ? $po->status->value : $po->status;
    $approvalRequest = $po->approvalRequests->first();
@endphp

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 style="margin: 0;">{{ $po->po_number }}</h1>
                @if ($statusStr === 'APPROVED')
                    <span class="badge" style="background-color: #dcfce7; color: #166534; font-size: 0.9rem;">APPROVED</span>
                @elseif ($statusStr === 'PENDING_APPROVAL')
                    <span class="badge" style="background-color: #fef3c7; color: #92400e; font-size: 0.9rem;">PENDING APPROVAL</span>
                @elseif ($statusStr === 'CANCELLED')
                    <span class="badge" style="background-color: #fee2e2; color: #991b1b; font-size: 0.9rem;">CANCELLED</span>
                @else
                    <span class="badge" style="background-color: #f1f5f9; color: #475569; font-size: 0.9rem;">DRAFT</span>
                @endif
            </div>
            <p style="color: #64748b; margin: 0.25rem 0 0 0;">
                Created by Buyer <strong>{{ $po->buyer?->name }}</strong> on {{ $po->created_at->format('M d, Y H:i') }}
            </p>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            @if ($statusStr === 'DRAFT' && Auth::user()->hasRole('Procurement Officer'))
                <a href="{{ route('po.edit', $po) }}" class="btn btn-secondary">Edit PO</a>
                <form id="submitPoForm" method="POST" action="{{ route('po.submit', $po) }}">
                    @csrf
                    <button type="button" class="btn" style="background-color: #16a34a;" onclick="confirmSubmitPo()">
                        Submit for Approval
                    </button>
                </form>
            @endif

            @if (in_array($statusStr, ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'PARTIALLY_RECEIVED']) && Auth::user()->hasRole('Procurement Officer'))
                <form id="cancelPoForm" method="POST" action="{{ route('po.cancel', $po) }}">
                    @csrf
                    <input type="hidden" name="comment" id="cancelPoCommentInput" value="">
                    <button type="button" class="btn" style="background-color: #dc2626;" onclick="confirmCancelPo('{{ $statusStr }}')">
                        Cancel PO
                    </button>
                </form>
            @endif

            <a href="{{ route('po.index') }}" class="btn btn-secondary">Back to PO List</a>
        </div>
    </div>

    @if (session('success'))
        <div style="background-color: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <!-- Header Metadata -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Vendor</div>
            <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.25rem;">{{ $po->vendor?->name }}</div>
            <div style="font-size: 0.85rem; color: #64748b;">{{ $po->vendor?->state }} | GSTIN: {{ $po->vendor?->gstin }}</div>
        </div>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Cost Centre</div>
            <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.25rem;">{{ $po->costCentre?->code ?? '-' }}</div>
            <div style="font-size: 0.85rem; color: #64748b;">{{ $po->costCentre?->name ?? 'General' }}</div>
        </div>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Grand Total</div>
            <div style="font-size: 1.3rem; font-weight: 700; color: #166534; margin-top: 0.25rem;">
                ₹ {{ number_format((float) ($po->grand_total ?? $po->total_amount), 2) }}
            </div>
            <div style="font-size: 0.8rem; color: #64748b;">Taxable: ₹ {{ number_format((float) $po->subtotal, 2) }}</div>
        </div>
    </div>

    @if ($po->notes)
        <div style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1.5rem;">
            <strong>PO Instructions / Notes:</strong> {{ $po->notes }}
        </div>
    @endif

    <h3>Purchase Order Lines & GST Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Item Details</th>
                <th>Ordered Qty</th>
                <th>Base Qty</th>
                <th>Unit Price</th>
                <th>Taxable Val</th>
                <th>GST %</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>IGST</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($po->lines as $line)
                <tr>
                    <td>{{ $line->line_number }}</td>
                    <td>
                        <strong>{{ $line->item?->code }} - {{ $line->item?->name }}</strong>
                    </td>
                    <td>{{ number_format((float) $line->quantity, 3) }} {{ $line->uom_code }}</td>
                    <td>{{ number_format((float) $line->base_quantity, 3) }} {{ $line->item?->base_uom_code }}</td>
                    <td>₹ {{ number_format((float) $line->unit_price, 2) }}</td>
                    <td>₹ {{ number_format((float) $line->taxable_value, 2) }}</td>
                    <td>{{ number_format((float) $line->gst_rate, 0) }}%</td>
                    <td>₹ {{ number_format((float) $line->cgst, 2) }}</td>
                    <td>₹ {{ number_format((float) $line->sgst, 2) }}</td>
                    <td>₹ {{ number_format((float) $line->igst, 2) }}</td>
                    <td><strong>₹ {{ number_format((float) $line->line_total, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: 600;">
                <td colspan="5" style="text-align: right;">Totals:</td>
                <td>₹ {{ number_format((float) $po->subtotal, 2) }}</td>
                <td>-</td>
                <td>₹ {{ number_format((float) $po->total_cgst, 2) }}</td>
                <td>₹ {{ number_format((float) $po->total_sgst, 2) }}</td>
                <td>₹ {{ number_format((float) $po->total_igst, 2) }}</td>
                <td style="color: #166534; font-size: 1.05rem;">₹ {{ number_format((float) ($po->grand_total ?? $po->total_amount), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Approval Chain Snapshot Section --}}
@if ($approvalRequest)
<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 0.25rem;">Approval Chain (Snapshot)</h3>
    <p style="color: #64748b; font-size: 0.85rem; margin-top: 0;">
        Status: <strong>{{ $approvalRequest->status }}</strong> (Current Step: Sequence {{ $approvalRequest->current_step }})
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Seq</th>
                <th>Required Role</th>
                <th>Step Status</th>
                <th>Acted By</th>
                <th>Acted At</th>
                <th>Remarks</th>
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

    {{-- Interactive Approve / Reject Box if current user has the current step role --}}
    @php
        $currentPendingStep = $approvalRequest->steps
            ->where('status', \App\Enums\ApprovalStepStatus::PENDING)
            ->where('sequence', $approvalRequest->current_step)
            ->first();
        $userCanAct = $currentPendingStep &&
                      Auth::user()->hasRole($currentPendingStep->role->name) &&
                      (int) Auth::id() !== (int) $po->buyer_id;
    @endphp

    @if ($userCanAct)
        <div style="margin-top: 1.5rem; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem;">
            <h4 style="margin-top: 0; margin-bottom: 0.5rem; color: #0f172a;">Action Required: Step {{ $currentPendingStep->sequence }} ({{ $currentPendingStep->role->name }})</h4>

            <div style="display: flex; gap: 2rem; margin-top: 1rem; flex-wrap: wrap;">
                <!-- Approve Form -->
                <form id="approvePoStepForm" method="POST" action="{{ route('approvals.approve', $currentPendingStep) }}" style="flex: 1; min-width: 280px;">
                    @csrf
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem;">Approval Remarks (Optional)</label>
                    <input type="text" name="comment" placeholder="e.g. Quotation & pricing verified, approved." style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 0.75rem;">
                    <button type="button" class="btn" style="background-color: #16a34a;" onclick="confirmApprovePoStep()">
                        ✓ Approve Purchase Order
                    </button>
                </form>

                <!-- Reject Form -->
                <form id="rejectPoStepForm" method="POST" action="{{ route('approvals.reject', $currentPendingStep) }}" style="flex: 1; min-width: 280px;">
                    @csrf
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: #b91c1c;">Rejection Reason (Mandatory) *</label>
                    <input type="text" id="rejectPoCommentInput" name="comment" required placeholder="e.g. Price too high or missing warranty terms" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 0.75rem;">
                    <button type="button" class="btn" style="background-color: #dc2626;" onclick="confirmRejectPoStep()">
                        ✕ Reject PO (Back to Draft)
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
            @forelse ($auditLogs as $log)
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
    async function confirmSubmitPo() {
        const ok = await showModernConfirm({
            title: 'Submit Purchase Order',
            message: 'Are you sure you want to submit this PO for approval? A frozen snapshot of the approval chain will be generated.',
            confirmText: 'Submit PO',
            confirmColor: '#16a34a',
            iconType: 'success'
        });
        if (ok) {
            document.getElementById('submitPoForm').submit();
        }
    }

    async function confirmCancelPo(status) {
        let comment = 'Purchase Order cancelled by procurement officer';
        if (status === 'APPROVED' || status === 'PARTIALLY_RECEIVED') {
            const promptReason = prompt('Please enter the mandatory reason for cancelling this ' + status + ' PO (Committed budget will be released):');
            if (!promptReason || promptReason.trim() === '') {
                alert('A cancellation comment is required.');
                return;
            }
            comment = promptReason.trim();
        }

        const ok = await showModernConfirm({
            title: 'Cancel Purchase Order',
            message: 'Are you sure you want to cancel this Purchase Order? Committed budget will be released back.',
            confirmText: 'Cancel PO',
            confirmColor: '#dc2626',
            iconType: 'danger'
        });
        if (ok) {
            document.getElementById('cancelPoCommentInput').value = comment;
            document.getElementById('cancelPoForm').submit();
        }
    }

    async function confirmApprovePoStep() {
        const ok = await showModernConfirm({
            title: 'Approve PO Step',
            message: 'Are you sure you want to approve this Purchase Order step?',
            confirmText: 'Approve',
            confirmColor: '#16a34a',
            iconType: 'success'
        });
        if (ok) {
            document.getElementById('approvePoStepForm').submit();
        }
    }

    async function confirmRejectPoStep() {
        const comment = document.getElementById('rejectPoCommentInput').value.trim();
        if (!comment) {
            await showModernConfirm({
                title: 'Rejection Reason Required',
                message: 'Please provide a valid mandatory rejection reason before rejecting.',
                confirmText: 'Got it',
                confirmColor: '#dc2626',
                iconType: 'danger'
            });
            document.getElementById('rejectPoCommentInput').focus();
            return;
        }

        const ok = await showModernConfirm({
            title: 'Reject Purchase Order',
            message: 'Are you sure you want to reject this PO? It will be returned to DRAFT status.',
            confirmText: 'Reject to Draft',
            confirmColor: '#dc2626',
            iconType: 'danger'
        });
        if (ok) {
            document.getElementById('rejectPoStepForm').submit();
        }
    }
</script>
@endsection
