<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
