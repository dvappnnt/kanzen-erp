<?php

namespace App\Http\Controllers\Api\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeSkillController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\EmployeeSkill::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(Request $request)
    {
        $query = $this->modelClass::with(['employee']);

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->whereHas('documentType', function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by employee_id if provided
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return $query->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'name' => 'required|string|max:255',
            'proficiency_level' => 'required|string|in:beginner,intermediate,advanced',
            'description' => 'nullable|string|max:1000'
        ]);

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['employee'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'proficiency_level' => 'required|string|in:beginner,intermediate,advanced',
            'description' => 'nullable|string|max:1000'
        ]);

        $updated = $model->update($validated);

        if (!$updated) {
            return response()->json([
                'message' => "Failed to update {$this->modelName}.",
            ], 500);
        }

        // Refresh the model to get updated data
        $model->refresh();

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} updated successfully.",
        ], 200);
    }

    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        
        // Delete file if exists
        if ($model->file_path) {
            Storage::disk('public')->delete($model->file_path);
        }
        
        $model->delete();

        return response()->json(['message' => "{$this->modelName} deleted successfully."], 200);
    }

    public function restore($id)
    {
        $model = $this->modelClass::withTrashed()->findOrFail($id);
        $model->restore();

        return response()->json([
            'message' => "{$this->modelName} restored successfully."
        ], 200);
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $searchTerm = $request->input('search');

        $models = $this->modelClass::with(['employee'])
            ->whereHas('documentType', function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            })
            ->take(10)
            ->get();

        if ($models->isEmpty()) {
            return response()->json([
                'message' => "No {$this->modelName}s found.",
            ], 404);
        }

        return response()->json([
            'data' => $models,
            'message' => "{$this->modelName}s retrieved successfully."
        ], 200);
    }
}