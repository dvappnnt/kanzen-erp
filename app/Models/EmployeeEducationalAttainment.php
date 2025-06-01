<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEducationalAttainment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'school_name',
        'course',
        'from_date',
        'to_date',
        'honors_received',
        'file_path',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
