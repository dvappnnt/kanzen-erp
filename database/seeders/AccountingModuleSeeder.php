<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountType;
use App\Models\Account;
use App\Models\PaymentMethod;

class AccountingModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed account types
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

        // 2. Seed accounts
        $accounts = [
            // Assets
            ['name' => 'Cash',                     'code' => '1001', 'type' => 'AS'],
            ['name' => 'Accounts Receivable',      'code' => '1101', 'type' => 'AS'],
            ['name' => 'Inventory',                'code' => '1201', 'type' => 'AS'],
            ['name' => 'Prepaid Expenses',         'code' => '1301', 'type' => 'AS'],
            ['name' => 'Bank Account',             'code' => '1401', 'type' => 'AS'],
            ['name' => 'GCash Wallet',             'code' => '1402', 'type' => 'AS'],
            ['name' => 'Fixed Assets',             'code' => '1501', 'type' => 'AS'],
            ['name' => 'Accumulated Depreciation', 'code' => '1502', 'type' => 'AS'],

            // Liabilities
            ['name' => 'Accounts Payable',         'code' => '2001', 'type' => 'LI'],
            ['name' => 'Goods Receipt Not Invoiced (GRNI)', 'code' => '2002', 'type' => 'LI'],
            ['name' => 'Unearned Revenue',         'code' => '2101', 'type' => 'LI'],
            ['name' => 'Taxes Payable',            'code' => '2201', 'type' => 'LI'],
            ['name' => 'Credit Card Payable',      'code' => '2102', 'type' => 'LI'],
            ['name' => 'Checks Payable',           'code' => '2103', 'type' => 'LI'],

            // Equity
            ['name' => "Owner's Equity",           'code' => '3001', 'type' => 'EQ'],
            ['name' => 'Retained Earnings',        'code' => '3002', 'type' => 'EQ'],

            // Revenue
            ['name' => 'Sales Revenue',            'code' => '4001', 'type' => 'RE'],
            ['name' => 'Service Revenue',          'code' => '4002', 'type' => 'RE'],
            ['name' => 'Shipping Revenue',         'code' => '4003', 'type' => 'RE'],

            // Expenses and Contra Revenue
            ['name' => 'Salaries Expense',         'code' => '5001', 'type' => 'EX'],
            ['name' => 'Rent Expense',             'code' => '5002', 'type' => 'EX'],
            ['name' => 'Cost of Goods Sold (COGS)', 'code' => '5003', 'type' => 'EX'],
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

        // 3. Seed payment methods (after accounts exist)
        $methods = [
            ['name' => 'Cash',          'code' => 'cash'],
            ['name' => 'Bank Transfer', 'code' => 'bank-transfer'],
            ['name' => 'Credit Card',   'code' => 'credit-card'],
            ['name' => 'GCash',         'code' => 'gcash'],
            ['name' => 'Check',         'code' => 'check'],
            ['name' => 'Credit',        'code' => 'credit'], // 🆕 Added
        ];

        $accountMap = [
            'cash'          => 'Cash',
            'bank-transfer' => 'Bank Account',
            'credit-card'   => 'Credit Card Payable',
            'gcash'         => 'GCash Wallet',
            'check'         => 'Checks Payable',
            'credit'        => 'Accounts Receivable', // 🆕 Mapped
        ];

        foreach ($methods as $method) {
            $accountName = $accountMap[$method['code']] ?? ucfirst($method['name']);
            $account = Account::where('name', $accountName)->first();

            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'account_id' => $account?->id,
                ]
            );
        }
    }
}
