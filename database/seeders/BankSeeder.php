<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $banks = [
            ['name' => 'Banco de Oro (BDO)', 'code' => 'BDO'],
            ['name' => 'Bank of the Philippine Islands (BPI)', 'code' => 'BPI'],
            ['name' => 'Metropolitan Bank & Trust Company (Metrobank)', 'code' => 'MBTC'],
            ['name' => 'Land Bank of the Philippines (Landbank)', 'code' => 'LBP'],
            ['name' => 'Philippine National Bank (PNB)', 'code' => 'PNB'],
            ['name' => 'China Banking Corporation (China Bank)', 'code' => 'CHINABANK'],
            ['name' => 'Rizal Commercial Banking Corporation (RCBC)', 'code' => 'RCBC'],
            ['name' => 'Union Bank of the Philippines (UnionBank)', 'code' => 'UBP'],
            ['name' => 'Security Bank Corporation (Security Bank)', 'code' => 'SECB'],
            ['name' => 'Development Bank of the Philippines (DBP)', 'code' => 'DBP'],
            ['name' => 'EastWest Bank (EastWest)', 'code' => 'EWB'],
            ['name' => 'Asia United Bank Corporation (AUB)', 'code' => 'AUB'],
            ['name' => 'Philippine Bank of Communications (PBCOM)', 'code' => 'PBCOM'],
            ['name' => 'Robinsons Bank Corporation (Robinsons Bank)', 'code' => 'RBC'],
            ['name' => 'Maybank Philippines, Inc. (Maybank)', 'code' => 'MAYBANK'],
        ];

        foreach ($banks as $bank) {
            DB::table('banks')->insert([
                'name' => $bank['name'],
                'code' => $bank['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
