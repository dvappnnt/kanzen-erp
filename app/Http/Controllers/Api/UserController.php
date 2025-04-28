<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class UserController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = User::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        $users = $this->modelClass::with('roles')
            ->latest()
            ->paginate(perPage: 10);

        // Attach role name dynamically
        $users->getCollection()->transform(function ($user) {
            $user->role = $user->getRoleNames()->first() ?? 'N/A';
            return $user;
        });

        return $users;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name', // Ensure valid role
        ]);

        // Hash the password
        $validated['password'] = Hash::make($validated['password']);

        // Create the user
        $user = $this->modelClass::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'avatar' => $validated['avatar'] ?? null,
        ]);

        // Assign the role
        $user->assignRole($validated['role']);

        event(new Registered($user));

        return response()->json([
            'modelData' => $user,
            'message' => "{$this->modelName} '{$user->name}' created successfully. Verification email sent.",
        ], 201);
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $model->id,
            'avatar' => 'nullable|string',
            'role' => 'required|string|exists:roles,name',
        ]);

        $model->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar' => $validated['avatar'] ?? null,
        ]);

        // Sync the role
        $model->syncRoles([$validated['role']]); // Replace old roles with new one

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' updated successfully.",
        ], 200);
    }

    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $model->delete();

        return response()->json([
            'message' => "{$this->modelName} deleted successfully.",
        ], 200);
    }

    public function restore($id)
    {
        $model = $this->modelClass::withTrashed()->findOrFail($id);
        $model->restore();

        return response()->json([
            'message' => "{$this->modelName} restored successfully.",
        ], 200);
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $searchTerm = $request->input('search');

        $models = $this->modelClass::query()
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            })
            ->orderByRaw("CASE 
                WHEN name LIKE '{$searchTerm}%' THEN 1 
                WHEN email LIKE '{$searchTerm}%' THEN 2 
                ELSE 3 END")
            ->take(10)
            ->get(['id', 'name', 'email', 'created_at']);

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

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:users,id'],
            'new_password' => ['required', 'string', 'min:8', 'same:confirm_new_password'],
        ]);

        $model = $this->modelClass::findOrFail($validated['id']);
        $model->password = Hash::make($validated['new_password']);
        $model->save();

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' updated successfully.",
        ], 200);
    }
}
