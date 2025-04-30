<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'supplier_product_detail_id',
        'qty',
        'free_qty',
        'discount',
        'price',
        'total',
        'notes'
    ];

    protected $casts = [
        'qty' => 'integer',
        'free_qty' => 'integer',
        'discount' => 'decimal:2',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplierProductDetail()
    {
        return $this->belongsTo(SupplierProductDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseRequisitionItem()
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
    }
}
