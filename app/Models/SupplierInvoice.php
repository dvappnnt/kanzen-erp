<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'company_account_id',
        'company_id',
        'goods_receipt_id',
        'supplier_id',
        'purchase_order_id',
        'invoice_date',
        'due_date',
        'tax_rate',
        'tax_amount',
        'shipping_cost',
        'subtotal',
        'total_amount',
        'status',
        'remarks',
    ];

    protected $with = ['supplier', 'purchaseOrder', 'details'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function details()
    {
        return $this->hasMany(SupplierInvoiceDetail::class);
    }

    public function companyAccount()
    {
        return $this->belongsTo(CompanyAccount::class);
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
