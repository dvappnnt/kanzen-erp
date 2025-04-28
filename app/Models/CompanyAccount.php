<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
}
