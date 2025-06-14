<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceiptDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_detail_id',
        'expected_qty',
        'received_qty',
        'notes',
        'has_serials',
        'is_synced'
    ];

    protected $casts = [
        'expected_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'has_serials' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        // Add observer for when serials are saved
        static::saved(function ($model) {
            if ($model->serials()->exists()) {
                $model->has_serials = true;
                $model->saveQuietly(); // Save without triggering other events
            }
        });
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderDetail()
    {
        return $this->belongsTo(PurchaseOrderDetail::class);
    }

    public function serials()
    {
        return $this->hasMany(GoodsReceiptSerial::class);
    }

    public function getHasSerialsAttribute($value)
    {
        // Return true if there are any serials associated
        if ($this->serials()->exists()) {
            return true;
        }
        
        // Return true if the product requires serials
        if ($this->purchaseOrderDetail?->supplierProductDetail?->product?->has_serials) {
            return true;
        }

        return (bool) $value;
    }
} 