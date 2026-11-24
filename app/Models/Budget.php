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

    public function transactions()
    {
        return $this->hasMany(BudgetTransaction::class)->orderBy('id');
    }

    public function availableAmount(): string
    {
        $used = bcadd((string) $this->committed, (string) $this->spent, 2);
        return bcsub((string) $this->amount, $used, 2);
    }
}
