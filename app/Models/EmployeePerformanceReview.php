<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePerformanceReview extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'date', 'description', 'rating', 'reviewer_id', 'file_path'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
