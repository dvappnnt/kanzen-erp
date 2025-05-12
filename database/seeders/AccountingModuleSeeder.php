<?php

namespace Database\Seeders;

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
            // Assets
            ['name' => 'Cash',                     'code' => '1001', 'type' => 'AS'],
            ['name' => 'Accounts Receivable',      'code' => '1101', 'type' => 'AS'],
            ['name' => 'Inventory',                'code' => '1201', 'type' => 'AS'],
            ['name' => 'Prepaid Expenses',         'code' => '1301', 'type' => 'AS'],
            ['name' => 'Bank Account',             'code' => '1401', 'type' => 'AS'],
            ['name' => 'Fixed Assets',             'code' => '1501', 'type' => 'AS'],
            ['name' => 'Accumulated Depreciation', 'code' => '1502', 'type' => 'AS'],

            // Liabilities
            ['name' => 'Accounts Payable',         'code' => '2001', 'type' => 'LI'],
            ['name' => 'Goods Receipt Not Invoiced (GRNI)', 'code' => '2002', 'type' => 'LI'],
            ['name' => 'Unearned Revenue',         'code' => '2101', 'type' => 'LI'],
            ['name' => 'Taxes Payable',            'code' => '2201', 'type' => 'LI'],

            // Equity
            ['name' => 'Owner’s Equity',           'code' => '3001', 'type' => 'EQ'],
            ['name' => 'Retained Earnings',        'code' => '3002', 'type' => 'EQ'],

            // Revenue
            ['name' => 'Sales Revenue',            'code' => '4001', 'type' => 'RE'],
            ['name' => 'Service Revenue',          'code' => '4002', 'type' => 'RE'],

            // Expenses
            ['name' => 'Salaries Expense',         'code' => '5001', 'type' => 'EX'],
            ['name' => 'Rent Expense',             'code' => '5002', 'type' => 'EX'],
            ['name' => 'Cost of Goods Sold (COGS)','code' => '5003', 'type' => 'EX'],
            ['name' => 'Sales Discounts',          'code' => '5004', 'type' => 'EX'],
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
