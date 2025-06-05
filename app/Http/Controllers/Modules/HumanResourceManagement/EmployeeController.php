<?php

namespace App\Http\Controllers\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\Employee::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/HumanResourceManagement';
    }

    public function index()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index");
    }

    public function create()
    {
        $companyQuery = \App\Models\Company::query();
        $companies = $companyQuery->get();
        $departmentQuery = \App\Models\Department::query();
        $departments = $departmentQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create", [
            'companies' => $companies,
            'departments' => $departments,
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with('company', 'department')->findOrFail($id);
        $companyQuery = \App\Models\Company::query();
        $companies = $companyQuery->get();
        $departmentQuery = \App\Models\Department::query();
        $departments = $departmentQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Show", [
            'modelData' => $model,
            'companies' => $companies,
            'departments' => $departments,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $companyQuery = \App\Models\Company::query();
        $companies = $companyQuery->get();
        $departmentQuery = \App\Models\Department::query();
        $departments = $departmentQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'modelData' => $model,
            'companies' => $companies,
            'departments' => $departments,
        ]);
    }

    public function educationalAttainments($id)
    {
        $model = $this->modelClass::with(['educationalAttainments'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/EducationalAttainments", [
            'modelData' => $model,
        ]);
    }

    public function workExperiences($id)
    {
        $model = $this->modelClass::with(['workExperiences'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/WorkExperiences", [
            'modelData' => $model,
        ]);
    }

    public function dependents($id)
    {
        $model = $this->modelClass::with(['dependents'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Dependents", [
            'modelData' => $model,
        ]);
    }

    public function contactDetails($id)
    {
        $model = $this->modelClass::with(['contactDetails'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/ContactDetails", [
            'modelData' => $model,
        ]);
    }

    public function documents($id)
    {
        $model = $this->modelClass::with(['documents'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Documents", [
            'modelData' => $model,
        ]);
    }

    public function certificates($id)
    {
        $model = $this->modelClass::with(['certificates'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Certificates", [
            'modelData' => $model,
        ]);
    }

    public function awards($id)
    {
        $model = $this->modelClass::with(['awards'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Awards", [
            'modelData' => $model,
        ]);
    }

    public function skills($id)
    {
        $model = $this->modelClass::with(['skills'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Skills", [
            'modelData' => $model,
        ]);
    }

    public function performanceReviews($id)
    {
        $model = $this->modelClass::with(['performanceReviews', 'performanceReviews.reviewer'])->findOrFail($id);

        $reviewerQuery = \App\Models\Employee::orderBy('lastname', 'asc');
        $reviewerQuery->where('company_id', $model->company_id);
        $reviewers = $reviewerQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/PerformanceReviews", [
            'modelData' => $model,
            'reviewers' => $reviewers,
        ]);
    }

    public function disciplinaryActions($id)
    {
        $model = $this->modelClass::with(['disciplinaryActions'])->findOrFail($id);
        return Inertia::render("{$this->modulePath}/{$this->modelName}/DisciplinaryActions", [
            'modelData' => $model,
        ]);
    }

    public function payrollDetails($id)
    {
        $model = $this->modelClass::with(['payrollDetails', 'payrollDetails.bank'])->findOrFail($id);
        $bankQuery = \App\Models\Bank::orderBy('name', 'asc');
        $banks = $bankQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/PayrollDetails", [
            'modelData' => $model,
            'banks' => $banks,
        ]);
    }

    public function employmentDetails($id)
    {
        $model = $this->modelClass::with(['employmentDetails'])->findOrFail($id);
        $positionQuery = \App\Models\Position::orderBy('name', 'asc');
        $positions = $positionQuery->get();

        $supervisorQuery = \App\Models\Employee::orderBy('lastname', 'asc');
        $supervisorQuery->where('company_id', $model->company_id);
        $supervisors = $supervisorQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/EmploymentDetails", [
            'modelData' => $model,
            'positions' => $positions,
            'supervisors' => $supervisors,
        ]);
    }

    public function print($id)
    {
        $model = $this->modelClass::with('company', 'department', 'educationalAttainments', 'workExperiences', 'dependents', 'contactDetails', 'documents', 'certificates', 'disciplinaryActions', 'payrollDetails', 'payrollDetails.bank', 'employmentDetails', 'employmentDetails.position', 'skills', 'awards')->findOrFail($id);
        $educationalAttainments = $model->educationalAttainments;
        $workExperiences = $model->workExperiences;
        $dependents = $model->dependents;
        $contactDetails = $model->contactDetails;
        $documents = $model->documents;
        $certificates = $model->certificates;
        $disciplinaryActions = $model->disciplinaryActions;
        $payrollDetails = $model->payrollDetails;
        $employmentDetails = $model->employmentDetails;
        $skills = $model->skills;
        $awards = $model->awards;

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Print", [
            'modelData' => $model,
            'educationalAttainments' => $educationalAttainments,
            'workExperiences' => $workExperiences,
            'dependents' => $dependents,
            'contactDetails' => $contactDetails,
            'documents' => $documents,
            'certificates' => $certificates,
            'disciplinaryActions' => $disciplinaryActions,
            'payrollDetails' => $payrollDetails,
            'employmentDetails' => $employmentDetails,
            'skills' => $skills,
            'awards' => $awards,
        ]);
    }
}
