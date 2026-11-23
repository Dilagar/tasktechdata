<?php

namespace App\Models;

use App\Enums\ApprovalStepStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'sequence',
        'role_id',
        'status',
        'acted_by',
        'comment',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => ApprovalStepStatus::class,
            'acted_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
