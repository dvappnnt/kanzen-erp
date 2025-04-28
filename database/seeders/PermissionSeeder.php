<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all tables
        $allTables = DB::select('SHOW TABLES');
        $tables = array_map('current', $allTables);

        // Exclude irrelevant tables
        $excludedTables = [
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
            'role_has_permissions'
        ];

        // Create permissions for allowed tables
        foreach ($tables as $table) {
            if (!in_array($table, $excludedTables)) {
                $resource = str_replace('_', ' ', $table);
                $this->createCrudPermissions($resource);
            }
        }
    }

    /**
     * Create CRUD permissions.
     */
    private function createCrudPermissions(string $resource): void
    {
        $actions = ['create', 'read', 'update', 'delete', 'restore'];

        foreach ($actions as $action) {
            Permission::updateOrCreate(
                [
                    'name' => "{$action} {$resource}",
                    'guard_name' => 'web',
                ],
                [] // No additional attributes required
            );
        }
    }
}
