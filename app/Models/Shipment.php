<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'courier_id',
        'tracking_number',
        'tracking_url',
        'shipment_date',
        'delivered_date',
        'courier_driver_id',
        'courier_vehicle_id',
        'notes',
        'file_path',
    ];

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }

    public function courier() {
        return $this->belongsTo(Courier::class);
    }

    public function courierDriver() {
        return $this->belongsTo(CourierDriver::class);
    }

    public function courierVehicle() {
        return $this->belongsTo(CourierVehicle::class);
    }
}
