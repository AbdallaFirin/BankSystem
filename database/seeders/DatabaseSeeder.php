<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Seed Branches
        $branchMain = DB::table('branches')->insertGetId([
            'branch_name'   => 'Mogadishu — Main Branch',
            'city'          => 'Mogadishu',
            'address'       => 'Makkah Al-Mukarramah Road',
            'phone'         => '+252 61 000 0001',
            'branch_code'   => 'MGD',
            'branch_number' => 101,
            'status'        => 'active',
            'opened_at'     => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $branchNugaal = DB::table('branches')->insertGetId([
            'branch_name'   => 'Garoowe — Nugaal Branch',
            'city'          => 'Garoowe',
            'address'       => 'Waberi District',
            'phone'         => '+252 61 000 0002',
            'branch_code'   => 'GRW',
            'branch_number' => 102,
            'status'        => 'active',
            'opened_at'     => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $branchBari = DB::table('branches')->insertGetId([
            'branch_name'   => 'Bosaso — Bari Branch',
            'city'          => 'Bosaso',
            'address'       => 'Port Road',
            'phone'         => '+252 61 000 0003',
            'branch_code'   => 'BSO',
            'branch_number' => 103,
            'status'        => 'active',
            'opened_at'     => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // 2. Seed Roles
        $superAdminRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Super Admin',
            'tier' => 'System',
            'description' => 'Full system control',
            'txn_limit' => null,
            'branch_restricted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $branchManagerRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Branch Manager',
            'tier' => 'Branch',
            'description' => 'Full branch authority',
            'txn_limit' => null,
            'branch_restricted' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tellerRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Bank Teller',
            'tier' => 'Front-line',
            'description' => 'Daily transactions',
            'txn_limit' => 5000.00,
            'branch_restricted' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $ccoRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Customer Care Officer',
            'tier' => 'Front-line',
            'description' => 'Customer registration and KYC',
            'txn_limit' => null,
            'branch_restricted' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $complianceRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Compliance Officer',
            'tier' => 'Operations',
            'description' => 'KYC and AML verification',
            'txn_limit' => null,
            'branch_restricted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $supervisorRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Teller Supervisor',
            'tier' => 'Branch',
            'description' => 'Approval of high-value transactions',
            'txn_limit' => 50000.00,
            'branch_restricted' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('roles')->insertGetId([
            'role_name' => 'System Admin',
            'tier' => 'System',
            'description' => 'IT infrastructure and system configuration',
            'txn_limit' => null,
            'branch_restricted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('roles')->insertGetId([
            'role_name' => 'Operational Manager',
            'tier' => 'Operations',
            'description' => 'Oversees daily operations across all branches',
            'txn_limit' => null,
            'branch_restricted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('roles')->insertGetId([
            'role_name' => 'Loan Officer',
            'tier' => 'Branch',
            'description' => 'Reviews and processes loan applications',
            'txn_limit' => null,
            'branch_restricted' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('roles')->insertGetId([
            'role_name' => 'Internal Auditor',
            'tier' => 'Operations',
            'description' => 'Audits transactions and compliance records',
            'txn_limit' => null,
            'branch_restricted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('roles')->insertGetId([
            'role_name' => 'Accounting Officer',
            'tier' => 'Operations',
            'description' => 'General ledger, trial balance and reconciliation',
            'txn_limit' => null,
            'branch_restricted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 5. Seed Super Admin Staff
        DB::table('staff')->updateOrInsert(
            ['ident_number' => 'ADM-001'],
            [
                'full_name'    => 'System Administrator',
                'email'        => 'abdallasport12@gmail.com',
                'ident_number' => 'ADM-001',
                'password'     => Hash::make('password'),
                'role_id'      => $superAdminRoleId,
                'branch_id'    => $branchMain,
                'status'       => 'active',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]
        );
        
        // 7. Seed Account Types
        DB::table('account_types')->insert([
            ['type_name' => 'Saving account', 'interest_rate' => 3.5, 'min_balance' => 0, 'overdraft_allowed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['type_name' => 'Current account', 'interest_rate' => 0, 'min_balance' => 0, 'overdraft_allowed' => true, 'created_at' => $now, 'updated_at' => $now],
            ['type_name' => 'Kaftoon account', 'interest_rate' => 0, 'min_balance' => 0, 'overdraft_allowed' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
        
        // 8. Seed Permissions & Role-Permission mappings
        $this->call(PermissionsSeeder::class);

        $this->command->info('Database seeded successfully.');
    }

    public function seedSar(): void
    {
        $this->call(SarSeeder::class);
    }
    }

