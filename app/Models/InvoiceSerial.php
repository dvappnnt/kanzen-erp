<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceSerial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_detail_id',
        'warehouse_product_id',
        'warehouse_product_serial_id',
        'replacement_warehouse_product_serial_id',
        'warranty_expiration',
        'is_expired',
        'is_replaced',
        'notes',
    ];

    protected $casts = [
        'warranty_expiration' => 'date',
        'is_expired' => 'boolean',
        'is_replaced' => 'boolean',
    ];

    public function invoiceDetail()
    {
        return $this->belongsTo(InvoiceDetail::class);
    }

    public function warehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class);
    }

    public function warehouseProductSerial()
    {
        return $this->belongsTo(WarehouseProductSerial::class);
    }

    public function replacementWarehouseProductSerial()
    {
        return $this->belongsTo(WarehouseProductSerial::class, 'replacement_warehouse_product_serial_id');
    }
}
