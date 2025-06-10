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
            'name' => 'KANZEN HOME BALIUAG',
            'email' => 'kanzenbulacan@gmail.com',
            'address' => '140 DRT Highway, Baliuag, Bulacan (Infront of Wilcon)',
            'description' => "Welcome to Kanzen Home, your premier destination for modern luxury home products. We offer a wide range of high-quality items for your kitchen and bathroom, meticulously selected to incorporate the latest advancements in technology.",
            'mobile' => '9621929589',
            'website' => 'https://www.kanzen-home.com/',
            'created_by_user_id' => null,  // Temporarily null, will update after user creation
            'country_id' => 177,
            'avatar' => 'app-settings/app-logo.png',
            'created_at' => now(),
            'updated_at' => now(),
            'token' => \Illuminate\Support\Str::random(64),
        ]);

        // Create a Company (Kanzen)
        $company = Company::create([
            'name' => 'KANZEN HOME CAVITE',
            'email' => 'kanzencavite@gmail.com',
            'address' => 'MG Center, Governors Drive, Langkaan II, Dasmarinas, Cavite',
            'description' => "Welcome to Kanzen Home, your premier destination for modern luxury home products. We offer a wide range of high-quality items for your kitchen and bathroom, meticulously selected to incorporate the latest advancements in technology.",
            'mobile' => '9619418114',
            'website' => 'https://www.kanzen-home.com/',
            'created_by_user_id' => null, // To be updated after user creation if applicable
            'country_id' => 177, // Philippines
            'avatar' => 'app-settings/app-logo.png',
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
            'company_id' => 1,
            'user_id' => $superAdmin->id,
            'level' => 2,
            'label' => 'Checked By:'
        ]);

        ApprovalLevelSetting::create([
            'type' => 'purchase-order',
            'company_id' => 1,
            'user_id' => $kanzenAdmin->id,
            'level' => 1,
            'label' => 'Approved By:'
        ]);

        ApprovalLevelSetting::create([
            'type' => 'purchase-order',
            'company_id' => 2,
            'user_id' => $superAdmin->id,
            'level' => 2,
            'label' => 'Checked By:'
        ]);

        ApprovalLevelSetting::create([
            'type' => 'purchase-order',
            'company_id' => 2,
            'user_id' => $kanzenAdmin->id,
            'level' => 1,
            'label' => 'Approved By:'
        ]);
    }
}
