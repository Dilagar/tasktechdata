<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'cost_centre_id',
        'financial_year',
        'amount',
        'committed',
        'spent',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'committed' => 'decimal:2',
            'spent' => 'decimal:2',
        ];
    }

    public function costCentre()
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function availableAmount(): float
    {
        return (float) ($this->amount - ($this->committed + $this->spent));
    }
}
