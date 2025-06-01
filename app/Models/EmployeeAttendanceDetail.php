<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAttendanceDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_date',
        'time_in',
        'break_out',
        'break_in',
        'time_out',
        'total_hours_worked',
        'late_minutes',
        'undertime_minutes',
        'is_absent',
        'shift_type',
        'is_overtime',
        'overtime_hours',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
