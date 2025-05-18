<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Account;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'company_id',
        'reference_number',
        'category_id',
        'payee',
        'payment_method',
        'amount',
        'currency',
        'description',
        'expense_date',
        'receipt_attachment',
        'created_by_user_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    protected static function booted()
    {
        static::creating(function ($expense) {
            // Auto-generate reference number if not set
            if (empty($expense->reference_number)) {
                $count = self::withTrashed()->count() + 1;
                $expense->reference_number = sprintf('EXP-%06d', $count);
            }
        });

        static::created(function ($expense) {
            $expense->registerJournalAfterCommit();
        });
    }

    public function registerJournalAfterCommit()
    {
        \DB::afterCommit(function () {
            $expense = self::find($this->id)->load(['category', 'company']);

            // Skip if already journaled
            if (\App\Models\JournalEntry::where('reference_number', $expense->reference_number)->exists()) {
                return;
            }

            // Get debit account from category
            $debitAccountId = $expense->category?->default_account_id;
            if (! $debitAccountId) return;

            // Get payment method account from payment_methods table
            $paymentMethod = \App\Models\PaymentMethod::where('code', $expense->payment_method)->first();
            $creditAccount = $paymentMethod?->account;

            if (! $creditAccount) {
                \Log::warning('Expense journal skipped - Missing credit account for payment method', [
                    'payment_method' => $expense->payment_method,
                    'expense_id' => $expense->id,
                ]);
                return;
            }

            // Create journal entry
            $entry = \App\Models\JournalEntry::create([
                'company_id' => $expense->company_id,
                'reference_number' => $expense->reference_number,
                'reference_date' => $expense->expense_date ?? now(),
                'remarks' => 'Expense: ' . ($expense->description ?? 'No description'),
                'created_by_user_id' => auth()->id(),
            ]);

            // 1. Debit: Expense Category Account
            \App\Models\JournalEntryDetail::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debitAccountId,
                'name' => 'Expense - ' . ($expense->description ?? 'Unknown'),
                'debit' => $expense->amount,
                'credit' => 0,
            ]);

            // 2. Credit: Payment Account (Cash, Bank, etc.)
            \App\Models\JournalEntryDetail::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAccount->id,
                'name' => 'Paid via ' . $paymentMethod->name,
                'debit' => 0,
                'credit' => $expense->amount,
            ]);
        });
    }
}
