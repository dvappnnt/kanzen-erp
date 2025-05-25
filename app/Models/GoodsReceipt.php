<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        static::creating(function ($gr) {
            if (empty($gr->number)) {
                $company = \App\Models\Company::find($gr->company_id);

                if ($company) {
                    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));
                    $count = self::where('company_id', $gr->company_id)->withTrashed()->count() + 1;
                    $gr->number = sprintf('%s-GR-%06d', $prefix, $count);
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
