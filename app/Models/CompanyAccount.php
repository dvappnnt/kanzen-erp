<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CompanyAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_id',
        'company_id',
        'name',
        'number',
        'type',
        'status',
        'balance',
        'currency',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
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
