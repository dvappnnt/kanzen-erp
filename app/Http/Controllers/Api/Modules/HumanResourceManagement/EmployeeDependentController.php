<?php

namespace App\Http\Controllers\Api\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeDependentController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\EmployeeDependent::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(Request $request)
    {
        $query = $this->modelClass::with(['employee']);

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('relationship', 'like', "%{$searchTerm}%")
                  ->orWhere('birth_date', 'like', "%{$searchTerm}%");
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
            'relationship' => 'required|string|max:255',
            'birthdate' => 'required|date',
            'birthplace' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'landline' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'is_emergency_contact' => 'boolean',
        ]);

        // If this is being set as emergency contact, find the last emergency contact and set it to false
        if ($validated['is_emergency_contact']) {
            $lastEmergencyContact = $this->modelClass::where('employee_id', $validated['employee_id'])
                ->where('is_emergency_contact', true)
                ->latest()
                ->first();

            if ($lastEmergencyContact) {
                $lastEmergencyContact->update(['is_emergency_contact' => false]);
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
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'birthdate' => 'required|date',
            'birthplace' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'landline' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'is_emergency_contact' => 'boolean',
        ]);

        // If this is being set as emergency contact, find the last emergency contact and set it to false
        if ($validated['is_emergency_contact']) {
            $lastEmergencyContact = $this->modelClass::where('employee_id', $validated['employee_id'])
                ->where('id', '!=', $id) // Exclude current record
                ->where('is_emergency_contact', true)
                ->latest()
                ->first();

            if ($lastEmergencyContact) {
                $lastEmergencyContact->update(['is_emergency_contact' => false]);
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
