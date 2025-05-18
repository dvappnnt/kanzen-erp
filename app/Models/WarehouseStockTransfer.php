<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseStockTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'origin_warehouse_id',
        'origin_warehouse_product_id',
        'destination_warehouse_id',
        'destination_warehouse_product_id',
        'quantity',
        'remarks',
        'created_by_user_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function originWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function originWarehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class, 'origin_warehouse_product_id');
    }

    public function destinationWarehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class, 'destination_warehouse_product_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
