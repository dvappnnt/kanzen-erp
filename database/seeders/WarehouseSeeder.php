<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $locations = [
            'Quezon City',
            'Taguig',
            'Makati',
            'Cebu City',
            'Davao City',
            'Baguio',
            'Iloilo City',
            'Cagayan de Oro',
        ];

        foreach ($locations as $index => $location) {
            $companyId = rand(1, 2); // Randomly assign to company 1 or 2
            $name = "{$location} Warehouse " . chr(65 + $index); // A, B, C, ...
            DB::table('warehouses')->insert([
                'company_id' => $companyId,
                'token' => Str::random(64),
                'slug' => Str::slug($name),
                'created_by_user_id' => $companyId,
                'category_id' => null,
                'country_id' => 177,
                'name' => $name,
                'email' => null,
                'landline' => null,
                'mobile' => null,
                'address' => $location,
                'description' => "Storage facility located in {$location}",
                'website' => null,
                'avatar' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
