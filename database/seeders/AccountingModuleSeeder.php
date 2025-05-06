<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccountType;
use App\Models\Account;

class AccountingModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Asset',     'code' => 'AS'],
            ['name' => 'Liability', 'code' => 'LI'],
            ['name' => 'Equity',    'code' => 'EQ'],
            ['name' => 'Revenue',   'code' => 'RE'],
            ['name' => 'Expense',   'code' => 'EX'],
        ];

        foreach ($types as $type) {
            AccountType::updateOrCreate(['code' => $type['code']], $type);
        }

        $accounts = [
            ['name' => 'Cash',               'code' => '1001', 'type' => 'AS'],
            ['name' => 'Accounts Receivable', 'code' => '1101', 'type' => 'AS'],
            ['name' => 'Accounts Payable',   'code' => '2001', 'type' => 'LI'],
            ['name' => 'Owner’s Equity',     'code' => '3001', 'type' => 'EQ'],
            ['name' => 'Sales Revenue',      'code' => '4001', 'type' => 'RE'],
            ['name' => 'Service Revenue',    'code' => '4002', 'type' => 'RE'],
            ['name' => 'Salaries Expense',   'code' => '5001', 'type' => 'EX'],
            ['name' => 'Rent Expense',       'code' => '5002', 'type' => 'EX'],
        ];

        foreach ($accounts as $account) {
            $type = AccountType::where('code', $account['type'])->first();
            Account::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'account_type_id' => $type?->id,
                    'is_active' => true
                ]
            );
        }
    }
}
