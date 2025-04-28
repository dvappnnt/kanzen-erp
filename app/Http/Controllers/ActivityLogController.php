<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ActivityLogController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\ActivityLog::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
    }

    public function index()
    {
        return Inertia::render("{$this->modelName}/Index");
    }

    public function create()
    {
        return Inertia::render("{$this->modelName}/Create");
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return Inertia::render("{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return Inertia::render("{$this->modelName}/Edit", [
            'modelData' => $model,
        ]);
    }
}
