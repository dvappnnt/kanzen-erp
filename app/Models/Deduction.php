<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deduction extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'is_mandatory', 'deduction_type', 'default_value'];

    public function employeeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }
}
