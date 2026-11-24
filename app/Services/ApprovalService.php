<?php

namespace App\Services;

use App\Enums\ApprovalStepStatus;
use App\Enums\DocumentType;
use App\Enums\PoStatus;
use App\Enums\PrStatus;
use App\Events\ApprovalCompleted;
use App\Events\PurchaseOrderApproved;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ApprovalService
{
    public function __construct(
        protected ApprovalChainBuilder $chainBuilder
    ) {
    }

    /**
     * Submit a document into the approval workflow.
     * Builds the approval chain, applies the SELF-APPROVAL rule (S8),
     * creates an immutable snapshot (approval_requests + approval_steps),
     * updates the document status, and writes an audit log.
     */
    public function submit(Model $document, User $creator): ApprovalRequest
    {
        return DB::transaction(function () use ($document, $creator) {
            // Determine document type
            $documentType = $document instanceof PurchaseRequisition ? DocumentType::PR : DocumentType::PO;

            // Validate valid initial transition (DRAFT -> SUBMITTED)
            $fromStatus = is_object($document->status) ? $document->status->value : (string) $document->status;
            if ($fromStatus !== 'DRAFT') {
                throw new RuntimeException("Only documents in DRAFT status can be submitted. Current status: {$fromStatus}");
            }

            // Extract distinct line item categories
            $categories = [];
            if (method_exists($document, 'lines')) {
                $categories = $document->lines()
                    ->with('item')
                    ->get()
                    ->map(fn($line) => $line->item?->category?->value ?? $line->item?->category)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }

            // 1. Build the baseline approval chain (role names) from DB rules
            $amountToEvaluate = $document instanceof PurchaseOrder
                ? ($document->grand_total ?? $document->total_amount)
                : $document->total_amount;

            $chain = $this->chainBuilder->build(
                $documentType,
                $amountToEvaluate,
                $categories,
                $document->company_id
            );

            // 2. SELF-APPROVAL RULE (S8):
            // When building the snapshot, remove any step whose role the CREATOR holds.
            // If that leaves zero steps, add "Finance Head" as the fallback approver.
            // (e.g. depthead.mum raises a PR of 12,500 => chain "Dept Head" is removed, fallback becomes Finance Head).
            $creatorRoleNames = $creator->roles()->pluck('name')->toArray();
            $filteredChain = [];
            foreach ($chain as $roleName) {
                if (! in_array($roleName, $creatorRoleNames, true)) {
                    $filteredChain[] = $roleName;
                }
            }

            if (empty($filteredChain)) {
                $filteredChain[] = 'Finance Head';
            }
            $finalChain = array_values($filteredChain);

            // 3. Create one approval_requests snapshot row
            $approvalRequest = ApprovalRequest::withoutGlobalScopes()->create([
                'company_id' => $document->company_id,
                'document_type' => $documentType,
                'document_id' => $document->id,
                'status' => 'PENDING',
                'current_step' => 1,
            ]);

            // 4. Create approval_steps snapshot rows
            $sequence = 1;
            foreach ($finalChain as $roleName) {
                $role = Role::where('name', $roleName)->firstOrFail();
                ApprovalStep::create([
                    'approval_request_id' => $approvalRequest->id,
                    'sequence' => $sequence++,
                    'role_id' => $role->id,
                    'status' => ApprovalStepStatus::PENDING,
                    'acted_by' => null,
                    'comment' => null,
                    'acted_at' => null,
                ]);
            }

            // 5. Update document status
            if ($document instanceof PurchaseRequisition) {
                $document->status = PrStatus::SUBMITTED;
                $toStatus = 'SUBMITTED';
            } elseif ($document instanceof PurchaseOrder) {
                $document->status = PoStatus::PENDING_APPROVAL;
                $toStatus = 'PENDING_APPROVAL';
            } else {
                $toStatus = 'SUBMITTED';
                $document->status = $toStatus;
            }
            $document->save();

            // 6. Record append-only audit log
            AuditLogger::log(
                $document,
                $fromStatus,
                $toStatus,
                'Document submitted for approval',
                $creator->id
            );

            return $approvalRequest;
        });
    }

    /**
     * Approve a pending approval step in a locked database transaction.
     */
    public function approve(ApprovalStep $step, User $user, ?string $comment = null): void
    {
        DB::transaction(function () use ($step, $user, $comment) {
            // Lock the parent approval request row for update
            $request = ApprovalRequest::withoutGlobalScopes()
                ->where('id', $step->approval_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            $document = $request->document()->withoutGlobalScopes()->firstOrFail();

            // Guard 1: Company isolation check
            if ($user->company_id !== $request->company_id) {
                throw new RuntimeException('Forbidden. Cannot approve documents belonging to another company.');
            }

            // Guard 2: Sequential execution check (only the lowest PENDING step can be acted on)
            $lowestPendingStep = $request->steps()
                ->where('status', ApprovalStepStatus::PENDING->value)
                ->orderBy('sequence')
                ->first();

            if (! $lowestPendingStep || $lowestPendingStep->id !== $step->id) {
                throw new RuntimeException("Sequential approval violation. Step sequence {$step->sequence} cannot be approved before prior pending steps.");
            }

            // Guard 3: Self-approval guard (creator cannot approve their own document)
            $creatorId = $document->requester_id ?? $document->buyer_id ?? null;
            if ($creatorId && (int) $user->id === (int) $creatorId) {
                throw new RuntimeException('Self-approval blocked. Document creator cannot approve their own document.');
            }

            // Guard 4: User must hold the required role for this step
            if (! $user->hasRole($step->role->name)) {
                throw new RuntimeException("Unauthorized. You do not possess the required '{$step->role->name}' role for this step.");
            }

            // Mark step as APPROVED
            $step->status = ApprovalStepStatus::APPROVED;
            $step->acted_by = $user->id;
            $step->comment = $comment;
            $step->acted_at = now();
            $step->save();

            // Check if there are more pending steps
            $remainingPendingSteps = $request->steps()
                ->where('status', ApprovalStepStatus::PENDING->value)
                ->orderBy('sequence')
                ->get();

            if ($remainingPendingSteps->isEmpty()) {
                // Last step completed -> Full approval achieved
                $request->status = 'APPROVED';
                $request->save();

                $oldDocStatus = is_object($document->status) ? $document->status->value : (string) $document->status;
                if ($document instanceof PurchaseRequisition) {
                    $document->status = PrStatus::APPROVED;
                } elseif ($document instanceof PurchaseOrder) {
                    $document->status = PoStatus::APPROVED;
                } else {
                    $document->status = 'APPROVED';
                }
                $document->save();

                AuditLogger::log(
                    $document,
                    $oldDocStatus,
                    'APPROVED',
                    $comment ?? 'Full approval chain completed',
                    $user->id
                );

                // Fire events
                event(new ApprovalCompleted($document));
                if ($document instanceof PurchaseOrder) {
                    // Dispatch PurchaseOrderApproved event (no listeners for now; budget commitment comes later)
                    event(new PurchaseOrderApproved($document));
                }
            } else {
                // Move current_step to the next pending step sequence
                $nextStep = $remainingPendingSteps->first();
                $request->current_step = $nextStep->sequence;
                $request->save();

                AuditLogger::log(
                    $document,
                    'SUBMITTED',
                    'SUBMITTED',
                    "Step {$step->sequence} ({$step->role->name}) approved by {$user->name}. Advanced to Step {$nextStep->sequence} ({$nextStep->role->name}).",
                    $user->id
                );
            }
        });
    }

    /**
     * Reject a pending approval step (comment is mandatory).
     */
    public function reject(ApprovalStep $step, User $user, string $comment): void
    {
        if (trim($comment) === '') {
            throw new InvalidArgumentException('A comment is mandatory when rejecting an approval step.');
        }

        DB::transaction(function () use ($step, $user, $comment) {
            $request = ApprovalRequest::withoutGlobalScopes()
                ->where('id', $step->approval_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            $document = $request->document()->withoutGlobalScopes()->firstOrFail();

            // Guard 1: Company isolation check
            if ($user->company_id !== $request->company_id) {
                throw new RuntimeException('Forbidden. Cannot reject documents belonging to another company.');
            }

            // Guard 2: Sequential execution check
            $lowestPendingStep = $request->steps()
                ->where('status', ApprovalStepStatus::PENDING->value)
                ->orderBy('sequence')
                ->first();

            if (! $lowestPendingStep || $lowestPendingStep->id !== $step->id) {
                throw new RuntimeException("Sequential approval violation. Step sequence {$step->sequence} cannot be rejected out of order.");
            }

            // Guard 3: Self-approval guard (creator cannot reject their own document)
            $creatorId = $document->requester_id ?? $document->buyer_id ?? null;
            if ($creatorId && (int) $user->id === (int) $creatorId) {
                throw new RuntimeException('Self-approval blocked. Document creator cannot act on their own document.');
            }

            // Guard 4: User role check
            if (! $user->hasRole($step->role->name)) {
                throw new RuntimeException("Unauthorized. You do not possess the required '{$step->role->name}' role for this step.");
            }
            $creatorId = $document->requester_id ?? $document->buyer_id ?? null;
            if ($creatorId && (int) $user->id === (int) $creatorId) {
                throw new RuntimeException('Self-rejection blocked. Document creator cannot act on their own document step.');
            }

            // Mark step as REJECTED
            $step->status = ApprovalStepStatus::REJECTED;
            $step->acted_by = $user->id;
            $step->comment = $comment;
            $step->acted_at = now();
            $step->save();

            // Mark request as REJECTED
            $request->status = 'REJECTED';
            $request->save();

            // Mark document as REJECTED (returns back to requester/buyer with comment)
            $oldDocStatus = is_object($document->status) ? $document->status->value : (string) $document->status;
            if ($document instanceof PurchaseRequisition) {
                $document->status = PrStatus::REJECTED;
                $targetStatus = 'REJECTED';
            } elseif ($document instanceof PurchaseOrder) {
                // PO rejection: returns back to DRAFT with the rejection comment visible to the Procurement Officer
                $document->status = PoStatus::DRAFT;
                $targetStatus = 'DRAFT';
            } else {
                $document->status = 'REJECTED';
                $targetStatus = 'REJECTED';
            }
            $document->save();

            AuditLogger::log(
                $document,
                $oldDocStatus,
                $targetStatus,
                "Rejected by {$user->name} ({$step->role->name}): {$comment}",
                $user->id
            );
        });
    }
}
