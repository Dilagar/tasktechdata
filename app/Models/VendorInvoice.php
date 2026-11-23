<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorInvoice extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'purchase_order_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_total',
        'total_amount',
        'paid_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function lines()
    {
        return $this->hasMany(VendorInvoiceLine::class)->orderBy('line_number');
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
