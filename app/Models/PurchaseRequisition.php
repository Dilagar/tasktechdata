<?php

namespace App\Models;

use App\Enums\PrStatus;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisition extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'pr_number',
        'requester_id',
        'cost_centre_id',
        'status',
        'total_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PrStatus::class,
            'total_amount' => 'decimal:2',
        ];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function costCentre()
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function lines()
    {
        return $this->hasMany(PurchaseRequisitionLine::class)->orderBy('line_number');
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'document');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
