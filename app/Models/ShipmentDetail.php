<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shipment_id',
        'invoice_detail_id',
        'qty',
        'courier_id',
        'tracking_number',
        'tracking_url',
        'shipment_date',
        'delivered_date',
        'courier_driver_id',
        'courier_vehicle_id',
        'destination',
        'notes',
        'status',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function invoiceDetail()
    {
        return $this->belongsTo(InvoiceDetail::class);
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function courierDriver()
    {
        return $this->belongsTo(CourierDriver::class);
    }

    public function courierVehicle()
    {
        return $this->belongsTo(CourierVehicle::class);
    }
}
