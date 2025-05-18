<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class WarehouseProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'supplier_product_detail_id',
        'qty',
        'price',
        'last_cost',
        'average_cost',
        'has_serials',
        'critical_level_qty'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'last_cost' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'has_serials' => 'boolean',
        'critical_level_qty' => 'integer'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplierProductDetail()
    {
        return $this->belongsTo(SupplierProductDetail::class);
    }

    public function serials()
    {
        return $this->hasMany(WarehouseProductSerial::class);
    }

    public function transfers()
    {
        return $this->hasMany(WarehouseTransfer::class, 'destination_warehouse_id', 'warehouse_id');
    }

    protected static function booted()
    {
        static::creating(function ($modelData) {
            $modelData->slug = self::generateUniqueSlug($modelData->supplierProductDetail->product->name);

            // Generate a unique token only if it's not set manually
            if (empty($modelData->token)) {
                $modelData->token = Str::random(64);
            }
        });

        static::updating(function ($modelData) {
            if ($modelData->isDirty('name')) {
                $modelData->slug = self::generateUniqueSlug($modelData->supplierProductDetail->product->name);
            }
        });
    }

    protected static function generateUniqueSlug($name)
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $count = 0;

        while (static::where('slug', $slug)->exists()) {
            $count++;
            $slug = "{$baseSlug}-{$count}";
        }

        return $slug;
    }

    public function stockAdjustments()
    {
        return $this->hasMany(WarehouseStockAdjustment::class);
    }

    public function stockTransfers()
    {
        return $this->hasMany(WarehouseStockTransfer::class, 'origin_warehouse_product_id')
            ->orWhere('destination_warehouse_product_id', $this->id);
    }
}
