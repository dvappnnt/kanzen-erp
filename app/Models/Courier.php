<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Courier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'state',
        'zip',
        'website',
        'avatar',
    ];
}
