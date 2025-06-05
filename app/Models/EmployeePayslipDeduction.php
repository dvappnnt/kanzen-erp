<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePayslipDeduction extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_payslip_id', 'deduction_id', 'amount'];

    public function employeePayslip()
    {
        return $this->belongsTo(EmployeePayslip::class);
    }
}
