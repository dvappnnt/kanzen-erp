<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'created_by_user_id',
        'company_id',
        'country_id',
        'name',
        'landline',
        'mobile',
        'email',
        'website',
        'description',
        'address',
        'avatar',
    ];

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    protected static function booted()
    {
        static::creating(function ($modelData) {
            $modelData->slug = self::generateUniqueSlug($modelData->name);

            // Generate a unique token only if it's not set manually
            if (empty($modelData->token)) {
                $modelData->token = Str::random(64);
            }
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

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_products')
            ->withTimestamps();
    }

    public function variationPrices()
    {
        return $this->hasMany(SupplierProductVariation::class);
    }
}
