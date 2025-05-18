<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseStockAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'warehouse_product_id',
        'system_quantity',
        'actual_quantity',
        'adjustment',
        'reason',
        'remarks',
        'adjusted_at',
        'adjusted_by_user_id',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class);
    }
    
    public function adjustedByUser()
    {
        return $this->belongsTo(User::class, 'adjusted_by_user_id');
    }
}
