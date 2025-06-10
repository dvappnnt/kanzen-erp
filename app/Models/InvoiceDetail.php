<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'warehouse_id',
        'warehouse_product_id',
        'qty',
        'price',
        'total',
        'currency',
        'notes',
        'is_pre_order',
        'is_delivered',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class);
    }

    public function invoiceSerials()
    {
        return $this->hasMany(InvoiceSerial::class);
    }
}
