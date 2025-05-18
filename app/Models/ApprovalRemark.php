<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalRemark extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'goods_receipt_id',
        'user_id',
        'status',
        'remarks'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
