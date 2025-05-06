<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $banks = [
            [
                'code' => 'BDO',
                'name' => 'Banco de Oro (BDO)',
                'phone' => '(02) 8631-8000',
                'email' => 'callcenter@bdo.com.ph',
                'address' => '7899 Makati Avenue',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '0726',
                'website' => 'https://www.bdo.com.ph',
            ],
            [
                'code' => 'BPI',
                'name' => 'Bank of the Philippine Islands (BPI)',
                'phone' => '(02) 889-10000',
                'email' => 'expressonline@bpi.com.ph',
                'address' => '6768 Ayala Avenue',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '1226',
                'website' => 'https://www.bpi.com.ph',
            ],
            [
                'code' => 'MBTC',
                'name' => 'Metropolitan Bank & Trust Company (Metrobank)',
                'phone' => '(02) 8898-8000',
                'email' => 'customercare@metrobank.com.ph',
                'address' => 'Metrobank Plaza, Sen. Gil Puyat Ave.',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '1200',
                'website' => 'https://www.metrobank.com.ph',
            ],
            [
                'code' => 'LBP',
                'name' => 'Land Bank of the Philippines (Landbank)',
                'phone' => '(02) 8405-7000',
                'email' => 'customercare@mail.landbank.com',
                'address' => 'Landbank Plaza, 1598 M.H. Del Pilar St.',
                'city' => 'Malate, Manila',
                'state' => 'Metro Manila',
                'zip' => '1004',
                'website' => 'https://www.landbank.com',
            ],
            [
                'code' => 'PNB',
                'name' => 'Philippine National Bank (PNB)',
                'phone' => '(02) 8573-8888',
                'email' => 'customercare@pnb.com.ph',
                'address' => 'PNB Financial Center, Pres. Diosdado Macapagal Blvd.',
                'city' => 'Pasay City',
                'state' => 'Metro Manila',
                'zip' => '1300',
                'website' => 'https://www.pnb.com.ph',
            ],
            [
                'code' => 'CHINABANK',
                'name' => 'China Banking Corporation (China Bank)',
                'phone' => '(02) 8885-5555',
                'email' => 'customercare@chinabank.ph',
                'address' => '8745 Paseo de Roxas',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '1226',
                'website' => 'https://www.chinabank.ph',
            ],
            [
                'code' => 'RCBC',
                'name' => 'Rizal Commercial Banking Corporation (RCBC)',
                'phone' => '(02) 8888-1888',
                'email' => 'customercare@rcbc.com',
                'address' => 'RCBC Plaza, 6819 Ayala Ave.',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '0727',
                'website' => 'https://www.rcbc.com',
            ],
            [
                'code' => 'UBP',
                'name' => 'Union Bank of the Philippines (UnionBank)',
                'phone' => '(02) 8841-8600',
                'email' => 'customer.service@unionbankph.com',
                'address' => 'UnionBank Plaza, Meralco Avenue',
                'city' => 'Ortigas Center, Pasig City',
                'state' => 'Metro Manila',
                'zip' => '1605',
                'website' => 'https://www.unionbankph.com',
            ],
            [
                'code' => 'SECB',
                'name' => 'Security Bank Corporation (Security Bank)',
                'phone' => '(02) 8887-9188',
                'email' => 'customercare@securitybank.com.ph',
                'address' => '6776 Ayala Avenue',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '1226',
                'website' => 'https://www.securitybank.com',
            ],
            [
                'code' => 'DBP',
                'name' => 'Development Bank of the Philippines (DBP)',
                'phone' => '(02) 8818-9511',
                'email' => 'info@dbp.ph',
                'address' => 'Sen. Gil J. Puyat Avenue cor. Makati Ave.',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '1200',
                'website' => 'https://www.dbp.ph',
            ],
            [
                'code' => 'EWB',
                'name' => 'EastWest Bank (EastWest)',
                'phone' => '(02) 8888-1700',
                'email' => 'service@eastwestbanker.com',
                'address' => 'The Beaufort, 5th Ave. cor. 23rd St.',
                'city' => 'Bonifacio Global City, Taguig',
                'state' => 'Metro Manila',
                'zip' => '1634',
                'website' => 'https://www.eastwestbanker.com',
            ],
            [
                'code' => 'AUB',
                'name' => 'Asia United Bank Corporation (AUB)',
                'phone' => '(02) 8631-3333',
                'email' => 'customercare@aub.com.ph',
                'address' => 'AUB Bldg., 110 Paseo de Roxas',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '1226',
                'website' => 'https://www.aub.com.ph',
            ],
            [
                'code' => 'PBCOM',
                'name' => 'Philippine Bank of Communications (PBCOM)',
                'phone' => '(02) 8777-2266',
                'email' => 'customercare@pbcom.com.ph',
                'address' => 'PBCOM Tower, 6795 Ayala Ave.',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'zip' => '1226',
                'website' => 'https://www.pbcom.com.ph',
            ],
            [
                'code' => 'RBC',
                'name' => 'Robinsons Bank Corporation (Robinsons Bank)',
                'phone' => '(02) 8637-2273',
                'email' => 'customercare@robinsonsbank.com.ph',
                'address' => 'Robinsons Equitable Tower, ADB Ave.',
                'city' => 'Ortigas Center, Pasig City',
                'state' => 'Metro Manila',
                'zip' => '1605',
                'website' => 'https://www.robinsonsbank.com.ph',
            ],
            [
                'code' => 'MAYBANK',
                'name' => 'Maybank Philippines, Inc. (Maybank)',
                'phone' => '(02) 8588-3888',
                'email' => 'customer.service@maybank.com.ph',
                'address' => 'Maybank Corporate Centre, 7th Ave. cor. 28th St.',
                'city' => 'Bonifacio Global City, Taguig',
                'state' => 'Metro Manila',
                'zip' => '1634',
                'website' => 'https://www.maybank.com.ph',
            ],
        ];

        foreach ($banks as $bank) {
            DB::table('banks')->insert([
                ...$bank,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}