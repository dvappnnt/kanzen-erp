<?php

namespace App\Http\Controllers\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DeductionController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\Deduction::class;
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

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create", [
            'companies' => $companies,
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $companyQuery = \App\Models\Company::query();
        $companies = $companyQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'modelData' => $model,
            'companies' => $companies,
        ]);
    }
}