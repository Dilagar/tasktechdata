<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'document_type',
        'document_id',
        'status',
        'current_step',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'current_step' => 'integer',
        ];
    }

    public function document()
    {
        return $this->morphTo(__FUNCTION__, 'document_type', 'document_id');
    }

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('sequence');
    }
}
