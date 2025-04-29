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
        'total_amount',
        'created_by_user_id',
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

    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected static function booted()
    {
        static::creating(function ($modelData) {
            $modelData->created_by_user_id = auth()->user()->id;

            if (empty($modelData->number)) {
                $company = \App\Models\Company::find($modelData->company_id);

                if ($company) {
                    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));
                    $count = self::where('company_id', $modelData->company_id)->withTrashed()->count() + 1;
                    $modelData->number = sprintf('%s-PO-%06d', $prefix, $count);
                } else {
                    $modelData->number = 'UNK-PO-' . sprintf('%06d', rand(1, 999999));
                }
            }
        });
    }
} 