<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRuleStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_rule_id',
        'sequence',
        'role_id',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
        ];
    }

    public function rule()
    {
        return $this->belongsTo(ApprovalRule::class, 'approval_rule_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
