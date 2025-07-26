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
        $this->createStockAlertPermissions();
        $this->createMaterialRequestPermissions();
        $this->createPurchaseRequestAndInternalTransferPermissions();
    }

    /**
     * Create system roles.
     */
    private function createSystemRoles(): void
    {
        $roles = [
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'manager', 'guard_name' => 'web'],
            ['name' => 'staff', 'guard_name' => 'web'],
            // ['name' => 'customer', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => $role['guard_name'],
            ]);
        }
    }
     private function createStockAlertPermissions(): void
    {
        $permissions = [
            'create stock alert thresholds',
            'read stock alert thresholds',
            'update stock alert thresholds',
            'delete stock alert thresholds',
            'restore stock alert thresholds',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
      private function createMaterialRequestPermissions(): void
    {
        $permissions = [
            'create material requests',
            'read material requests',
            'update material requests',
            'delete material requests',
            'restore material requests',
            'create material request items',
            'read material request items',
            'update material request items',
            'delete material request items',
            'restore material request items',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
     private function createPurchaseRequestAndInternalTransferPermissions(): void
    {
        $permissions = [
            'create purchase requests',
            'read purchase requests',
            'update purchase requests',
            'delete purchase requests',
            'restore purchase requests',
            'create internal transfers',
            'read internal transfers',
            'update internal transfers',
            'delete internal transfers',
            'restore internal transfers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
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
                'create product variations',
                'read product variations',
                'update product variations',
                'delete product variations',
                'restore product variations',
                'create suppliers',
                'read suppliers',
                'update suppliers',
                'delete suppliers',
                'restore suppliers',
                'create supplier product details',
                'read supplier product details',
                'update supplier product details',
                'delete supplier product details',
                'restore supplier product details',
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
                'create purchase requisitions',
                'read purchase requisitions',
                'update purchase requisitions',
                'delete purchase requisitions',
                'restore purchase requisitions',
                'create purchase orders',
                'read purchase orders',
                'update purchase orders',
                'delete purchase orders',
                'restore purchase orders',
                'create purchase order details',
                'read purchase order details',
                'update purchase order details',
                'delete purchase order details',
                'restore purchase order details',
                'create goods receipts',
                'read goods receipts',
                'update goods receipts',
                'delete goods receipts',
                'restore goods receipts',
                'create goods receipt details',
                'read goods receipt details',
                'update goods receipt details',
                'delete goods receipt details',
                'restore goods receipt details',
                'create invoices',
                'read invoices',
                'update invoices',
                'delete invoices',
                'restore invoices',
                'create invoice details',
                'read invoice details',
                'update invoice details',
                'delete invoice details',
                'restore invoice details',
                'create journal entries',
                'read journal entries',
                'update journal entries',
                'delete journal entries',
                'restore journal entries',
                'create journal entry details',
                'read journal entry details',
                'update journal entry details',
                'delete journal entry details',
                'restore journal entry details',
            ]);
        }
    }
}
