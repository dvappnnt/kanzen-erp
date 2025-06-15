<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\WarehouseProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'company_id',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'notes',
        'is_active',
        'manager_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseRequisitions()
    {
        return $this->hasMany(PurchaseRequisition::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function products()
    {
        return $this->hasMany(WarehouseProduct::class);
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
}
