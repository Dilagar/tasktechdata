<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an append-only audit log entry for status transitions.
     *
     * @param Model $auditable Target document (PurchaseRequisition, PurchaseOrder, etc.)
     * @param string|null $fromStatus Previous status
     * @param string $toStatus New status
     * @param string|null $comment Optional reason / remarks
     * @param int|null $userId User responsible (defaults to Auth::id())
     * @return AuditLog
     */
    public static function log(
        Model $auditable,
        ?string $fromStatus,
        string $toStatus,
        ?string $comment = null,
        ?int $userId = null
    ): AuditLog {
        $actorId = $userId ?? Auth::id();
        $companyId = $auditable->company_id ?? (Auth::check() ? Auth::user()->company_id : null);

        return AuditLog::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'user_id' => $actorId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }
}
