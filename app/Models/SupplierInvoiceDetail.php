<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierInvoiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_invoice_id',
        'supplier_product_id',
        'quantity',
        'unit_price',
        'total',
    ];

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(SupplierProduct::class, 'supplier_product_id');
    }
}
