<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseStockTransferSerial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_stock_transfer_id',
        'warehouse_stock_transfer_detail_id',
        'serial_number',
        'batch_number',
        'manufactured_at',
        'expired_at',
        'is_sold',
        'is_received',
        'received_at'
    ];

    protected $casts = [
        'is_sold' => 'boolean',
        'is_received' => 'boolean',
        'manufactured_at' => 'datetime',
        'expired_at' => 'datetime',
        'received_at' => 'datetime'
    ];

    public function transfer()
    {
        return $this->belongsTo(WarehouseStockTransfer::class, 'warehouse_stock_transfer_id');
    }

    public function detail()
    {
        return $this->belongsTo(WarehouseStockTransferDetail::class, 'warehouse_stock_transfer_detail_id');
    }
}