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
        DB::afterCommit(function () {
            $expense = self::find($this->id)->load('category');

            // Skip if already journaled
            if (JournalEntry::where('reference_number', $expense->reference_number)->exists()) {
                return;
            }

            // Resolve debit account from category
            $debitAccountId = $expense->category?->default_account_id;
            if (! $debitAccountId) return;

            // Fallback: determine credit account by keyword in payment method
            $paymentMethod = strtolower($expense->payment_method);
            $creditAccountName = 'Cash';
            if (str_contains($paymentMethod, 'bank')) {
                $creditAccountName = 'Bank Account';
            } elseif (str_contains($paymentMethod, 'online')) {
                $creditAccountName = 'Bank Account';
            } elseif (str_contains($paymentMethod, 'cheque')) {
                $creditAccountName = 'Cash'; // You can change this if needed
            }

            $creditAccount = Account::where('name', $creditAccountName)->first();
            if (! $creditAccount) return;

            // Create journal entry
            $entry = JournalEntry::create([
                'company_id' => $expense->company_id,
                'reference_number' => $expense->reference_number,
                'reference_date' => $expense->expense_date ?? now(),
                'remarks' => 'Expense: ' . ($expense->description ?? 'No description'),
                'created_by_user_id' => auth()->user()->id,
            ]);

            // 1. Debit: Expense Category Account
            JournalEntryDetail::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debitAccountId,
                'name' => 'Expense - ' . ($expense->description ?? 'Unknown'),
                'debit' => $expense->amount,
                'credit' => 0,
            ]);

            // 2. Credit: Payment Method Account (Cash/Bank)
            JournalEntryDetail::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAccount->id,
                'name' => 'Payment via ' . $expense->payment_method,
                'debit' => 0,
                'credit' => $expense->amount,
            ]);
        });
    }
}
