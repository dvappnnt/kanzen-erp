<?php

namespace App\Http\Controllers\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EmployeeOvertimeController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\EmployeeOvertime::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/HumanResourceManagement';
    }

    public function index()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index");
    }

    public function create()
    {
        $employeesQuery = \App\Models\Employee::query();
        $employees = $employeesQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create", [
            'employees' => $employees,
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['employee'])->findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $employeesQuery = \App\Models\Employee::query();
        $employees = $employeesQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'modelData' => $model,
            'employees' => $employees,
        ]);
    }
}