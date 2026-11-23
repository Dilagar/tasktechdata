<?php

namespace App\Models;

use App\Enums\ItemCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'base_uom_code',
        'gst_rate',
    ];

    protected function casts(): array
    {
        return [
            'category' => ItemCategory::class,
            'gst_rate' => 'decimal:2',
        ];
    }

    public function baseUom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_uom_code', 'code');
    }
}
