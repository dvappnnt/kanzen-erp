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

    protected $with = ['supplierProduct'];

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function supplierProduct()
    {
        return $this->belongsTo(SupplierProduct::class)->with(['product']);
    }
}
