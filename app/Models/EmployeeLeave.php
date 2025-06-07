<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class EmployeeLeave extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'start_date', 'end_date', 'leave_type', 'reason', 'remarks', 'status'];

    protected $appends = ['leave_days', 'approved_minutes', 'approved_hours', 'minutes', 'hours'];

    public function getLeaveDaysAttribute()
    {
        return Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;
    }

    public function getApprovedMinutesAttribute()
    {
        if ($this->status == 'approved') {
            return $this->leave_days * 8 * 60;
        }
        return 0;
    }

    public function getApprovedHoursAttribute()
    {
        if ($this->status == 'approved') {
            return $this->leave_days * 8;
        }
        return 0;
    }

    public function getMinutesAttribute()
    {
        return $this->leave_days * 8 * 60;
    }

    public function getHoursAttribute()
    {
        return $this->leave_days * 8;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
