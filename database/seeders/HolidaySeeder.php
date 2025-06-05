<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        {
            $year = now()->year;
    
            $holidays = [
                // Regular Holidays
                ['name' => 'New Year\'s Day', 'date' => "$year-01-01", 'type' => 'regular-holiday'],
                ['name' => 'Araw ng Kagitingan', 'date' => "$year-04-09", 'type' => 'regular-holiday'],
                ['name' => 'Labor Day', 'date' => "$year-05-01", 'type' => 'regular-holiday'],
                ['name' => 'Independence Day', 'date' => "$year-06-12", 'type' => 'regular-holiday'],
                ['name' => 'National Heroes Day', 'date' => Carbon::parse("last Monday of August $year")->toDateString(), 'type' => 'regular-holiday'],
                ['name' => 'Bonifacio Day', 'date' => "$year-11-30", 'type' => 'regular-holiday'],
                ['name' => 'Christmas Day', 'date' => "$year-12-25", 'type' => 'regular-holiday'],
                ['name' => 'Rizal Day', 'date' => "$year-12-30", 'type' => 'regular-holiday'],
    
                // Special Non-Working Holidays
                ['name' => 'Chinese New Year', 'date' => "$year-02-10", 'type' => 'special-non-working-holiday'], // Adjust based on lunar calendar
                ['name' => 'EDSA People Power Revolution Anniversary', 'date' => "$year-02-25", 'type' => 'special-non-working-holiday'],
                ['name' => 'Black Saturday', 'date' => "$year-03-30", 'type' => 'special-non-working-holiday'], // Adjust every year
                ['name' => 'Ninoy Aquino Day', 'date' => "$year-08-21", 'type' => 'special-non-working-holiday'],
                ['name' => 'All Saints’ Day', 'date' => "$year-11-01", 'type' => 'special-non-working-holiday'],
                ['name' => 'Feast of the Immaculate Conception', 'date' => "$year-12-08", 'type' => 'special-non-working-holiday'],
                ['name' => 'New Year’s Eve', 'date' => "$year-12-31", 'type' => 'special-non-working-holiday'],
    
                // Special Working Holidays (Examples)
                ['name' => 'Valentine\'s Day', 'date' => "$year-02-14", 'type' => 'special-working-holiday'],
                ['name' => 'April Fools\' Day', 'date' => "$year-04-01", 'type' => 'special-working-holiday'],
    
                // Observances (optional)
                ['name' => 'Earth Day', 'date' => "$year-04-22", 'type' => 'observance'],
            ];
    
            foreach ($holidays as $holiday) {
                DB::table('holidays')->updateOrInsert(
                    ['date' => $holiday['date']],
                    [
                        'name' => $holiday['name'],
                        'type' => $holiday['type'],
                        'description' => $holiday['name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
