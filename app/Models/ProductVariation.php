<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

    protected static function booted()
    {
        static::creating(function ($productVariation) {
            $productName = $productVariation->product->name ?? '';
            $variationName = $productVariation->name ?? '';

            $productPrefix = substr($productName, 0, 3);
            $variationPrefix = substr($variationName, 0, 3);
            $basePrefix = strtoupper($productPrefix . $variationPrefix);

            // Count existing SKUs with this prefix
            $count = static::where('sku', 'LIKE', $basePrefix . '%')->count() + 1;

            // Pad count to always be 3 digits (e.g., 001, 002)
            $increment = str_pad($count, 3, '0', STR_PAD_LEFT);

            // Optional: you can still append date if needed
            // $datetime = now()->format('ymdHi');

            // Generate final SKU
            $productVariation->sku = $basePrefix . $increment;
            $productVariation->barcode = Str::random(10);
        });
    }
}
