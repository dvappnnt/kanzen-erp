<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourierDriver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'courier_id',
        'name',
        'license_number',
        'mobile',
        'landline',
        'email',
        'birthdate',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
