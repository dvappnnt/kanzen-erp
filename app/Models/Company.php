<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $appends = ['created_by_user'];

    public function getCreatedByUserAttribute()
    {
        return $this->created_by_user()->first();
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'company_members')
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
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

            ApprovalLevelSetting::create([
                'company_id' => $modelData->id,
                'type' => 'purchase-order',
                'level' => 1,
                'label' => 'Approved By:',
                'user_id' => $modelData->created_by_user_id
            ]);

            ApprovalLevelSetting::create([
                'company_id' => $modelData->id,
                'type' => 'purchase-order',
                'level' => 2,
                'label' => 'Checked By:',
                'user_id' => $modelData->created_by_user_id
            ]);
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
