<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalLevelSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'company_id',
        'user_id',
        'level',
        'label'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}