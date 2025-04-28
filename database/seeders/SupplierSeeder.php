<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $suppliers = [
            [
                'created_by_user_id' => 1,
                'country_id' => 177,
                'name' => 'Fresh Harvest Farms',
                'email' => 'contact@freshharvest.ph',
                'mobile' => '0917 222 1234',
                'address' => 'La Trinidad, Benguet',
                'description' => 'Supplier of high-quality organic vegetables and fresh produce directly from farmers.',
                'website' => 'https://freshharvest.ph',
                'category' => 'Farming',
            ],
            [
                'created_by_user_id' => 1,
                'country_id' => 177,
                'name' => 'SteelMax Industries',
                'email' => 'sales@steelmax.com',
                'mobile' => '0918 500 9922',
                'address' => 'Valenzuela City',
                'description' => 'Philippines-based steel manufacturer offering custom metal fabrication and construction supplies.',
                'website' => 'https://steelmax.ph',
                'category' => 'Construction',
            ],
            [
                'created_by_user_id' => 1,
                'country_id' => 177,
                'name' => 'GoodBeans Coffee Co.',
                'email' => 'brew@goodbeans.coffee',
                'mobile' => '0928 887 1122',
                'address' => 'Tagaytay City',
                'description' => 'Local coffee roaster providing high-quality beans to cafes and coffee chains.',
                'website' => 'https://goodbeans.coffee',
                'category' => 'Restaurant',
            ],
            [
                'created_by_user_id' => 1,
                'country_id' => 177,
                'name' => 'WellCare Pharmacy Supplies',
                'email' => 'orders@wellcarepharma.ph',
                'mobile' => '0999 884 2211',
                'address' => 'San Juan City',
                'description' => 'Distributor of pharmaceutical and medical supplies to clinics and hospitals nationwide.',
                'website' => 'https://wellcarepharma.ph',
                'category' => 'Pharmacy',
            ],
            [
                'created_by_user_id' => 1,
                'country_id' => 177,
                'name' => 'SmartTech World',
                'email' => 'orders@smartechworld.ph',
                'mobile' => '0999 884 2211',
                'address' => 'San Juan City',
                'description' => 'Distributor of pharmaceutical and medical supplies to clinics and hospitals nationwide.',
                'website' => 'https://smartechworld.ph',
                'category' => 'Electronics Assembly',
            ],
            [
                'company_id' => 2,
                'created_by_user_id' => 2,
                'country_id' => 177,
                'name' => 'GameSource Asia',
                'email' => 'sales@gamesource.asia',
                'mobile' => '0917 888 7788',
                'address' => 'Pasig City, Metro Manila',
                'description' => 'Leading supplier of imported video games, consoles, and collectibles catering to retailers across Southeast Asia.',
                'website' => 'https://gamesource.asia',
                'category' => 'Game Distribution',
            ],
            [
                'company_id' => 2,
                'created_by_user_id' => 2,
                'country_id' => 177,
                'name' => 'PixelPlay Distributors',
                'email' => 'orders@pixelplay.ph',
                'mobile' => '0922 456 7890',
                'address' => 'Taguig City, Metro Manila',
                'description' => 'Official distributor of top console brands including PlayStation, Xbox, and Nintendo in the Philippines.',
                'website' => 'https://pixelplay.ph',
                'category' => 'Electronics and Gaming',
            ],
            [
                'company_id' => 2,
                'created_by_user_id' => 2,
                'country_id' => 177,
                'name' => 'NextGen Gaming Supply Co.',
                'email' => 'partners@nextgengaming.ph',
                'mobile' => '0919 300 2200',
                'address' => 'Cebu City, Cebu',
                'description' => 'Supplies gaming accessories and esports peripherals to tech retailers nationwide.',
                'website' => 'https://nextgengaming.ph',
                'category' => 'Gaming Accessories',
            ],
            [
                'company_id' => 2,
                'created_by_user_id' => 2,
                'country_id' => 177,
                'name' => 'Bytewave Technologies',
                'email' => 'sales@bytewave.ph',
                'mobile' => '0947 112 3344',
                'address' => 'Davao City, Davao del Sur',
                'description' => 'Wholesaler of handheld consoles and digital game codes for PC and console platforms.',
                'website' => 'https://bytewave.ph',
                'category' => 'Digital Game Distribution',
            ],
        ];

        foreach ($suppliers as $supplier) {
            $categoryId = DB::table('categories')
                ->where('name', $supplier['category'])
                ->where('related_model', 'suppliers')
                ->value('id');

            DB::table('suppliers')->insert([
                'token' => Str::random(64),
                'slug' => Str::slug($supplier['name']),
                'created_by_user_id' => $supplier['created_by_user_id'],
                'country_id' => $supplier['country_id'],
                'name' => $supplier['name'],
                'email' => $supplier['email'],
                'mobile' => $supplier['mobile'],
                'address' => $supplier['address'],
                'description' => $supplier['description'],
                'website' => $supplier['website'],
                'category_id' => $categoryId,
                'avatar' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
