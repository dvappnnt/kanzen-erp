<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class EmployeeOvertime extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'date', 'start_time', 'end_time', 'reason', 'remarks', 'status'];

    protected $casts = [
        'date' => 'date',
    ];

    protected $appends = ['time_difference', 'approved_hours_rendered', 'approved_minutes_rendered', 'hours_rendered', 'minutes_rendered'];

    public function getTimeDifferenceAttribute()
    {
        try {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

            // If end time is before start time, it means overtime went to next day
            if ($end->lt($start)) {
                $end->addDay();
            }

        return $start->diffInMinutes($end);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getHoursRenderedAttribute()
    {
        return round($this->time_difference / 60, 2);
    }

    public function getMinutesRenderedAttribute()
    {
        return $this->time_difference;
    }

    public function getApprovedHoursRenderedAttribute()
    {
        if ($this->status == 'approved') {
            return round($this->time_difference / 60, 2);
        }
        return 0;
    }

    public function getApprovedMinutesRenderedAttribute()
    {
        if ($this->status == 'approved') {
            return $this->time_difference;
        }
        return 0;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
