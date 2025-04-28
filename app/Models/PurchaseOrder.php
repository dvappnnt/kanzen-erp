<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'company_id',
        'warehouse_id',
        'supplier_id',
        'purchase_requisition_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'delivery_date',
        'payment_terms',
        'shipping_terms',
        'notes',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    protected static function booted()
    {
        static::creating(function ($po) {
            if (empty($po->number)) {
                $company = \App\Models\Company::find($po->company_id);

                if ($company) {
                    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));
                    $count = self::where('company_id', $po->company_id)->withTrashed()->count() + 1;
                    $po->number = sprintf('%s-PO-%06d', $prefix, $count);
                } else {
                    $po->number = 'UNK-PO-' . sprintf('%06d', rand(1, 999999));
                }
            }
        });
    }
} 