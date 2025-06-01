<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use App\Models\ApprovalLevelSetting;

class CompanySeeder extends Seeder
{
    public function run()
    {
        // Create a Company
        $company = Company::create([
            'name' => 'Kanzen Homes Bulacan',
            'email' => 'kanzenbulacan@gmail.com',
            'address' => '140 Doña Remedios Trinidad Highway Tarcan Baliuag City, Bulacan',
            'description' => "Kanzen Homes sells smart home appliances and gadgets",
            'mobile' => '09178889999',
            'website' => 'https://fe-kanzen.dvapp.cloud/',
            'created_by_user_id' => null,  // Temporarily null, will update after user creation
            'country_id' => 177,
            'avatar' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'token' => \Illuminate\Support\Str::random(64),
        ]);

        // Create a Company (Kanzen)
        $company = Company::create([
            'name' => 'Kanzen Homes Cavite',
            'email' => 'support@dvapp.cloud',
            'address' => 'MG Center, Governors Drive, Langkaan II Dasmariñas, Cavite',
            'description' => "Kanzen Homes sells smart home appliances and gadgets",
            'mobile' => '09178889999',
            'website' => 'https://fe-kanzen.dvapp.cloud/',
            'created_by_user_id' => null, // To be updated after user creation if applicable
            'country_id' => 177, // Philippines
            'avatar' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'token' => \Illuminate\Support\Str::random(64),
        ]);

        // Create Super Admin User after company is created
        $superAdmin = User::factory()->withPersonalTeam()->create([
            'name' => 'Super Admin User',
            'email' => 'super.admin@kanzen.ph',
            'password' => bcrypt('123123123'),
            'company_id' => $company->id,
        ]);
        
        // Create Admin User for Datablitz
        $kanzenAdmin = User::factory()->withPersonalTeam()->create([
            'name' => 'Admin User',
            'email' => 'admin@kanzen.ph',
            'password' => bcrypt('123123123'),
            'company_id' => $company->id,
        ]);

        // Assign the super-admin role to the created user
        $superAdmin->assignRole('super-admin');
        $kanzenAdmin->assignRole('admin');
        
        // Update the company with the created_by_user_id
        $company->update(['created_by_user_id' => $superAdmin->id]);

        // Create Approval Level Settings
        ApprovalLevelSetting::create([
            'type' => 'purchase-order',
            'company_id' => $company->id,
            'user_id' => $superAdmin->id,
            'level' => 2,
            'label' => 'Checked By:'
        ]);

        ApprovalLevelSetting::create([
            'type' => 'purchase-order',
            'company_id' => $company->id,
            'user_id' => $kanzenAdmin->id,
            'level' => 1,
            'label' => 'Approved By:'
        ]);
    }
}
