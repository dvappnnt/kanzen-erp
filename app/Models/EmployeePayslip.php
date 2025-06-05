<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePayslip extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'company_id', 'period_start', 'period_end', 'total_hours_worked', 'total_overtime_hours', 'total_late_minutes', 'total_undertime_minutes', 'total_days_absent', 'basic_salary', 'overtime_pay', 'other_earnings', 'total_deductions', 'net_pay', 'notes'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
