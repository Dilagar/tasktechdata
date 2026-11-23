<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payment_number',
        'vendor_id',
        'payment_date',
        'total_amount',
        'payment_mode',
        'reference_number',
        'status',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
