<?php

namespace App\Http\Controllers\Api\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeEmploymentDetail;
use App\Models\EmployeePayrollDetail;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\Employee::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['company', 'department'])->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'required|string|max:255',
            'gender' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('employees/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['company', 'department'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            // Employee Information
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',

            // Personal Information
            'firstname' => 'nullable|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'birthplace' => 'nullable|string|max:255',
            'civil_status' => 'nullable|string|max:255',
            'citizenship' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',

            // Vital Statistics
            'blood_type' => 'nullable|string|max:10',
            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',

            // Government IDs
            'sss' => 'nullable|string|max:50',
            'philhealth' => 'nullable|string|max:50',
            'pagibig' => 'nullable|string|max:50',
            'tin' => 'nullable|string|max:50',
            'umid' => 'nullable|string|max:50',

            // Avatar
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($model->avatar && \Storage::disk('public')->exists($model->avatar)) {
                \Storage::disk('public')->delete($model->avatar);
            }

            $avatarPath = $request->file('avatar')->store('employees/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $model->update($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' updated successfully.",
        ]);
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

        $models = $this->modelClass::with(['company', 'department'])
            ->where('firstname', 'like', "%{$searchTerm}%")
            ->orWhere('middlename', 'like', "%{$searchTerm}%")
            ->orWhere('lastname', 'like', "%{$searchTerm}%")
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

    public function updateEmploymentDetails(Request $request, $id)
    {
        $employee = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'employment_status' => 'required|string|max:255',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date',
            'position_id' => 'required|exists:positions,id',
            'supervisor_id' => 'nullable|exists:employees,id',
            'basic_salary' => 'required|numeric',
            'salary_type' => 'required|string|max:255|in:monthly,semi-monthly,weekly,daily',
            'tax_status' => 'required|string|max:255|in:single,married,widowed,separated,divorced',
            'remarks' => 'nullable|string'
        ]);

        if ($employee->employmentDetails()->exists()) {
            $employee->employmentDetails()->update($validated);
        } else {
            $employee->employmentDetails()->create($validated);
        }

        return response()->json(['message' => "Employment details updated successfully."], 200);
    }

    public function updatePayrollDetails(Request $request, $id)
    {
        // Find the employee first
        $employee = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'payroll_type' => 'required|string|max:255|in:ATM,CASH,CHECK',
        ]);

        try {
            // Check if payroll details exist for this employee
            if ($employee->payrollDetails()->exists()) {
                // Update existing payroll details
                $employee->payrollDetails()->update($validated);
            } else {
                // Create new payroll details
                $employee->payrollDetails()->create($validated);
            }

            // Refresh the employee model to get the latest data
            $employee->refresh();

            return response()->json([
                'message' => 'Payroll details updated successfully.',
                'data' => $employee->payrollDetails
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating payroll details', [
                'employee_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to update payroll details.'], 500);
        }
    }
}
