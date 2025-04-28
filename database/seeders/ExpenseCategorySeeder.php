<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            'Utilities' => ['Electricity', 'Water', 'Internet', 'Telephone'],
            'Rentals' => ['Office Rent', 'Warehouse Rent', 'Equipment Lease'],
            'Salaries and Wages' => ['Employee Salaries', 'Overtime Pay', 'Bonuses'],
            'Transportation' => ['Fuel', 'Vehicle Maintenance', 'Delivery Expenses'],
            'Repairs and Maintenance' => ['Building Repairs', 'Equipment Repairs', 'IT Maintenance'],
            'Office Expenses' => ['Office Supplies', 'Printing and Stationery', 'Postage and Courier'],
            'Professional Fees' => ['Legal Fees', 'Accounting Services', 'Consulting Fees'],
            'Taxes and Licenses' => ['Business Permit', 'VAT Payments', 'Other Government Fees'],
            'Insurance' => ['Property Insurance', 'Vehicle Insurance', 'Health Insurance'],
            'Advertising and Marketing' => ['Online Advertising', 'Print Advertising', 'Promotional Events'],
            'Miscellaneous' => ['Other Expenses'],
        ];

        $parentIds = [];

        foreach ($categories as $parent => $children) {
            $parentId = DB::table('categories')->insertGetId([
                'related_model' => 'expenses',
                'parent_id' => null,
                'name' => $parent,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $parentIds[$parent] = $parentId;

            foreach ($children as $child) {
                DB::table('categories')->insert([
                    'related_model' => 'expenses',
                    'parent_id' => $parentId,
                    'name' => $child,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
