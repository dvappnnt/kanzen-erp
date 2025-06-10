<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourierVehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'courier_id',
        'type',
        'brand',
        'model',
        'plate_number',
        'color',
        'year',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
