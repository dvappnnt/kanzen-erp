<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $productCategories = [
            'Food & Beverage' => ['Packaged Food', 'Fresh Produce', 'Frozen Goods', 'Beverages', 'Snacks'],
            'Clothing & Apparel' => ['Men’s Clothing', 'Women’s Clothing', 'Children’s Wear', 'Footwear', 'Accessories'],
            'Electronics' => ['Smartphones', 'Laptops', 'Home Appliances', 'Audio Devices', 'Wearables', 'Gaming Consoles', 'Gaming Accessories', 'Gaming Merchandise'],
            'Health & Personal Care' => ['Skincare', 'Supplements', 'Medical Supplies', 'Hygiene Products'],
            'Home & Living' => ['Furniture', 'Kitchenware', 'Bedding', 'Lighting', 'Home Decor'],
            'Automotive Parts' => ['Engine Components', 'Tires', 'Batteries', 'Accessories', 'Oils & Fluids'],
            'Industrial Supplies' => ['Machinery', 'Tools', 'Construction Materials', 'Electrical Equipment'],
            'Office & School Supplies' => ['Stationery', 'Office Furniture', 'Printers & Accessories', 'School Bags'],
            'Agricultural Products' => ['Fertilizers', 'Seeds', 'Farm Tools', 'Animal Feed'],
            'Sports & Outdoors' => ['Gym Equipment', 'Outdoor Gear', 'Sportswear', 'Bicycles'],
            'Beauty & Wellness' => ['Makeup', 'Haircare', 'Spa Tools', 'Essential Oils'],
            'Toys & Games' => ['Educational Toys', 'Board Games', 'Puzzles', 'Action Figures', 'PS5 Games', 'Xbox Games'],
            'Books & Media' => ['Fiction', 'Non-Fiction', 'Magazines', 'E-Books'],
            'Pet Supplies' => ['Pet Food', 'Toys & Accessories', 'Pet Grooming'],
            'Gadgets & Accessories' => ['Chargers', 'Power Banks', 'Phone Cases', 'Smart Home Devices'],
        ];

        foreach ($productCategories as $parent => $children) {
            $parentId = DB::table('categories')->insertGetId([
                'related_model' => 'products',
                'parent_id' => null,
                'name' => $parent,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($children as $child) {
                DB::table('categories')->insert([
                    'related_model' => 'products',
                    'parent_id' => $parentId,
                    'name' => $child,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
