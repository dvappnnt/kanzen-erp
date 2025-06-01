<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductVariationAttribute;
use Carbon\Carbon;

class KanzenProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $productCategories = [
            'BATHROOM',
            'KITCHEN',
            'HOME SECURITY',
        ];

        foreach ($productCategories as $category) {
            DB::table('categories')->insert([
                'related_model' => 'products',
                'parent_id' => null,
                'name' => $category,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $productsByCategory = [
            'BATHROOM' => [
                'KANSO X1', 'KANSO X2', 'KANSO X3', 'KANSO X4', 'KANSO X5', 'KANSO X6', 'KANSO X7', 'KANSO X8',
                'KUMO G1', 'KUMO G2', 'SHIKISAI S', '019', '019GY', '019BK', '019PK', '019BL', '019Y', '019GR',
                '019OR', '032', '032BK', '032GY', '032Y', '032GR', '032PK', '032BL', '032OR', 'KHM-01', 'KHM-02',
                'KHM-03', 'KHS-01', 'KHS-02', 'KHS-03', 'KHS-04', 'KHS-05', 'KHS-05G', 'KHS-06', 'KHS-07',
                'KHS-1000B', 'KHS-1000C', 'KHS-1000G', 'KHS-1000GM', 'KHS-1000W', 'KHS-2000B', 'KHS-2000C',
                'KHS-2000G', 'KHS-2000GM', 'KHS-2000W', 'KHS-3000B', 'KHS-3000C', 'KHS-3000G', 'KHS-3000GM',
                'KHS-3000W', 'KHB-1000', 'KHB-2000', 'KHF-1000GM', 'KHF-1000B', 'KHF-1000G', 'KHF-2000C',
                'KHF-2000GM', 'KHF-2000RG', 'KHF-2000W', 'SB100-RG', 'SB100-GM', 'SB100-BG', 'SB100-CH',
                'KHF-200CH', 'KHF-200GM', 'KHF-200BL', 'KHF-200W', 'KHF-200G', 'KHF-201CH', 'KHF-201GM',
                'KHF-201BL', 'KHF-201W', 'KHF-202CH', 'KHF-202GM', 'KHF-202BL', 'KHF-202BG', 'KHF-202W',
                'KH-94103CH', 'KH-94103GM', 'KH-94103BG', 'KH-94103BL', 'KH-97003CH', 'KH-97003GM',
                'KH-97003BG', 'KH-97003BL', 'KH-97004CH', 'KH-97004GM', 'KH-97004BG', 'KH-97004BL',
                'KH-94109CH', 'KH-94109GM', 'KH-94109BG', 'KH-94109BL', 'KH-94107CH', 'KH-94107GM',
                'KH-94107BG', 'KH-94107BL', 'KH-97010CH', 'KH-97010GM', 'KH-97010BG', 'KH-97010BL',
                'KH-9411CH', 'KH-94112GM', 'KH-9411BG', 'KH-94112BL', 'KH-R335', 'A135', 'A855'
            ],
            'HOME SECURITY' => [
                'SENTINEL', 'KHL-2000', 'SECURE', 'SECURE PLUS', 'GLYDE PRO', 'KHL-8000', 'GATE SHEILD', 'GLASS GUARD'
            ],
            'KITCHEN' => [
                'KH-01B', 'KH-01G', 'KH-01W', 'KH-02', 'KH-03', 'KH-04', 'KH-05', 'KH-10B', 'KH-10W', 'KH-10O',
                'KH-11B', 'KH-11W', 'KH-11O', 'KH-12B', 'KH-12W', 'KH-12O', 'KHC-100SS', 'KHC-100GS', 'KHC-100R',
                'KHC-200SW', 'KHC-200GS', 'KHC-300SS', 'KHC-300GS', 'KHC-400SS', 'KHC-400GS', 'KHC-500SS',
                'KHC-500GS', 'KHC-501SS', 'KHC-600SS', 'KHC-600GS', 'KHC-800', 'KHC-900', 'KHC-1000B',
                'KHC-1000G', 'KHC-1000S', 'KHF-100CH', 'KHF-100BG', 'KHF-100BL', 'KHF-101CH', 'KHF-101GM',
                'KHF-101BL', 'KHF-101W', 'KHF-103GM', 'KHF-103BG', 'KHF-104CH', 'KHF-104GM', 'KHF-104BG',
                'SOAP DISPENSER', 'SMART BIN Z13', 'SMART BIN Z50'
            ],
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

                $product = Product::where('name', $productName)->first();

                $defaultVariation = $product->variations()->create([
                    'name' => 'Default Product Variation',
                    'is_default' => true,
                ]);

                $conditionAttributeId = DB::table('attributes')->where('name', 'Condition')->value('id');

                foreach (['New'] as $condition) {
                    ProductVariationAttribute::create([
                        'product_variation_id' => $defaultVariation->id,
                        'attribute_id' => $conditionAttributeId,
                        'attribute_value_id' => DB::table('attribute_values')->where('value', $condition)->value('id'),
                    ]);
                }
            }
        }
    }
}
