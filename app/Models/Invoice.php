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
        'warehouse_id',
        'number',
        'type',
        'payment_method',
        'invoice_date',
        'due_date',
        'payment_date',
        'discount_rate',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'shipping_cost',
        'subtotal',
        'total_amount',
        'currency',
        'status',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->number;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
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
                    $invoice->number = sprintf('%s-SI-%06d', $prefix, $count);
                } else {
                    // In case no company found (should not happen)
                    $invoice->number = 'UNK-SI-' . sprintf('%06d', rand(1, 999999));
                }
            }
        });

        static::created(function ($invoice) {
            $invoice->registerJournalAfterCommit();
        });

        static::updated(function ($invoice) {
            if ($invoice->status === 'fully-paid') {
                $invoice->registerJournalAfterCommit();
            }
        });
    }

    public function registerJournalAfterCommit()
    {
        \DB::afterCommit(function () {
            $invoice = self::find($this->id)->load(['details.warehouseProduct', 'paymentMethodDetails']);

            if ($invoice->status !== 'fully-paid') return;
            if (\App\Models\JournalEntry::where('reference_number', $invoice->number)->exists()) return;

            $entry = \App\Models\JournalEntry::create([
                'company_id' => $invoice->company_id,
                'reference_number' => $invoice->number,
                'reference_date' => $invoice->invoice_date,
                'remarks' => 'POS Invoice Payment: ' . $invoice->number,
                'created_by_user_id' => $invoice->created_by_user_id,
            ]);

            $revenueAccount   = \App\Models\Account::where('name', 'Sales Revenue')->firstOrFail();
            $taxAccount       = \App\Models\Account::where('name', 'Taxes Payable')->first();
            $cogsAccount      = \App\Models\Account::where('name', 'Cost of Goods Sold (COGS)')->first();
            $inventoryAccount = \App\Models\Account::where('name', 'Inventory')->first();

            // 🔁 Handle each payment method
            foreach ($invoice->paymentMethodDetails as $methodDetail) {
                $paymentMethodCode = $methodDetail->payment_method;
                $amount = $methodDetail->amount ?? 0;

                $paymentMethod = \App\Models\PaymentMethod::where('code', $paymentMethodCode)->first();
                $paymentAccount = $paymentMethod?->account;

                if (!$paymentAccount) {
                    \Log::warning('Missing account for invoice payment method:', [
                        'payment_method' => $paymentMethodCode,
                        'invoice_id' => $invoice->id,
                    ]);
                    continue;
                }

                // Debit: Cash / Bank / etc.
                \App\Models\JournalEntryDetail::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $paymentAccount->id,
                    'name' => 'Received via ' . $paymentMethod->name . ' for invoice ' . $invoice->number,
                    'debit' => $amount,
                    'credit' => 0,
                ]);
            }

            // Credit: Revenue
            \App\Models\JournalEntryDetail::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'name' => 'Sales income for invoice ' . $invoice->number,
                'debit' => 0,
                'credit' => $invoice->subtotal,
            ]);

            // Credit: VAT
            if ($invoice->tax_amount > 0 && $taxAccount) {
                \App\Models\JournalEntryDetail::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $taxAccount->id,
                    'name' => 'VAT for invoice ' . $invoice->number,
                    'debit' => 0,
                    'credit' => $invoice->tax_amount,
                ]);
            }

            // Inventory and COGS
            if ($cogsAccount && $inventoryAccount) {
                $totalCOGS = 0;

                foreach ($invoice->details as $detail) {
                    $cost = $detail->warehouseProduct?->last_cost ?? 0;
                    $totalCOGS += $cost * $detail->qty;
                }

                \Log::info('COGS Debug', [
                    'invoice_id' => $invoice->id,
                    'total_cogs' => $totalCOGS,
                    'details' => $invoice->details->map(function ($d) {
                        return [
                            'warehouse_product_id' => $d->warehouse_product_id,
                            'qty' => $d->qty,
                            'last_cost' => optional($d->warehouseProduct)->last_cost,
                        ];
                    })->toArray()
                ]);

                if ($totalCOGS > 0) {
                    \App\Models\JournalEntryDetail::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $cogsAccount->id,
                        'name' => 'COGS for invoice ' . $invoice->number,
                        'debit' => $totalCOGS,
                        'credit' => 0,
                    ]);

                    \App\Models\JournalEntryDetail::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $inventoryAccount->id,
                        'name' => 'Inventory reduction for invoice ' . $invoice->number,
                        'debit' => 0,
                        'credit' => $totalCOGS,
                    ]);
                }
            }
        });
    }

    public function paymentMethodDetails()
    {
        return $this->hasMany(InvoicePaymentMethodDetail::class);
    }

    public function invoiceSerials()
    {
        return $this->hasMany(InvoiceSerial::class);
    }
}
