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
    ];

    public function warehouseStockTransfer()
    {
        return $this->belongsTo(WarehouseStockTransfer::class);
    }

    public function warehouseStockTransferDetail()
    {
        return $this->belongsTo(WarehouseStockTransferDetail::class);
    }
}