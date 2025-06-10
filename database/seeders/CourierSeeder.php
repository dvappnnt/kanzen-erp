<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $couriers = [
            [
                'code' => 'LBC',
                'name' => 'LBC Express',
                'phone' => '02 8588 9999',
                'mobile' => '0917 559 9999',
                'email' => 'customerservice@lbcexpress.com',
                'address' => 'LBC Building, 1078 Chino Roces Ave, Makati City',
                'city' => 'Makati',
                'state' => 'Metro Manila',
                'zip' => '1231',
                'website' => 'https://www.lbcexpress.com',
                'avatar' => 'couriers/avatars/lbc.png',
            ],
            [
                'code' => 'J&T',
                'name' => 'J&T Express',
                'phone' => '02 8888 9999',
                'mobile' => '0917 888 8888',
                'email' => 'support@jtexpress.ph',
                'address' => 'J&T Express Building, Quezon City',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'zip' => '1100',
                'website' => 'https://www.jtexpress.ph',
                'avatar' => 'couriers/avatars/j&t.png',
            ],
            [
                'code' => 'NINJA',
                'name' => 'Ninja Van Philippines',
                'phone' => '02 8888 8888',
                'mobile' => '0917 777 7777',
                'email' => 'support@ninjavan.co',
                'address' => 'Ninja Van Building, Taguig City',
                'city' => 'Taguig',
                'state' => 'Metro Manila',
                'zip' => '1630',
                'website' => 'https://www.ninjavan.co',
                'avatar' => 'couriers/avatars/ninjavan.png',
            ],
            [
                'code' => 'LALAMOVE',
                'name' => 'Lalamove Philippines',
                'phone' => '02 8885 5266',
                'mobile' => '0917 123 4567',
                'email' => 'support.ph@lalamove.com',
                'address' => 'Lalamove HQ, Makati City',
                'city' => 'Makati',
                'state' => 'Metro Manila',
                'zip' => '1200',
                'website' => 'https://www.lalamove.com/ph',
                'avatar' => 'couriers/avatars/lalamove.png',
            ],
            [
                'code' => 'GRABEXP',
                'name' => 'GrabExpress',
                'phone' => '02 8837 9600',
                'mobile' => '0917 654 3210',
                'email' => 'support.ph@grab.com',
                'address' => 'Grab HQ, Bonifacio Global City',
                'city' => 'Taguig',
                'state' => 'Metro Manila',
                'zip' => '1634',
                'website' => 'https://www.grab.com/ph/express/',
                'avatar' => 'couriers/avatars/grabexpress.png',
            ],
        ];

        DB::table('couriers')->insert($couriers);
    }
}
