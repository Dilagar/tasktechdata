<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class AuditLog extends Model
{
    use HasFactory, BelongsToCompany;

    // Table has no updated_at column
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'user_id',
        'from_status',
        'to_status',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Audit log is strictly append-only.
     * Throw exception on any attempt to update or delete.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Audit logs are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Audit logs are append-only and cannot be deleted.');
        });
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
