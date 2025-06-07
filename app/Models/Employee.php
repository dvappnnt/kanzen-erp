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
        'name',
        'formal_full_name',
        'current_position',
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
                    // Extract base prefix from company name
                    $basePrefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));

                    // Find companies with the same base prefix
                    $matchingCompanies = \App\Models\Company::all()->filter(function ($comp) use ($basePrefix) {
                        return strtoupper(substr(preg_replace('/\s+/', '', $comp->name), 0, 3)) === $basePrefix;
                    })->values();

                    if ($matchingCompanies->count() === 1) {
                        $finalPrefix = $basePrefix;
                    } else {
                        $index = $matchingCompanies->search(function ($comp) use ($company) {
                            return $comp->id === $company->id;
                        });
                        $finalPrefix = $basePrefix . ($index !== false ? $index + 1 : 1);
                    }

                    $count = self::where('company_id', $employee->company_id)->withTrashed()->count() + 1;
                    $employee->number = sprintf('%s-EMP-%06d', $finalPrefix, $count);
                } else {
                    $employee->number = 'UNK-EMP-' . sprintf('%06d', rand(1, 999999));
                }
            }
        });
    }

    public function getNameAttribute()
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

    public function getFormalFullNameAttribute()
    {
        $middleInitial = $this->middlename ? strtoupper($this->middlename[0]) . '.' : '';
        $suffix = $this->suffix ? ' ' . $this->suffix : '';

        return "{$this->lastname}, {$this->firstname} {$middleInitial}{$suffix}";
    }

    public function getCurrentPositionAttribute()
    {
        return $this->employmentDetails()->where('to_date', null)->first()?->position->name ?? null;
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

    public function educationalAttainments()
    {
        return $this->hasMany(EmployeeEducationalAttainment::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(EmployeeWorkExperience::class);
    }

    public function dependents()
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    public function contactDetails()
    {
        return $this->hasMany(EmployeeContactDetail::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function certificates()
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    public function disciplinaryActions()
    {
        return $this->hasMany(EmployeeDisciplinaryAction::class);
    }

    public function payrollDetails()
    {
        return $this->hasMany(EmployeePayrollDetail::class);
    }

    public function employmentDetails()
    {
        return $this->hasMany(EmployeeEmploymentDetail::class);
    }

    public function skills()
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function awards()
    {
        return $this->hasMany(EmployeeAward::class);
    }

    public function performanceReviews()
    {
        return $this->hasMany(EmployeePerformanceReview::class);
    }
}
