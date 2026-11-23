<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'document_type',
        'min_amount',
        'max_amount',
        'category',
        'rule_type',
        'insert_after_role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function steps()
    {
        return $this->hasMany(ApprovalRuleStep::class)->orderBy('sequence');
    }
}
