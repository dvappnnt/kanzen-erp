<?php

namespace App\Http\Controllers\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PurchaseRequisitionController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\PurchaseRequisition::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/WarehouseManagement';
    }

    public function index()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index");
    }

    public function create()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create");
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['company'])->findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'modelData' => $model,
        ]);
    }

    public function settings($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Settings", [
            'modelData' => $model,
        ]);
    }

    public function print($id)
    {
        $model = $this->modelClass::with(['company'])->findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Print", [
            'modelData' => $model,
        ]);
    }
}