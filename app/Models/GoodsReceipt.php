<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
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
        });
    }
}
