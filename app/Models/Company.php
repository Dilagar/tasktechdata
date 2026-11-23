<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'state',
        'gstin',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function costCentres()
    {
        return $this->hasMany(CostCentre::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }

    public function purchaseRequisitions()
    {
        return $this->hasMany(PurchaseRequisition::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function vendorInvoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function approvalRules()
    {
        return $this->hasMany(ApprovalRule::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
