<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\SubscriptionPlan;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createSystemRoles();
        $this->assignSuperAdminPermissions();
    }

    /**
     * Create system roles.
     */
    private function createSystemRoles(): void
    {
        $roles = [
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            // ['name' => 'owner', 'guard_name' => 'web'],
            // ['name' => 'customer', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => $role['guard_name'],
            ]);
        }
    }

    /**
     * Assign all permissions to the super-admin role.
     */
    private function assignSuperAdminPermissions(): void
    {
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->syncPermissions([
                'create companies',
                'read companies',
                'update companies',
                'delete companies',
                'restore companies',
                'create users',
                'read users',
                'update users',
                'delete users',
                'restore users',
                'create products',
                'read products',
                'update products',
                'delete products',
                'restore products',
                'create suppliers',
                'read suppliers',
                'update suppliers',
                'delete suppliers',
                'restore suppliers',
                'create supplier product variations',
                'read supplier product variations',
                'update supplier product variations',
                'delete supplier product variations',
                'restore supplier product variations',
                'create warehouses',
                'read warehouses',
                'update warehouses',
                'delete warehouses',
                'restore warehouses',
                'create categories',
                'read categories',
                'update categories',
                'delete categories',
                'restore categories',
                'create attributes',
                'read attributes',
                'update attributes',
                'delete attributes',
                'restore attributes',
                'create attribute values',
                'read attribute values',
                'update attribute values',
                'delete attribute values',
                'restore attribute values',
                'create product variations',
                'read product variations',
                'update product variations',
                'delete product variations',
                'restore product variations',
                'create product specifications',
                'read product specifications',
                'update product specifications',
                'delete product specifications',
                'restore product specifications',
                'create product images',
                'read product images',
                'update product images',
                'delete product images',
                'restore product images',
                'create product tags',
                'read product tags',
                'update product tags',
                'delete product tags',
                'restore product tags',
            ]);
        }
    }
}
