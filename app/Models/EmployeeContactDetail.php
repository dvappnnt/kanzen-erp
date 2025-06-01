<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeContactDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'address',
        'landline',
        'mobile',
        'email',
        'is_primary',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
