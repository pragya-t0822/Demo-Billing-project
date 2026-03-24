<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * RBAC Roles as defined in .claude/rules/security.md
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'SUPER_ADMIN'    => 'Full system access, multi-store management',
            'STORE_MANAGER'  => 'Full access within their assigned store(s)',
            'CASHIER'        => 'POS billing only — no reports or settings',
            'ACCOUNTANT'     => 'Accounting and reports — no POS',
            'AUDITOR'        => 'Read-only access to all financial records',
            'RECOVERY_AGENT' => 'Recovery module only',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        $this->command->info('✅ Roles created: ' . implode(', ', array_keys($roles)));
    }
}
