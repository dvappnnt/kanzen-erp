<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'product_id'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function details()
    {
        return $this->hasMany(SupplierProductDetail::class, 'product_id', 'product_id')
            ->where('supplier_id', $this->supplier_id);
    }
}
