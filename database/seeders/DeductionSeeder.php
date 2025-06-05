<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $deductions = [
            [
                'name' => 'SSS',
                'description' => 'Social Security System',
                'is_mandatory' => true,
                'deduction_type' => 'percentage',
                'default_value' => 4.50,
            ],
            [
                'name' => 'PhilHealth',
                'description' => 'PhilHealth contribution',
                'is_mandatory' => true,
                'deduction_type' => 'percentage',
                'default_value' => 3.00,
            ],
            [
                'name' => 'Pag-IBIG',
                'description' => 'Pag-IBIG Fund contribution',
                'is_mandatory' => true,
                'deduction_type' => 'fixed',
                'default_value' => 100.00,
            ],
            [
                'name' => 'Cash Advance',
                'description' => 'Employee cash advance deduction',
                'is_mandatory' => false,
                'deduction_type' => 'fixed',
                'default_value' => null,
            ],
        ];

        foreach ($deductions as $deduction) {
            DB::table('deductions')->updateOrInsert(
                ['name' => $deduction['name']],
                array_merge($deduction, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
