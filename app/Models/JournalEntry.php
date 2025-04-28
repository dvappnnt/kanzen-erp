<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'reference_number',
        'reference_date',
        'total_debit',
        'total_credit',
        'remarks',
        'created_by_user_id',
    ];

    protected $casts = [
        'reference_date' => 'date',
    ];

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
