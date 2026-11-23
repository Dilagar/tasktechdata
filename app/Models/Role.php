<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function approvalRuleSteps()
    {
        return $this->hasMany(ApprovalRuleStep::class);
    }
}
