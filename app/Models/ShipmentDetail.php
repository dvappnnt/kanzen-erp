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
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function invoiceDetail()
    {
        return $this->belongsTo(InvoiceDetail::class);
    }
}
