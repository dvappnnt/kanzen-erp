<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InternalTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_transfer_id',
        'material_request_item_id',
        'quantity',
    ];

    public function transfer()
    {
        return $this->belongsTo(InternalTransfer::class, 'internal_transfer_id');
    }

    public function requestItem()
    {
        return $this->belongsTo(MaterialRequestItem::class, 'material_request_item_id');
    }
}

