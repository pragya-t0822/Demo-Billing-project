<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FiscalPeriod;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultStoreAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default store
        /** @var Store $store */
        $store = Store::firstOrCreate(
            ['code' => 'STR-001'],
            [
                'name'               => 'Main Store',
                'gstin'              => '',
                'state'              => 'Maharashtra',
                'state_code'         => '27',
                'allow_negative_stock'=> false,
                'is_active'          => true,
            ]
        );

        // Create default SUPER_ADMIN user
        /** @var User $admin */
        $admin = User::firstOrCreate(
            ['email' => 'admin@retailflow.com'],
            [
                'name'          => 'System Admin',
                'password'      => Hash::make('password'),
                'employee_code' => 'EMP-001',
                'is_active'     => true,
            ]
        );

        $admin->assignRole('SUPER_ADMIN');
        $admin->stores()->syncWithoutDetaching([$store->id => ['is_primary' => true]]);

        // Create demo users for each role
        $demoUsers = [
            ['email' => 'manager@retailflow.com',  'name' => 'Store Manager',  'role' => 'STORE_MANAGER',  'emp' => 'EMP-002'],
            ['email' => 'cashier@retailflow.com',  'name' => 'Cashier',        'role' => 'CASHIER',        'emp' => 'EMP-003'],
            ['email' => 'accounts@retailflow.com', 'name' => 'Accountant',     'role' => 'ACCOUNTANT',     'emp' => 'EMP-004'],
            ['email' => 'auditor@retailflow.com',  'name' => 'Auditor',        'role' => 'AUDITOR',        'emp' => 'EMP-005'],
        ];

        foreach ($demoUsers as $u) {
            /** @var User $user */
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => Hash::make('password'), 'employee_code' => $u['emp'], 'is_active' => true]
            );
            $user->assignRole($u['role']);
            $user->stores()->syncWithoutDetaching([$store->id => ['is_primary' => true]]);
        }

        // Create current fiscal period (April 2026 – March 2027)
        FiscalPeriod::firstOrCreate(
            ['fiscal_year' => '2026-27', 'start_date' => '2026-04-01'],
            [
                'name'       => 'Apr 2026 – Mar 2027',
                'end_date'   => '2027-03-31',
                'status'     => 'OPEN',
            ]
        );

        // Create current month period
        FiscalPeriod::firstOrCreate(
            ['fiscal_year' => '2025-26', 'start_date' => '2025-04-01'],
            [
                'name'     => 'FY 2025-26',
                'end_date' => '2026-03-31',
                'status'   => 'OPEN',
            ]
        );

        $this->command->info("✅ Default store '{$store->name}' created.");
        $this->command->info('✅ Users: admin@retailflow.com / password (SUPER_ADMIN)');
        $this->command->info('✅ Demo users created: manager/cashier/accounts/auditor @retailflow.com / password');
        $this->command->info('✅ Fiscal periods created.');
    }
}
