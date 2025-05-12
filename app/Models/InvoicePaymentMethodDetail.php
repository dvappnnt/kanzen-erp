<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePaymentMethodDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_account_id',
        'invoice_id',
        'bank_id',
        'payment_method',
        'reference_number',
        'account_name',
        'account_number',
        'status',
        'payment_date',
        'amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function companyAccount()
    {
        return $this->belongsTo(CompanyAccount::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
