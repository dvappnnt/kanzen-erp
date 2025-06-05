<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSkill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'name',
        'proficiency_level',
        'description'
    ];
    
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
