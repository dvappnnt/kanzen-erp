<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GoodsReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'number',
        'date',
        'notes',
        'status',
        'created_by_user_id'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->number;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function details()
    {
        return $this->hasMany(GoodsReceiptDetail::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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

        static::creating(function ($gr) {
            if (empty($gr->number)) {
                $company = \App\Models\Company::find($gr->company_id);

                if ($company) {
                    // Extract base prefix
                    $basePrefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));

                    // Get all companies with the same base prefix
                    $matchingCompanies = \App\Models\Company::all()->filter(function ($comp) use ($basePrefix) {
                        return strtoupper(substr(preg_replace('/\s+/', '', $comp->name), 0, 3)) === $basePrefix;
                    })->values();

                    if ($matchingCompanies->count() === 1) {
                        // Only one company with this prefix
                        $finalPrefix = $basePrefix;
                    } else {
                        // Multiple companies sharing prefix
                        $index = $matchingCompanies->search(function ($comp) use ($company) {
                            return $comp->id === $company->id;
                        });
                        $finalPrefix = $basePrefix . ($index !== false ? $index + 1 : 1);
                    }

                    // Count GRs for this company
                    $count = self::where('company_id', $gr->company_id)->withTrashed()->count() + 1;
                    $gr->number = sprintf('%s-GR-%06d', $finalPrefix, $count);
                } else {
                    $gr->number = 'UNK-GR-' . sprintf('%06d', rand(1, 999999));
                }
            }

            // Set initial status
            if (empty($gr->status)) {
                $gr->status = 'pending';
            }
        });
    }
}
