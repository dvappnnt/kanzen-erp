<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $exists = DB::table('settings')
            ->where('group', 'app')
            ->where('name', 'receive_with_serial')
            ->exists();

        if (! $exists) {
            DB::table('settings')->insert([
                'group' => 'app',
                'name' => 'receive_with_serial',
                'payload' => json_encode(false),
                'locked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
