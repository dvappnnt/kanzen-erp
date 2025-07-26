<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InternalTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'material_request_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'remarks',
    ];

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
    public function items()
{
    return $this->hasMany(InternalTransferItem::class);
}
}
