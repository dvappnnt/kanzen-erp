<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'token',
        'company_id',
        'category_id',
        'name',
        'description',
        'avatar',
        'unit_of_measure',
        'has_variation',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_product_variations')
            ->withPivot('price', 'cost', 'lead_time_days')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::creating(function ($modelData) {
            $modelData->slug = self::generateUniqueSlug($modelData->name);
            $modelData->company_id = auth()->user()->company->id;

            // Generate a unique token only if it's not set manually
            if (empty($modelData->token)) {
                $modelData->token = Str::random(64);
            }
        });

        static::created(function ($modelData) {
            if ($modelData->has_variation) {
                $modelData->variations()->create([
                    'name' => 'Default',
                    'is_default' => true,
                ]);
            } else {
                $conditionAttributeId = DB::table('attributes')->where('name', 'Condition')->value('id');

                foreach (['New', 'Used', 'Refurbished'] as $condition) {
                    ProductVariation::create([
                        'product_id' => $modelData->id,
                        'attribute_id' => $conditionAttributeId,
                        'attribute_value_id' => DB::table('attribute_values')->where('value', $condition)->value('id'),
                    ]);
                }
            }
        });

        static::updating(function ($modelData) {
            if ($modelData->isDirty('name')) {
                $modelData->slug = self::generateUniqueSlug($modelData->name);
                $modelData->company_id = auth()->user()->company->id;
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
