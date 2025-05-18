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

    protected static function booted()
    {
        // Calculate and update total before saving
        static::saving(function ($detail) {
            // Calculate total if not set
            if (empty($detail->total)) {
                $qty = $detail->qty ?? 0;
                $price = $detail->price ?? 0;
                $discount = $detail->discount ?? 0;
                
                $total = ($qty * $price) * (1 - ($discount / 100));
                $detail->total = round($total, 2);
            }
        });

        // After creating/updating/deleting a detail, update the purchase order's total
        static::saved(function ($detail) {
            static::updatePurchaseOrderTotal($detail->purchase_order_id);
        });

        static::deleted(function ($detail) {
            static::updatePurchaseOrderTotal($detail->purchase_order_id);
        });
    }

    protected static function updatePurchaseOrderTotal($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
        if ($purchaseOrder) {
            $subtotal = static::where('purchase_order_id', $purchaseOrderId)
                ->whereNull('deleted_at')
                ->sum('total');

            // Calculate tax amount based on subtotal and tax rate
            $tax_amount = ($subtotal * $purchaseOrder->tax_rate) / 100;
            
            // Calculate final total
            $total_amount = $subtotal + $tax_amount + ($purchaseOrder->shipping_cost ?? 0);

            $purchaseOrder->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($tax_amount, 2),
                'total_amount' => round($total_amount, 2)
            ]);
        }
    }
}
