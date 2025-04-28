<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of roles and permissions.
     */
    public function index()
    {
        return Inertia::render('Roles/Index');
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        return Inertia::render('Roles/Create');
    }

    /**
     * Show the form for editing a role.
     */
    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('name')->toArray(); // Current permissions

        // Fetch all tables except non-CRUD-able ones
        $allTables = \DB::select('SHOW TABLES');
        $tables = array_map('current', $allTables); // Convert to array

        // Filter out non-CRUD-able tables
        $resources = array_map(function ($table) {
            return str_replace('_', ' ', $table);
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

        // Return the view using Inertia
        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'rolePermissions' => $rolePermissions,
            'resources' => array_values($resources), // Ensure array format
        ]);
    }
}
