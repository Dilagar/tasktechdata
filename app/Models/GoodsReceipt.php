<?php

namespace App\Models;

use App\Enums\GrnStatus;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'grn_number',
        'purchase_order_id',
        'received_by',
        'status',
        'receipt_date',
        'delivery_challan_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => GrnStatus::class,
            'receipt_date' => 'date',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function lines()
    {
        return $this->hasMany(GoodsReceiptLine::class)->orderBy('line_number');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
