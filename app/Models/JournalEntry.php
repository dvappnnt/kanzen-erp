<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_id',
        'reference_number',
        'reference_date',
        'remarks',
        'created_by_user_id',
    ];

    protected $casts = [
        'reference_date' => 'date',
    ];

    protected $appends = ['total_debit', 'total_credit'];

    public function getTotalDebitAttribute()
    {
        return $this->details()->sum('debit');
    }

    public function getTotalCreditAttribute()
    {
        return $this->details()->sum('credit');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function details()
    {
        return $this->hasMany(JournalEntryDetail::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('company_filter', function (Builder $builder) {
            $user = Auth::user();
    
            if ($user && !$user->hasRole('super-admin')) {
                $builder->where(function ($query) use ($user) {
                    $query->where('company_id', $user->company_id)
                          ->orWhereNull('company_id');
                });
            }
        });
    }
}
