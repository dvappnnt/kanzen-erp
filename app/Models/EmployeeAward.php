<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAward extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'title', 'description', 'date', 'awarded_by', 'file_path'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
