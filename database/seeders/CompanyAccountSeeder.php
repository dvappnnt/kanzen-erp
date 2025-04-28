<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompanyAccountSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $companyAccounts = [
            [
                'bank_id' => 1,
                'company_id' => 1,
                'name' => 'Main Operating Account (BDO)',
                'number' => '1234567890',
                'type' => 'Checking',
                'status' => 'Active',
                'balance' => '1000000.00',
                'currency' => 'PHP',
            ],
            [
                'bank_id' => 1,
                'company_id' => 1,
                'name' => 'Payroll Account (BDO)',
                'number' => '0987654321',
                'type' => 'Savings',
                'status' => 'Active',
                'balance' => '500000.00',
                'currency' => 'PHP',
            ],
        ];

        foreach ($companyAccounts as $account) {
            DB::table('company_accounts')->insert([
                'bank_id' => $account['bank_id'],
                'company_id' => $account['company_id'],
                'name' => $account['name'],
                'number' => $account['number'],
                'type' => $account['type'],
                'status' => $account['status'],
                'balance' => $account['balance'],
                'currency' => $account['currency'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}