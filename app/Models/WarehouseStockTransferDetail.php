<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseStockTransferDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_stock_transfer_id',
        'origin_warehouse_product_id',
        'destination_warehouse_product_id',
        'expected_qty',
        'transferred_qty',
    ];

    protected $casts = [
        'expected_qty' => 'integer',
        'transferred_qty' => 'integer',
    ];

    public function warehouseStockTransfer()
    {
        return $this->belongsTo(WarehouseStockTransfer::class);
    }

    public function originWarehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class, 'origin_warehouse_product_id');
    }

    public function destinationWarehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class, 'destination_warehouse_product_id');
    }
}
