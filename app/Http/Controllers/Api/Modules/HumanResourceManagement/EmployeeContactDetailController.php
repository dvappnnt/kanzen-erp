<?php

namespace App\Http\Controllers\Api\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeContactDetailController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\EmployeeContactDetail::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(Request $request)
    {
        $query = $this->modelClass::with(['employee']);

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('level', 'like', "%{$searchTerm}%")
                  ->orWhere('school_name', 'like', "%{$searchTerm}%")
                  ->orWhere('honors_received', 'like', "%{$searchTerm}%");
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
            'address' => 'nullable|string|max:255',
            'landline' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'is_primary' => 'nullable|boolean',
        ]);

        // If this is being set as primary, find the last primary contact and set it to false
        if ($validated['is_primary']) {
            $lastPrimary = $this->modelClass::where('employee_id', $validated['employee_id'])
                ->where('is_primary', true)
                ->latest()
                ->first();

            if ($lastPrimary) {
                $lastPrimary->update(['is_primary' => false]);
            }
        }

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'address' => 'nullable|string|max:255',
            'landline' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'is_primary' => 'nullable|boolean',
        ]);

        // If this is being set as primary, find the last primary contact and set it to false
        if ($validated['is_primary']) {
            $lastPrimary = $this->modelClass::where('employee_id', $validated['employee_id'])
                ->where('id', '!=', $id) // Exclude current record
                ->where('is_primary', true)
                ->latest()
                ->first();

            if ($lastPrimary) {
                $lastPrimary->update(['is_primary' => false]);
            }
        }

        // Update the model
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

        $models = $this->modelClass::with(['employee'])->where('name', 'like', "%{$searchTerm}%")
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