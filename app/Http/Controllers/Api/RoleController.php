<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = Role::class;
        $this->modelName = class_basename($this->modelClass);
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $roles = $this->modelClass::with('permissions')
            ->paginate(10);

        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);

        $model = $this->modelClass::create([
            'name' => $validated['name'],
            'guard_name' => 'sanctum',
        ]);

        if (!empty($validated['permissions'])) {
            $model->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' created successfully.",
            'redirect' => route('roles.edit', $model->id),
        ], 201);
    }

    /**
     * Update a specific role's permissions.
     */
    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);
        $validated = $request->validate([
            'roleName' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $model->update(['name' => $validated['roleName']]);
        $model->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' updated successfully.",
        ], 200);
    }

    /**
     * Display a specific role with permissions.
     */
    public function show($id)
    {
        $model = $this->modelClass::with('permissions')->findOrFail($id);
        return response()->json($model, 200);
    }

    /**
     * Delete a specific role.
     */
    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $model->delete();

        return response()->json([
            'message' => "{$this->modelName} deleted successfully.",
        ], 200);
    }

    /**
     * Restore a soft-deleted role.
     */
    public function restore($id)
    {
        $model = $this->modelClass::withTrashed()->findOrFail($id);
        $model->restore();

        return response()->json([
            'message' => "{$this->modelName} restored successfully.",
        ], 200);
    }

    /**
     * Autocomplete search for roles.
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $searchTerm = $request->input('search');

        $models = $this->modelClass::where('name', 'like', "%{$searchTerm}%")
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
        ], 200);
    }
}
