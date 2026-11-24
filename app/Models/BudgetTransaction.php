<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class BudgetTransaction extends Model
{
    use HasFactory, BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'budget_id',
        'purchase_order_id',
        'type',
        'amount',
        'balance_after_committed',
        'balance_after_spent',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after_committed' => 'decimal:2',
            'balance_after_spent' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Enforce append-only semantics: Budget transactions cannot be updated or deleted.
     */
    public static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new RuntimeException('Budget transaction logs are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Budget transaction logs are immutable and cannot be deleted.');
        });
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
