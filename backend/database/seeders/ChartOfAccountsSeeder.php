<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Standard Chart of Accounts for Indian Retail/Jewellery business.
     * Code ranges follow .claude/agents/accounting.agent.md specification.
     */
    public function run(): void
    {
        $accounts = [
            // ── ASSETS (1000–1999) ────────────────────────────────────────
            ['code' => '1100', 'name' => 'Cash in Hand',              'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => true],
            ['code' => '1110', 'name' => 'Bank Account',              'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => true],
            ['code' => '1120', 'name' => 'Payment Gateway Clearing',  'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => true],
            ['code' => '1200', 'name' => 'Accounts Receivable',       'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => true],
            ['code' => '1300', 'name' => 'Inventory Asset',           'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => true],
            ['code' => '1400', 'name' => 'GST Input Tax Credit',      'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => true],
            ['code' => '1500', 'name' => 'Prepaid Expenses',          'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '1600', 'name' => 'Fixed Assets (Gross)',      'type' => 'ASSET',   'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '1610', 'name' => 'Accumulated Depreciation',  'type' => 'ASSET',   'normal_balance' => 'CREDIT', 'is_system_account' => false],

            // ── LIABILITIES (2000–2999) ───────────────────────────────────
            ['code' => '2100', 'name' => 'Accounts Payable',          'type' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'is_system_account' => true],
            ['code' => '2200', 'name' => 'CGST Payable',              'type' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'is_system_account' => true],
            ['code' => '2210', 'name' => 'SGST Payable',              'type' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'is_system_account' => true],
            ['code' => '2220', 'name' => 'IGST Payable',              'type' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'is_system_account' => true],
            ['code' => '2300', 'name' => 'Customer Advances',         'type' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'is_system_account' => true],
            ['code' => '2400', 'name' => 'TDS Payable',               'type' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'is_system_account' => false],
            ['code' => '2500', 'name' => 'Bank Loan',                 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'is_system_account' => false],

            // ── EQUITY (3000–3999) ────────────────────────────────────────
            ['code' => '3000', 'name' => 'Owner Capital',             'type' => 'EQUITY',  'normal_balance' => 'CREDIT', 'is_system_account' => false],
            ['code' => '3100', 'name' => 'Retained Earnings',         'type' => 'EQUITY',  'normal_balance' => 'CREDIT', 'is_system_account' => false],
            ['code' => '3200', 'name' => 'Owner Drawings',            'type' => 'EQUITY',  'normal_balance' => 'DEBIT',  'is_system_account' => false],

            // ── REVENUE (4000–4999) ───────────────────────────────────────
            ['code' => '4000', 'name' => 'Sales Revenue',             'type' => 'REVENUE', 'normal_balance' => 'CREDIT', 'is_system_account' => true],
            ['code' => '4100', 'name' => 'Making Charges Income',     'type' => 'REVENUE', 'normal_balance' => 'CREDIT', 'is_system_account' => false],
            ['code' => '4200', 'name' => 'Other Income',              'type' => 'REVENUE', 'normal_balance' => 'CREDIT', 'is_system_account' => false],
            ['code' => '4900', 'name' => 'Sales Returns',             'type' => 'REVENUE', 'normal_balance' => 'DEBIT',  'is_system_account' => false],

            // ── COGS (5000–5999) ──────────────────────────────────────────
            ['code' => '5000', 'name' => 'Cost of Goods Sold',        'type' => 'COGS',    'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '5100', 'name' => 'Purchases',                 'type' => 'COGS',    'normal_balance' => 'DEBIT',  'is_system_account' => false],

            // ── EXPENSES (6000–6999) ──────────────────────────────────────
            ['code' => '6000', 'name' => 'Salaries & Wages',          'type' => 'EXPENSE', 'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '6100', 'name' => 'Gateway Fee Expense',       'type' => 'EXPENSE', 'normal_balance' => 'DEBIT',  'is_system_account' => true],
            ['code' => '6200', 'name' => 'Rent Expense',              'type' => 'EXPENSE', 'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '6300', 'name' => 'Utilities Expense',         'type' => 'EXPENSE', 'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '6400', 'name' => 'Depreciation Expense',      'type' => 'EXPENSE', 'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '6500', 'name' => 'Marketing Expense',         'type' => 'EXPENSE', 'normal_balance' => 'DEBIT',  'is_system_account' => false],
            ['code' => '6900', 'name' => 'Miscellaneous Expense',     'type' => 'EXPENSE', 'normal_balance' => 'DEBIT',  'is_system_account' => false],

            // ── TAX ACCOUNTS (7000–7999) ──────────────────────────────────
            ['code' => '7000', 'name' => 'Income Tax Provision',      'type' => 'TAX',     'normal_balance' => 'DEBIT',  'is_system_account' => false],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::firstOrCreate(
                ['code' => $account['code']],
                array_merge($account, ['is_active' => true])
            );
        }

        $this->command->info('✅ Chart of Accounts seeded: ' . count($accounts) . ' accounts.');
    }
}
