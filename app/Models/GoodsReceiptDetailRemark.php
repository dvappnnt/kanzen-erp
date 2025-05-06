<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceiptDetailRemark extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'goods_receipt_detail_id',
        'goods_receipt_serial_id',
        'user_id',
        'status',
        'remarks',
    ];

    public function goodsReceiptDetail()
    {
        return $this->belongsTo(GoodsReceiptDetail::class);
    }

    public function goodsReceiptSerial()
    {
        return $this->belongsTo(GoodsReceiptSerial::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
