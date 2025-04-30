<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'supplier_product_detail_id',
        'qty',
        'price',
        'last_cost',
        'average_cost',
        'has_serials',
        'critical_level_qty'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'last_cost' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'has_serials' => 'boolean',
        'critical_level_qty' => 'integer'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplierProductDetail()
    {
        return $this->belongsTo(SupplierProductDetail::class);
    }

    public function serials()
    {
        return $this->hasMany(WarehouseProductSerial::class);
    }

    public function transfers()
    {
        return $this->hasMany(WarehouseTransfer::class, 'destination_warehouse_id', 'warehouse_id');
    }
}
