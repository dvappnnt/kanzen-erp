<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed countries first
        $this->call(CountrySeeder::class);

        // Seed permissions before roles
        $this->call(PermissionSeeder::class);

        // Seed roles after permissions and subscription plans
        $this->call(RoleSeeder::class);
        
        // Seed companies before users
        $this->call(CompanySeeder::class);

        // Seed additional users
        User::factory(10)->withPersonalTeam()->create();

        $this->call(WarehouseSeeder::class);
        $this->call(ProductCategorySeeder::class);
        $this->call(SupplierCategorySeeder::class);
        $this->call(AttributeSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(ProductSeeder::class);
        // $this->call(ProductVariationSeeder::class);
        $this->call(BankSeeder::class);
        $this->call(CompanyAccountSeeder::class);
        $this->call(AccountingModuleSeeder::class);
        $this->call(ExpenseCategorySeeder::class);

        $this->call(ProjectModuleSeeder::class);
    }
}
