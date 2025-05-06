<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_number',
        'voucher_date',
        'supplier_id',
        'invoice_id',
        'amount',
        'payment_method',
        'cheque_number',
        'bank_name',
        'cheque_date',
        'remarks',
        'status',
        'created_by_user_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'invoice_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
