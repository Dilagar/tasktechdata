<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_requisition_id',
        'line_number',
        'item_id',
        'description',
        'quantity',
        'uom_code',
        'base_quantity',
        'estimated_unit_price',
        'estimated_total',
        'ordered_quantity',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:3',
            'base_quantity' => 'decimal:3',
            'ordered_quantity' => 'decimal:3',
            'estimated_unit_price' => 'decimal:2',
            'estimated_total' => 'decimal:2',
        ];
    }

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
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
}
