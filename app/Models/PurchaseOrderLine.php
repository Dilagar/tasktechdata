<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'line_number',
        'item_id',
        'description',
        'quantity',
        'uom_code',
        'base_quantity',
        'unit_price',
        'unit_price_base',
        'taxable_value',
        'gst_rate',
        'cgst',
        'sgst',
        'igst',
        'line_total',
        'received_quantity',
        'billed_quantity',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:3',
            'base_quantity' => 'decimal:3',
            'received_quantity' => 'decimal:3',
            'billed_quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'unit_price_base' => 'decimal:4',
            'taxable_value' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'igst' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_code', 'code');
    }

    public function poPrLines()
    {
        return $this->hasMany(PoPrLine::class);
    }

    public function goodsReceiptLines()
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function invoiceLines()
    {
        return $this->hasMany(VendorInvoiceLine::class);
    }
}
