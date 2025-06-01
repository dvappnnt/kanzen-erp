<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePayrollDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'bank_id',
        'payroll_type',
        'account_number',
        'account_name',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
