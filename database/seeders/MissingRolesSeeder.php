<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MissingRolesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $missing = [
            [
                'role_name'         => 'System Admin',
                'tier'              => 'System',
                'description'       => 'IT infrastructure and system configuration',
                'txn_limit'         => null,
                'branch_restricted' => false,
            ],
            [
                'role_name'         => 'Operational Manager',
                'tier'              => 'Operations',
                'description'       => 'Oversees daily operations across all branches',
                'txn_limit'         => null,
                'branch_restricted' => false,
            ],
            [
                'role_name'         => 'Loan Officer',
                'tier'              => 'Branch',
                'description'       => 'Reviews and processes loan applications',
                'txn_limit'         => null,
                'branch_restricted' => true,
            ],
            [
                'role_name'         => 'Internal Auditor',
                'tier'              => 'Operations',
                'description'       => 'Audits transactions and compliance records',
                'txn_limit'         => null,
                'branch_restricted' => false,
            ],
            [
                'role_name'         => 'Accounting Officer',
                'tier'              => 'Operations',
                'description'       => 'General ledger, trial balance and reconciliation',
                'txn_limit'         => null,
                'branch_restricted' => false,
            ],
        ];

        foreach ($missing as $role) {
            DB::table('roles')->updateOrInsert(
                ['role_name' => $role['role_name']],
                array_merge($role, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $this->command->info('Missing roles inserted.');

        // Re-run permissions seeder to map the new roles
        $this->call(PermissionsSeeder::class);
    }
}
