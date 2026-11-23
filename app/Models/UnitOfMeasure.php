<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'code',
        'base_unit_code',
        'factor',
    ];

    protected function casts(): array
    {
        return [
            'factor' => 'decimal:6',
        ];
    }
}
