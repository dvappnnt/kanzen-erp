<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

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
        'country_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
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
        return $this->belongsToMany(Supplier::class, 'supplier_products')
            ->withTimestamps();
    }

    public function supplierProductDetails()
    {
        return $this->hasMany(SupplierProductDetail::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('company_filter', function (Builder $builder) {
            $user = Auth::user();
    
            if ($user && !$user->hasRole('super-admin')) {
                $builder->where(function ($query) use ($user) {
                    $query->where('company_id', $user->company_id)
                          ->orWhereNull('company_id');
                });
            }
        });

        static::creating(function ($modelData) {
            $modelData->slug = self::generateUniqueSlug($modelData->name);
            
            if (Auth::check()) {
                $modelData->company_id = Auth::user()->company->id;
            }

            // Generate a unique token only if it's not set manually
            if (empty($modelData->token)) {
                $modelData->token = Str::random(64);
            }
        });

        static::created(function ($modelData) {
            $defaultVariation = $modelData->variations()->create([
                'name' => 'Default Product Variation',
                'is_default' => true,
            ]);

            $conditionAttributeId = DB::table('attributes')->where('name', 'Condition')->value('id');

            foreach (['New'] as $condition) {
                ProductVariationAttribute::create([
                    'product_variation_id' => $defaultVariation->id,
                    'attribute_id' => $conditionAttributeId,
                    'attribute_value_id' => DB::table('attribute_values')->where('value', $condition)->value('id'),
                ]);
            }
        });

        static::updating(function ($modelData) {
            if ($modelData->isDirty('name')) {
                $modelData->slug = self::generateUniqueSlug($modelData->name);
                if (Auth::check()) {
                    $modelData->company_id = Auth::user()->company->id;
                }
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
