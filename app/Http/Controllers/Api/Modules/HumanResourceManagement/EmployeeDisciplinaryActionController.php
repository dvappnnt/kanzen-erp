<?php

namespace App\Http\Controllers\Api\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDisciplinaryActionController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $actionTypes = ['verbal-warning', 'suspension', 'dismissal'];

    public function __construct()
    {
        $this->modelClass = \App\Models\EmployeeDisciplinaryAction::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(Request $request)
    {
        $query = $this->modelClass::with(['employee', 'offenseType']);

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('offense_description', 'like', "%{$searchTerm}%")
                  ->orWhere('action_description', 'like', "%{$searchTerm}%")
                  ->orWhereHas('offenseType', function($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  });
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
            'offense_type_id' => 'required|exists:offense_types,id',
            'offense_date' => 'required|date',
            'offense_description' => 'nullable|string',
            'action_date' => 'required|date',
            'action_taken' => 'required|in:' . implode(',', $this->actionTypes),
            'action_description' => 'nullable|string',
            'file_path' => 'nullable|file|max:10240', // 10MB max
            'remarks' => 'nullable|string'
        ]);

        // Handle file upload if present
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $path = $file->store('disciplinary-actions', 'public');
            $validated['file_path'] = $path;
        }

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model->load(['employee', 'offenseType']),
            'message' => "{$this->modelName} created successfully.",
            'action_types' => $this->actionTypes // Include action types in response
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['employee', 'offenseType'])->findOrFail($id);
        return response()->json([
            'data' => $model,
            'action_types' => $this->actionTypes // Include action types in response
        ]);
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'offense_type_id' => 'required|exists:offense_types,id',
            'offense_date' => 'required|date',
            'offense_description' => 'nullable|string',
            'action_date' => 'required|date',
            'action_taken' => 'required|in:' . implode(',', $this->actionTypes),
            'action_description' => 'nullable|string',
            'file_path' => 'nullable|file|max:10240', // 10MB max
            'remarks' => 'nullable|string'
        ]);

        // Handle file upload if present
        if ($request->hasFile('file_path')) {
            // Delete old file if exists
            if ($model->file_path) {
                Storage::disk('public')->delete($model->file_path);
            }
            
            $file = $request->file('file_path');
            $path = $file->store('disciplinary-actions', 'public');
            $validated['file_path'] = $path;
        }

        // Update the model
        $updated = $model->update($validated);

        if (!$updated) {
            return response()->json([
                'message' => "Failed to update {$this->modelName}.",
            ], 500);
        }

        // Refresh the model to get updated data
        $model->refresh()->load(['employee', 'offenseType']);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} updated successfully.",
            'action_types' => $this->actionTypes // Include action types in response
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

        $models = $this->modelClass::with(['employee', 'offenseType'])
            ->where(function($query) use ($searchTerm) {
                $query->where('offense_description', 'like', "%{$searchTerm}%")
                    ->orWhere('action_description', 'like', "%{$searchTerm}%")
                    ->orWhereHas('offenseType', function($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
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
            'message' => "{$this->modelName}s retrieved successfully.",
            'action_types' => $this->actionTypes // Include action types in response
        ], 200);
    }
}
