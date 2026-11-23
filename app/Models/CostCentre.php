<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCentre extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
    ];

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function purchaseRequisitions()
    {
        return $this->hasMany(PurchaseRequisition::class);
    }
}
