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
            [
                'name' => 'Bulacan Showroom',
                'address' => '140 Doña Remedios Trinidad Highway Tarcan Baliuag City, Bulacan',
            ],
            [
                'name' => 'SHOWROOM - CAVITE',
                'address' => 'MG Center, Governors Drive, Langkaan II Dasmariñas, Cavite',
                'mobile' => '9619418114',
            ],
            [
                'name' => 'SHOWROOM - BULACAN',
                'address' => '792 Pinagpala St. Pinagbarilan Baliuag City, Bulacan',
                'mobile' => '9621929589',
            ],
        ];

        foreach ($locations as $location) {
            $companyId = rand(1, 2); // Randomly assign to company 1 or 2
            DB::table('warehouses')->insert([
                'company_id' => $companyId,
                'token' => Str::random(64),
                'slug' => Str::slug($location['name']),
                'created_by_user_id' => $companyId,
                'category_id' => null,
                'country_id' => 177, // Philippines
                'name' => $location['name'],
                'email' => null,
                'landline' => null,
                'mobile' => null,
                'address' => $location['address'],
                'description' => "Facility located at {$location['address']}",
                'website' => null,
                'avatar' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
