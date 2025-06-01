<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Department extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'company_id'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
