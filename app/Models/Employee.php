<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'department_id',
        'number',
        'firstname',
        'middlename',
        'lastname',
        'suffix',
        'gender',
        'birthdate',
        'birthplace',
        'civil_status',
        'citizenship',
        'religion',
        'sss',
        'philhealth',
        'pagibig',
        'tin',
        'umid',
        'avatar',
        'blood_type',
        'height',
        'weight',
    ];

    protected $appends = [
        'full_name',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (empty($employee->number)) {
                $company = \App\Models\Company::find($employee->company_id);

                if ($company) {
                    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));
                    $count = self::where('company_id', $employee->company_id)->withTrashed()->count() + 1;
                    $employee->number = sprintf('%s-EMP-%06d', $prefix, $count);
                } else {
                    $employee->number = 'UNK-EMP-' . sprintf('%06d', rand(1, 999999));
                }
            }
        });
    }

    public function getFullNameAttribute()
    {
        $middleInitial = $this->middlename
            ? strtoupper(substr($this->middlename, 0, 1)) . '.'
            : null;

        $names = [
            $this->firstname,
            $middleInitial,
            $this->lastname,
            $this->suffix, // e.g. Jr., Sr., III
        ];

        return collect($names)
            ->filter() // removes null or empty values
            ->implode(' ');
    }
}
