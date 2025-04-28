<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $industries = [
            'Food & Business' => ['Restaurant', 'Bar', 'Catering Services', 'Bakery'],
            'Manufacturing' => ['Clothing Factory', 'Electronics Assembly', 'Furniture Manufacturing', 'Automobile Parts'],
            'Technology' => ['Software Development', 'IT Services', 'Cybersecurity', 'Data Analytics'],
            'Healthcare' => ['Hospital', 'Pharmacy', 'Medical Equipment Supplier', 'Clinic'],
            'Construction' => ['Residential Building', 'Commercial Building', 'Infrastructure', 'Interior Design'],
            'Education' => ['School', 'Training Center', 'E-Learning Platform'],
            'Retail & E-Commerce' => ['Clothing Store', 'Electronics Store', 'Online Marketplace', 'Supermarket'],
            'Transportation & Logistics' => ['Trucking', 'Courier Services', 'Freight Forwarding', 'Warehouse Management'],
            'Finance' => ['Bank', 'Insurance Company', 'Lending Services', 'Fintech'],
            'Real Estate' => ['Residential Sales', 'Commercial Leasing', 'Property Management'],
            'Agriculture' => ['Farming', 'Poultry', 'Aquaculture', 'Agritech'],
            'Energy' => ['Renewable Energy', 'Oil & Gas', 'Power Distribution'],
            'Entertainment' => ['Event Organizer', 'Media Production', 'Talent Agency'],
            'Hospitality' => ['Hotel', 'Resort', 'Travel Agency'],
            'Professional Services' => ['Legal Services', 'Accounting Firm', 'Consulting Agency'],
            'Beauty & Wellness' => ['Salon', 'Spa', 'Fitness Center'],
            'Automotive' => ['Car Dealership', 'Auto Repair', 'Car Rental'],
            'Telecommunications' => ['Mobile Network', 'Internet Provider'],
            'Government & NGO' => ['Local Government', 'Non-Profit Organization', 'Charity'],
        ];

        $parentIds = [];

        foreach ($industries as $parent => $children) {
            $parentId = DB::table('categories')->insertGetId([
                'related_model' => 'suppliers',
                'parent_id' => null,
                'name' => $parent,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $parentIds[$parent] = $parentId;

            foreach ($children as $child) {
                DB::table('categories')->insert([
                    'related_model' => 'suppliers',
                    'parent_id' => $parentId,
                    'name' => $child,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
