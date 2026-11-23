<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoPrLine extends Model
{
    use HasFactory;

    protected $table = 'po_pr_line';

    protected $fillable = [
        'purchase_order_line_id',
        'purchase_requisition_line_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function purchaseRequisitionLine()
    {
        return $this->belongsTo(PurchaseRequisitionLine::class);
    }
}
