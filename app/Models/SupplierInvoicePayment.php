<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoicePayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_invoice_id',
        'company_account_id',
        'payment_method',
        'reference_number',
        'account_name',
        'account_number',
        'status',
        'payment_date',
        'amount',
        'file_path',
        'remarks'
    ];

    protected static function boot()
    {
        parent::boot();

        // Update supplier invoice status after payment is saved (created or updated)
        static::saved(function ($payment) {
            $payment->updateSupplierInvoiceStatus();

            // Only journal if status is approved and not already posted
            if ($payment->status === 'approved') {
                \DB::afterCommit(fn () => $payment->registerJournalEntry());
            }
        });

        // Update supplier invoice status after payment is deleted
        static::deleted(function ($payment) {
            $payment->updateSupplierInvoiceStatus();
        });

        // Update supplier invoice status after payment is restored from soft delete
        static::restored(function ($payment) {
            $payment->updateSupplierInvoiceStatus();
        });
    }

    public function updateSupplierInvoiceStatus()
    {
        $supplierInvoice = $this->supplierInvoice;

        if (!$supplierInvoice || $supplierInvoice->status === 'cancelled') {
            return;
        }

        // Get total amount paid from approved payments only
        $totalPaid = $supplierInvoice->payments()
            ->where('status', 'approved')
            ->withoutTrashed()
            ->sum('amount');

        // Calculate new status based on total paid amount
        $newStatus = 'unpaid';

        if ($totalPaid >= $supplierInvoice->total_amount) {
            $newStatus = 'fully-paid';
        } elseif ($totalPaid > 0) {
            $newStatus = 'partially-paid';
        }

        // Update the supplier invoice status if it's different
        if ($supplierInvoice->status !== $newStatus) {
            $supplierInvoice->update(['status' => $newStatus]);
        }
    }

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function companyAccount()
    {
        return $this->belongsTo(CompanyAccount::class);
    }

    public function registerJournalEntry()
    {
        if (empty($this->reference_number)) {
            $this->reference_number = 'SIP-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
            $this->saveQuietly();
        }

        $alreadyJournaled = JournalEntry::where('reference_number', $this->reference_number)
            ->where('remarks', 'LIKE', '%Payment for Supplier Invoice%')
            ->exists();

        if ($alreadyJournaled) return;

        $invoice = $this->supplierInvoice;
        if (!$invoice) return;

        // Resolve Accounts Payable account
        $apAccount = Account::where('name', 'Accounts Payable')->first();
        if (!$apAccount) return;

        // Use PaymentMethod model instead of hardcoded map
        $paymentMethod = \App\Models\PaymentMethod::where('code', $this->payment_method)->first();
        if (!$paymentMethod || !$paymentMethod->account) {
            \Log::warning('Missing account for payment method:', [
                'code' => $this->payment_method,
                'supplier_invoice_payment_id' => $this->id,
            ]);
            return;
        }

        $paymentAccount = $paymentMethod->account;

        // Create Journal Entry
        $entry = JournalEntry::create([
            'company_id' => $invoice->company_id,
            'reference_number' => $this->reference_number,
            'reference_date' => $this->payment_date,
            'remarks' => 'Payment for Supplier Invoice ' . $invoice->reference_number,
            'created_by_user_id' => auth()?->id() ?? $this->supplierInvoice?->created_by_user_id,
        ]);

        // Debit: Accounts Payable
        JournalEntryDetail::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $apAccount->id,
            'name' => 'Payment for Supplier Invoice ' . $invoice->reference_number,
            'debit' => $this->amount,
            'credit' => 0,
        ]);

        // Credit: Resolved payment account
        JournalEntryDetail::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $paymentAccount->id,
            'name' => 'Paid via ' . $paymentMethod->name,
            'debit' => 0,
            'credit' => $this->amount,
        ]);
    }
}
