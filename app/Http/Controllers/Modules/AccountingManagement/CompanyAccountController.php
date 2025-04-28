<?php

namespace App\Http\Controllers\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompanyAccountController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\CompanyAccount::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/AccountingManagement';
    }

    public function index()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index");
    }

    public function create()
    {
        $bankQuery = \App\Models\Bank::orderBy('name', 'asc');
        $banks = $bankQuery->get();
        $companyQuery = \App\Models\Company::orderBy('name', 'asc');
        $companies = $companyQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create", [
            'banks' => $banks,
            'companies' => $companies,
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['company', 'bank'])->findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::with(['company', 'bank'])->findOrFail($id);
        $bankQuery = \App\Models\Bank::orderBy('name', 'asc');
        $banks = $bankQuery->get();
        $companyQuery = \App\Models\Company::orderBy('name', 'asc');
        $companies = $companyQuery->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'modelData' => $model,
            'banks' => $banks,
            'companies' => $companies,
        ]);
    }
}