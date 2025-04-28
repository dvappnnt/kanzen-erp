<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $productsByCategory = [
            'Men’s Clothing' => ["Men's T-Shirt", "Men's Jeans", "Formal Shirt"],
            'Medical Supplies' => ["Paracetamol Tablet", "Surgical Mask", "Digital Thermometer"],
            'Smartphones' => ["iPhone 15", "Samsung Galaxy S23", "Xiaomi Redmi Note 12"],
            'Beverages' => ["Flavored Milk", "Canned Soda", "Bottled Water"],
        ];

        foreach ($productsByCategory as $categoryName => $products) {
            $category = DB::table('categories')->where('name', $categoryName)->first();

            if (!$category) continue;

            foreach ($products as $productName) {
                DB::table('products')->insert([
                    'slug' => Str::slug($productName),
                    'token' => Str::random(64),
                    'company_id' => 1,
                    'category_id' => $category->id,
                    'name' => $productName,
                    'description' => null,
                    'avatar' => null,
                    'unit_of_measure' => 'pcs',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        /* Datablitz Products */
        $datablitzProductsByCategory = [
            'Gaming Consoles' => ["PS5", "Xbox Series X", "Nintendo Switch"],
            'Gaming Accessories' => ["Nintendo Switch Pro Controller", "Playstation DualSense", "Xbox Wireless Controller"],
            'Gaming Merchandise' => ["Nintendo T-Shirt", "Xbox Hoodie", "Playstation Backpack"],
            'PS5 Games' => ["God of War", "Uncharted", "Horizon Zero Dawn"],
            'Xbox Games' => ["Halo", "Gears of War", "Forza"],
        ];

        foreach ($datablitzProductsByCategory as $categoryName => $products) {
            $category = DB::table('categories')->where('name', $categoryName)->first();

            if (!$category) continue;

            foreach ($products as $productName) {
                DB::table('products')->insert([
                    'slug' => Str::slug($productName),
                    'token' => Str::random(64),
                    'company_id' => 2,
                    'category_id' => $category->id,
                    'name' => $productName,
                    'description' => null,
                    'avatar' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        /* Datablitz Products */
    }
}
