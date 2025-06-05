<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoice extends Model
{
    use HasFactory, SoftDeletes;

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

    public function payments()
    {
        return $this->hasMany(SupplierInvoicePayment::class);
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $company = \App\Models\Company::find($invoice->company_id);

                if ($company) {
                    $basePrefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));

                    $matchingCompanies = \App\Models\Company::all()->filter(function ($comp) use ($basePrefix) {
                        return strtoupper(substr(preg_replace('/\s+/', '', $comp->name), 0, 3)) === $basePrefix;
                    })->values();

                    $finalPrefix = $matchingCompanies->count() === 1
                        ? $basePrefix
                        : $basePrefix . ($matchingCompanies->search(fn($c) => $c->id === $company->id) + 1);

                    $count = self::where('company_id', $company->id)->withTrashed()->count() + 1;

                    $invoice->invoice_number = sprintf('%s-SUP-INV-%06d', $finalPrefix, $count);
                } else {
                    $invoice->invoice_number = 'UNK-SUP-INV-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                }
            }
        });

        static::deleting(function ($model) {
            $model->payments()->delete();
        });
    }
}
