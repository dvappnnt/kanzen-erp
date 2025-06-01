<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeCertificate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'title',
        'organizer',
        'date_from',
        'date_to',
        'location',
        'file_path',
        'remarks',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
