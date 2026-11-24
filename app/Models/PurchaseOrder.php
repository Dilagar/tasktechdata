<?php

namespace App\Models;

use App\Enums\PoStatus;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'po_number',
        'po_date',
        'vendor_id',
        'cost_centre_id',
        'buyer_id',
        'status',
        'subtotal',
        'total_cgst',
        'total_sgst',
        'total_igst',
        'tax_total',
        'total_amount',
        'grand_total',
        'committed_amount',
        'payment_terms_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PoStatus::class,
            'po_date' => 'date',
            'subtotal' => 'decimal:2',
            'total_cgst' => 'decimal:2',
            'total_sgst' => 'decimal:2',
            'total_igst' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'committed_amount' => 'decimal:2',
            'payment_terms_days' => 'integer',
        ];
    }

    public function costCentre()
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_number');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function invoices()
    {
        return $this->hasMany(VendorInvoice::class);
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
