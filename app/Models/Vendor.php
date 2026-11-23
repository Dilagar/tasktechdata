<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'state',
        'gstin',
        'payment_terms_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
