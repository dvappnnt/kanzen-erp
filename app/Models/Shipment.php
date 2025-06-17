<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Shipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'number',
        'status',
        'notes',
        'created_by_user_id',
        'file_path',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function details()
    {
        return $this->hasMany(ShipmentDetail::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected static function booted()
    {
        static::creating(function ($modelData) {
            $modelData->created_by_user_id = Auth::id();

            if (empty($modelData->number)) {
                $company = Company::find($modelData->invoice->company_id);

                if ($company) {
                    // Extract base prefix from company name
                    $basePrefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));

                    // Find companies with same base prefix
                    $matchingCompanies = Company::all()->filter(function ($comp) use ($basePrefix) {
                        return strtoupper(substr(preg_replace('/\s+/', '', $comp->name), 0, 3)) === $basePrefix;
                    })->values();

                    if ($matchingCompanies->count() === 1) {
                        // Unique: use basePrefix only
                        $finalPrefix = $basePrefix;
                    } else {
                        // Shared prefix: assign index
                        $index = $matchingCompanies->search(function ($comp) use ($company) {
                            return $comp->id === $company->id;
                        });
                        $finalPrefix = $basePrefix . ($index !== false ? $index + 1 : 1);
                    }

                    // Generate SHP number
                    $count = self::where('company_id', $modelData->company_id)->withTrashed()->count() + 1;
                    $modelData->number = sprintf('%s-SHP-%06d', $finalPrefix, $count);
                } else {
                    $modelData->number = 'UNK-SHP-' . sprintf('%06d', rand(1, 999999));
                }
            }

            if (empty($modelData->status)) {
                $modelData->status = 'draft';
            }
        });
    }
}
