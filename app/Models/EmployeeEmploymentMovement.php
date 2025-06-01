<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEmploymentMovement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'movement_date',
        'movement_type',
        'from_position_id',
        'to_position_id',
        'remarks',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
