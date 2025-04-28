<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Rinvex\Country\CountryLoader;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Load country data from the JSON file
        $countries = CountryLoader::countries();

        foreach ($countries as $code => $country) {
            DB::table('countries')->insert([
                'code' => $code, // Country ISO 3166-1 alpha-2 code (e.g., "PH")
                'name' => $country['name'], // Common name (e.g., "Philippines")
                'official_name' => $country['official_name'], // Official name (e.g., "Republic of the Philippines")
                'native_name' => $country['native_name'], // Native name
                'native_official_name' => $country['native_official_name'], // Native official name
                'iso_3166_1_alpha2' => $country['iso_3166_1_alpha2'], // ISO alpha-2 code
                'iso_3166_1_alpha3' => $country['iso_3166_1_alpha3'], // ISO alpha-3 code
                'calling_code' => $country['calling_code'], // Calling code (e.g., "63")
                'currency' => $country['currency'], // Currency code (e.g., "PHP")
                'emoji' => $country['emoji'], // Emoji flag (e.g., "🇵🇭")
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
