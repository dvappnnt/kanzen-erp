<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseStockTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'transfer_date',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'status',
        'remarks',
        'created_by_user_id'
    ];

    public function originWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function details()
    {
        return $this->hasMany(WarehouseStockTransferDetail::class);
    }
}
