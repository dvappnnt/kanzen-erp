<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseProductSerial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_product_id',
        'serial_number',
        'batch_number',
        'manufactured_at',
        'expired_at',
        'is_sold'
    ];

    public function warehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class);
    }
}
