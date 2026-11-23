<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_line_id',
        'line_number',
        'item_id',
        'uom_code',
        'received_qty',
        'accepted_qty',
        'rejected_qty',
        'rejection_reason',
        'base_received_qty',
        'base_accepted_qty',
        'base_rejected_qty',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'received_qty' => 'decimal:3',
            'accepted_qty' => 'decimal:3',
            'rejected_qty' => 'decimal:3',
            'base_received_qty' => 'decimal:3',
            'base_accepted_qty' => 'decimal:3',
            'base_rejected_qty' => 'decimal:3',
        ];
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_code', 'code');
    }

    public function invoiceLines()
    {
        return $this->hasMany(VendorInvoiceLine::class);
    }
}
