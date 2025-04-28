<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\AiAssistant;
use App\Models\AiAction;
use App\Models\AiMessage;
use App\Models\User;
use App\Models\BookingSetting;
use App\Models\BookingSettingSchedule;
use App\Models\UserWalletBalance;

class CompanySeeder extends Seeder
{
    public function run()
    {
        // Create a Company
        $company = Company::create([
            'name' => 'Ed Opina',
            'email' => 'support@edeesonopina.vercel.app',
            'address' => 'Quezon City',
            'description' => "With 9 years of web development experience, I specialize in creating dynamic, user-friendly applications using Laravel, VueJS, and NuxtJS. My expertise extends across full-stack development, blending backend functionality with responsive, engaging frontend interfaces.",
            'mobile' => '9604704024',
            'website' => 'https://edeesonopina.vercel.app/',
            'created_by_user_id' => null,  // Temporarily null, will update after user creation
            'country_id' => 177,
            'avatar' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'token' => \Illuminate\Support\Str::random(64),
        ]);

        // Create a Company (DataBlitz)
        $company = Company::create([
            'name' => 'DataBlitz',
            'email' => 'support@datablitz.com.ph',
            'address' => 'Makati City, Metro Manila',
            'description' => "DataBlitz is a premier retail chain offering the latest in video games, consoles, and accessories. With a strong presence in the tech and gaming community, we deliver quality products and services for Filipino gamers nationwide.",
            'mobile' => '09178889999',
            'website' => 'https://www.datablitz.com.ph/',
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
            'email' => 'super.admin@test.com',
            'password' => bcrypt('123123123'),
            'company_id' => $company->id,
        ]);
        
        // Create Admin User for Datablitz
        $datablitzAdmin = User::factory()->withPersonalTeam()->create([
            'name' => 'Admin User',
            'email' => 'admin@datablitz.com.ph',
            'password' => bcrypt('123123123'),
            'company_id' => $company->id,
        ]);

        // Assign the super-admin role to the created user
        $superAdmin->assignRole('super-admin');
        $datablitzAdmin->assignRole('admin');
        
        // Update the company with the created_by_user_id
        $company->update(['created_by_user_id' => $superAdmin->id]);
    }
}
