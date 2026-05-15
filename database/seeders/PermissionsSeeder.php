<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Define Permissions
        $permissions = [
            // CRM
            ['permission_key' => 'customer.read',  'module' => 'CRM',        'description' => 'View customer list'],
            ['permission_key' => 'customer.write', 'module' => 'CRM',        'description' => 'Register new customers'],
            ['permission_key' => 'kyc.upload',     'module' => 'CRM',        'description' => 'Upload KYC documents'],
            ['permission_key' => 'kyc.verify',     'module' => 'Compliance', 'description' => 'Verify KYC status'],

            // Banking
            ['permission_key' => 'account.read',         'module' => 'Banking', 'description' => 'View account details'],
            ['permission_key' => 'account.write',        'module' => 'Banking', 'description' => 'Open new accounts'],
            ['permission_key' => 'transaction.deposit',  'module' => 'Banking', 'description' => 'Process deposits'],
            ['permission_key' => 'transaction.withdraw', 'module' => 'Banking', 'description' => 'Process withdrawals'],
            ['permission_key' => 'transaction.transfer', 'module' => 'Banking', 'description' => 'Process transfers'],
            ['permission_key' => 'vault.manage',         'module' => 'Banking', 'description' => 'Manage vault and cash allocation'],

            // Operations / Controls
            ['permission_key' => 'ledger.read',         'module' => 'Banking',    'description' => 'View branch dashboard and vault'],
            ['permission_key' => 'approvals.read',      'module' => 'Operations', 'description' => 'View approval queue'],
            ['permission_key' => 'approvals.write',     'module' => 'Operations', 'description' => 'Approve/Reject transactions'],
            ['permission_key' => 'compliance.check',    'module' => 'Compliance', 'description' => 'Perform AML/KYC checks'],
            ['permission_key' => 'reconciliation.read', 'module' => 'Accounting', 'description' => 'View GL and reconciliations'],

            // Branch Management
            ['permission_key' => 'branch.settings', 'module' => 'Branch', 'description' => 'Manage branch profile, hours, and configuration'],

            // System
            ['permission_key' => 'system.admin', 'module' => 'System', 'description' => 'Global HQ management'],
        ];

        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['permission_key' => $p['permission_key']],
                array_merge($p, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // 2. Map Permissions to Roles
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $keys = [];

            switch ($role->role_name) {

                case 'Customer Care Officer':
                    $keys = [
                        'customer.read', 'customer.write',
                        'kyc.upload',
                        'account.read', 'account.write',
                        'ledger.read',
                    ];
                    break;

                case 'Bank Teller':
                    $keys = [
                        'customer.read',
                        'account.read',
                        'transaction.deposit', 'transaction.withdraw', 'transaction.transfer',
                        'ledger.read',
                    ];
                    break;

                case 'Branch Manager':
                    // Full branch authority — every branch-level permission except system.admin
                    $keys = [
                        // CRM
                        'customer.read', 'customer.write',
                        'kyc.upload', 'kyc.verify',
                        // Banking
                        'account.read', 'account.write',
                        'transaction.deposit', 'transaction.withdraw', 'transaction.transfer',
                        'vault.manage',
                        'ledger.read',
                        // Operations
                        'approvals.read', 'approvals.write',
                        // Compliance
                        'compliance.check',
                        // Accounting
                        'reconciliation.read',
                        // Branch config
                        'branch.settings',
                    ];
                    break;

                case 'Compliance Officer':
                    $keys = ['customer.read', 'kyc.verify', 'compliance.check'];
                    break;

                case 'Accounting Officer':
                    $keys = ['reconciliation.read', 'ledger.read', 'account.read'];
                    break;

                case 'Teller Supervisor':
                    $keys = [
                        'customer.read',
                        'account.read',
                        'transaction.deposit', 'transaction.withdraw', 'transaction.transfer',
                        'approvals.read', 'approvals.write',
                        'vault.manage',
                        'ledger.read',
                    ];
                    break;

                case 'Super Admin':
                    $keys = ['system.admin'];
                    break;
            }

            if (!empty($keys)) {
                $permissionIds = DB::table('permissions')->whereIn('permission_key', $keys)->pluck('id');
                foreach ($permissionIds as $pId) {
                    DB::table('role_permissions')->updateOrInsert([
                        'role_id'       => $role->id,
                        'permission_id' => $pId,
                    ]);
                }
            }
        }
    }
}
