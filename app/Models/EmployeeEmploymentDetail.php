<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEmploymentDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'employment_status',
        'date_hired',
        'date_regularized',
        'position_id',
        'department_id',
        'company_id',
        'supervisor_id',
        'basic_salary',
        'payroll_type',
        'tax_status',
        'remarks',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
