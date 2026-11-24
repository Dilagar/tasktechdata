<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStepStatus;
use App\Http\Requests\ApproveStepRequest;
use App\Http\Requests\RejectStepRequest;
use App\Models\ApprovalStep;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {
    }

    /**
     * Show "Pending My Approval" page:
     * Steps that are PENDING, are the current step in their request,
     * match one of my roles, same company, and document NOT created by me.
     */
    public function pending(): View
    {
        $user = Auth::user();
        $myRoleIds = $user->roles()->pluck('roles.id')->toArray();

        $pendingSteps = ApprovalStep::where('status', ApprovalStepStatus::PENDING->value)
            ->whereIn('role_id', $myRoleIds)
            ->whereHas('request', function ($q) use ($user) {
                $q->where('company_id', $user->company_id)
                    ->where('status', 'PENDING')
                    ->whereColumn('approval_steps.sequence', 'approval_requests.current_step');
            })
            ->with(['request.document', 'role'])
            ->get()
            ->filter(function ($step) use ($user) {
                // Ensure document was NOT created by current user (Self-approval guard)
                $doc = $step->request->document;
                if (! $doc) {
                    return false;
                }
                $creatorId = $doc->requester_id ?? $doc->buyer_id ?? null;
                return (int) $creatorId !== (int) $user->id;
            });

        return view('approvals.pending', compact('pendingSteps'));
    }

    /**
     * Approve a step.
     */
    public function approve(ApproveStepRequest $request, ApprovalStep $step): RedirectResponse
    {
        $this->approvalService->approve($step, Auth::user(), $request->validated('comment'));

        return back()->with('success', "Step sequence {$step->sequence} ({$step->role->name}) approved successfully.");
    }

    /**
     * Reject a step (comment is mandatory).
     */
    public function reject(RejectStepRequest $request, ApprovalStep $step): RedirectResponse
    {
        $this->approvalService->reject($step, Auth::user(), $request->validated('comment'));

        return back()->with('success', "Document rejected and returned to requester.");
    }
}
