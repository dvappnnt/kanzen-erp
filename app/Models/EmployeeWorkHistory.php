<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeWorkHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'company_name',
        'position',
        'responsibilities',
        'start_date',
        'end_date',
        'last_salary',
        'reason_for_leaving',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
