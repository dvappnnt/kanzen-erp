<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeOvertime extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'overtime_date', 'start_time', 'end_time', 'reason', 'remarks', 'status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
