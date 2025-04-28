<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductVariationSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Create variations
        $variation1 = DB::table('product_variations')->insertGetId([
            'product_id' => DB::table('products')->where('name', "Men's T-Shirt")->value('id'),
            'unit_of_measure' => 'pcs',
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variation2 = DB::table('product_variations')->insertGetId([
            'product_id' => DB::table('products')->where('name', 'Paracetamol Tablet')->value('id'),
            'unit_of_measure' => 'pcs',
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variation3 = DB::table('product_variations')->insertGetId([
            'product_id' => DB::table('products')->where('name', 'iPhone 15')->value('id'),
            'unit_of_measure' => 'pcs',
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variation4 = DB::table('product_variations')->insertGetId([
            'product_id' => DB::table('products')->where('name', 'Flavored Milk')->value('id'),
            'unit_of_measure' => 'pcs',
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Assign attributes
        $attributes = [
            [$variation1, 'Size', ['S', 'M']],
            [$variation1, 'Color', ['Red', 'Blue']],
            [$variation2, 'Dosage', ['500mg']],
            [$variation2, 'Packaging', ['Strip']],
            [$variation3, 'Capacity', ['128GB', '256GB']],
            [$variation3, 'Color', ['Black', 'White']],
            [$variation4, 'Flavor', ['Strawberry', 'Vanilla']],
            [$variation4, 'Packaging', ['Bottle']],
        ];

        foreach ($attributes as [$variationId, $attributeName, $values]) {
            $attributeId = DB::table('attributes')->where('name', $attributeName)->value('id');

            foreach ($values as $value) {
                $valueId = DB::table('attribute_values')
                    ->where('value', $value)
                    ->where('attribute_id', $attributeId)
                    ->value('id');

                DB::table('product_variations')->insert([
                    'product_variation_id' => $variationId,
                    'attribute_id' => $attributeId,
                    'attribute_value_id' => $valueId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 3. Assign supplier prices
        $supplierMap = [
            [$variation1, 'Fresh Harvest Farms'],
            [$variation2, 'WellCare Pharmacy Supplies'],
            [$variation3, 'SmartTech World'],
            [$variation4, 'GoodBeans Coffee Co.'],
        ];

        foreach ($supplierMap as $index => [$variationId, $supplierName]) {
            DB::table('supplier_product_variations')->insert([
                'supplier_id' => DB::table('suppliers')->where('name', $supplierName)->value('id'),
                'product_variation_id' => $variationId,
                'sku' => 'SKU-' . ($index + 1),
                'barcode' => 'BAR-' . ($index + 1),
                'price' => 100 + ($index + 1) * 10,
                'cost' => 80 + ($index + 1) * 10,
                'lead_time_days' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
