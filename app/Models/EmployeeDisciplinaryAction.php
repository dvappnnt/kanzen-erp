<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDisciplinaryAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'offense_type_id',
        'offense_date',
        'offense_description',
        'action_date',
        'action_taken',
        'action_description',
        'file_path',
        'remarks',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function offenseType()
    {
        return $this->belongsTo(OffenseType::class);
    }
}
