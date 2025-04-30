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
        'has_serials'
    ];

    protected $casts = [
        'expected_qty' => 'decimal:2',
        'received_qty' => 'decimal:2'
    ];

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
} 