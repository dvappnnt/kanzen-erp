<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Models\Category;

class CategoryController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\Category::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
    }

    public function index()
    {
        return Inertia::render("{$this->modelName}/Index");
    }

    public function create()
    {
        // Fetch all tables except non-CRUD-able ones
        $allTables = \DB::select('SHOW TABLES');
        $tables = array_map('current', $allTables); // Convert to array

        // Filter out non-CRUD-able tables
        $resources = array_map(function ($table) {
            return str_replace('_', '-', $table);
        }, array_filter($tables, function ($table) {
            return !in_array($table, [
                'migrations',
                'password_resets',
                'failed_jobs',
                'personal_access_tokens',
                'cache',
                'cache_locks',
                'assigned_roles',
                'jobs',
                'job_batches',
                'password_reset_tokens',
                'sessions',
                'model_has_permissions',
                'model_has_roles',
                'role_has_permissions',
            ]);
        }));

        $categories = Category::all();
        return Inertia::render("{$this->modelName}/Create", [
            'categories' => $categories,
            'resources' => array_values($resources),
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['parent'])->findOrFail($id);

        return Inertia::render("{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $categories = Category::all();

        // Fetch all tables except non-CRUD-able ones
        $allTables = \DB::select('SHOW TABLES');
        $tables = array_map('current', $allTables); // Convert to array

        // Filter out non-CRUD-able tables
        $resources = array_map(function ($table) {
            return str_replace('_', '-', $table);
        }, array_filter($tables, function ($table) {
            return !in_array($table, [
                'migrations',
                'password_resets',
                'failed_jobs',
                'personal_access_tokens',
                'cache',
                'cache_locks',
                'assigned_roles',
                'jobs',
                'job_batches',
                'password_reset_tokens',
                'sessions',
                'model_has_permissions',
                'model_has_roles',
                'role_has_permissions',
            ]);
        }));

        return Inertia::render("{$this->modelName}/Edit", [
            'modelData' => $model,
            'categories' => $categories,
            'resources' => array_values($resources),
        ]);
    }
}
