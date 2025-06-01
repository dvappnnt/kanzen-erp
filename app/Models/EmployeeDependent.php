<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDependent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'name',
        'mobile',
        'landline',
        'address',
        'email',
        'relationship',
        'birthdate',
        'birthplace',
        'is_emergency_contact',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
