<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceiptSerial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'goods_receipt_detail_id',
        'serial_number',
        'batch_number',
        'manufactured_at',
        'expired_at'
    ];

    protected $casts = [
        'manufactured_at' => 'datetime',
        'expired_at' => 'datetime'
    ];

    public function goodsReceiptDetail()
    {
        return $this->belongsTo(GoodsReceiptDetail::class);
    }
} 