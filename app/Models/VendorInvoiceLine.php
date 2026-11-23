<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_invoice_id',
        'purchase_order_line_id',
        'goods_receipt_line_id',
        'line_number',
        'item_id',
        'description',
        'quantity',
        'uom_code',
        'base_quantity',
        'unit_price',
        'taxable_value',
        'gst_rate',
        'cgst',
        'sgst',
        'igst',
        'line_total',
        'match_status',
        'hold_reason',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:3',
            'base_quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'taxable_value' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'igst' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function goodsReceiptLine()
    {
        return $this->belongsTo(GoodsReceiptLine::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_code', 'code');
    }
}
