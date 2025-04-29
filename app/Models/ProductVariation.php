<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'name',
        'is_default'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariationAttribute::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_product_variations')
            ->withPivot('price', 'cost', 'lead_time_days')
            ->withTimestamps();
    }

    public function supplierProductDetails()
    {
        return $this->hasMany(SupplierProductDetail::class, 'product_variation_id');
    }
}
