<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'number',
        'type',
        'invoice_date',
        'subtotal',
        'tax',
        'total',
        'currency',
        'status',
        'notes',
        'created_by_user_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            // Only generate if number is still empty
            if (empty($invoice->number)) {
                // Fetch company name
                $company = \App\Models\Company::find($invoice->company_id);

                if ($company) {
                    // Get first 3 uppercase letters of company name
                    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3)); // Removes spaces too

                    // Count existing invoices for that company
                    $count = self::where('company_id', $invoice->company_id)->withTrashed()->count() + 1;

                    // Build the reference number
                    $invoice->number = sprintf('%s-INV-%06d', $prefix, $count);
                } else {
                    // In case no company found (should not happen)
                    $invoice->number = 'UNK-INV-' . sprintf('%06d', rand(1, 999999));
                }
            }
        });
    }
}
