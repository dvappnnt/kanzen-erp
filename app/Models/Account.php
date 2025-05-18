<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['name', 'code', 'account_type_id', 'is_active'];

    public function type()
    {
        return $this->belongsTo(AccountType::class);
    }
}
