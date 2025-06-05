<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLeave extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'start_date', 'end_date', 'leave_type', 'reason', 'remarks', 'status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
