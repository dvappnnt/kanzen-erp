<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'account_type_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function expenses()
    {
        return $this->hasManyThrough(
            Expense::class,
            Category::class,
            'default_account_id',
            'category_id'
        );
    }

    public function getBalanceAttribute()
    {
        // For now, we'll just calculate from expenses
        // You may want to add other transactions later
        return $this->expenses()->sum('amount');
    }

    public function scopeWithActiveAccounts($query)
    {
        return $query->with(['accounts' => fn($q) => $q->where('is_active', 1)]);
    }
}
