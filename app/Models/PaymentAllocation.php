<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'vendor_invoice_id',
        'amount',
        'tds_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tds_amount' => 'decimal:2',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }
}
