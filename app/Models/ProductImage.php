<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'file_path', 'name', 'description', 'is_default'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::creating(function ($modelData) {
            $modelData->slug = self::generateUniqueSlug($modelData->name);
        });

        static::updating(function ($modelData) {
            if ($modelData->isDirty('name')) {
                $modelData->slug = self::generateUniqueSlug($modelData->name);
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
}
