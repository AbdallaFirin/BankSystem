<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Account;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\AccountType;
use App\Models\Role;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $branchCount = Branch::count();
        $staffCount = Staff::count();
        $customerCount = Customer::count();
        $pendingTxCount = Transaction::where('status', 'pending_approval')->count();
        
        $totalAssets = DB::table('ledger_entries')
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.status', 'active')
            ->selectRaw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END) as net")
            ->value('net') ?? 0;

        $branches = Branch::withCount(['staff', 'accounts'])->get();

        return \Inertia\Inertia::render('Staff/Admin/Dashboard', [
            'metrics' => [
                'branches' => $branchCount,
                'staff' => $staffCount,
                'customers' => $customerCount,
                'pending_approvals' => $pendingTxCount,
                'total_assets' => $totalAssets,
            ],
            'branches' => $branches
        ]);
    }

    public function staffIndex()
    {
        $staff = Staff::with(['branch', 'role'])
            ->orderBy('branch_id')
            ->orderBy('role_id')
            ->get();

        $roles = \App\Models\Role::all();
        $branches = Branch::all();

        return \Inertia\Inertia::render('Staff/Admin/StaffIndex', [
            'staff' => $staff,
            'roles' => $roles,
            'branches' => $branches
        ]);
    }

    /**
     * Generate a unique Staff ID based on the branch.
     * Format: [First 3 letters of branch name in UPPERCASE][3-digit sequence starting at 120]
     * e.g., First staff at Carmo Branch → CAR120, second → CAR121, etc.
     */
    private function generateStaffId(int $branchId): string
    {
        $branch = Branch::findOrFail($branchId);
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $branch->branch_name), 0, 3));

        $existingCount = Staff::where('branch_id', $branchId)->count();
        $sequence = 120 + $existingCount;

        // Ensure uniqueness in case of gaps/deletions
        while (Staff::where('ident_number', $prefix . $sequence)->exists()) {
            $sequence++;
        }

        return $prefix . $sequence;
    }

    public function storeStaff(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:staff,email',
            'phone'     => 'required|string|unique:staff,phone',
            'password'  => 'required|min:8',
            'role_id'   => 'required|exists:roles,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $staffId = $this->generateStaffId((int)$request->branch_id);

        Staff::create([
            'full_name'             => $request->full_name,
            'email'                 => $request->email,
            'phone'                 => $request->phone,
            'password'              => \Illuminate\Support\Facades\Hash::make($request->password),
            'role_id'               => $request->role_id,
            'branch_id'             => $request->branch_id,
            'ident_number'          => $staffId,
            'status'                => 'active',
            'force_password_change' => true,
            'temp_password'         => $request->password,   // plain-text; wiped on first self-change
        ]);

        return back()->with('success', "Staff account for {$request->full_name} created. Login ID: {$staffId}");
    }

    /**
     * Preview what Staff ID would be generated for a given branch.
     * Used by the frontend form to show a live ID preview.
     */
    public function previewStaffId(Request $request)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);
        return response()->json([
            'staff_id' => $this->generateStaffId((int)$request->branch_id)
        ]);
    }

    public function branchIndex()
    {
        $branches = Branch::with('manager')
            ->withCount(['staff', 'accounts'])
            ->addSelect(['total_balance' => DB::table('ledger_entries')
                ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
                ->whereColumn('accounts.home_branch_id', 'branches.id')
                ->selectRaw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END)")
            ])
            ->get();

        $allStaff = Staff::select('id', 'full_name', 'branch_id')->orderBy('full_name')->get();

        return \Inertia\Inertia::render('Staff/Admin/Branches', [
            'branches' => $branches,
            'allStaff' => $allStaff,
        ]);
    }

    public function storeBranch(Request $request)
    {
        $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:10|unique:branches,branch_code|regex:/^[A-Z0-9]+$/',
            'city'        => 'required|string|max:255',
            'address'     => 'required|string',
            'phone'       => 'required|string',
        ]);

        // Auto-generate the next branch_number: max existing + 1, floor at 101.
        $nextNumber = Branch::max('branch_number');
        $nextNumber = $nextNumber ? $nextNumber + 1 : 101;

        $branch = Branch::create(
            $request->only('branch_name', 'branch_code', 'city', 'address', 'phone')
            + ['branch_number' => $nextNumber, 'status' => 'active', 'opened_at' => now()]
        );

        $fullCode = ($branch->branch_code ?? '') . $branch->branch_number;
        return back()->with('success', "Branch {$branch->branch_name} established — Code: {$fullCode}");
    }

    public function roleIndex()
    {
        $roles = \App\Models\Role::with('permissions')->get();
        $allPermissions = \App\Models\Permission::all()->groupBy('module');

        return \Inertia\Inertia::render('Staff/Admin/Roles', [
            'roles' => $roles,
            'allPermissions' => $allPermissions
        ]);
    }

    public function updateRolePermissions(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'required|array',
        ]);

        $role = \App\Models\Role::findOrFail($request->role_id);
        
        // Sync role permissions
        DB::table('role_permissions')->where('role_id', $role->id)->delete();
        
        foreach ($request->permissions as $pId) {
            DB::table('role_permissions')->insert([
                'role_id' => $role->id,
                'permission_id' => $pId
            ]);
        }

        return back()->with('success', "Permissions for {$role->role_name} have been updated.");
    }

    public function updateStaff(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => "required|email|unique:staff,email,{$id}",
            'phone'     => "required|string|unique:staff,phone,{$id}",
            'role_id'   => 'required|exists:roles,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $staff->update($request->only('full_name', 'email', 'phone', 'role_id', 'branch_id'));

        return back()->with('success', "Staff profile for {$staff->full_name} updated.");
    }

    public function resetStaffPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $staff = Staff::findOrFail($id);
        $staff->update([
            'password'              => Hash::make($request->password),
            'force_password_change' => true,
            'temp_password'         => $request->password,
        ]);

        return back()->with('success', "Password for {$staff->full_name} has been reset. They will be required to change it on next login.");
    }

    public function updateStaffStatus(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $newStatus = $request->status === 'active' ? 'active' : 'inactive';

        $staff->update(['status' => $newStatus]);

        return back()->with('success', "Staff account for {$staff->full_name} is now {$newStatus}.");
    }

    public function auditIndex()
    {
        $logs = \App\Models\StaffAuditLog::with(['staff', 'branch'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return \Inertia\Inertia::render('Staff/Admin/AuditLogs', [
            'logs' => $logs
        ]);
    }

    public function settingsIndex()
    {
        $accountTypes = AccountType::all();
        $rolesWithLimits = Role::all(['id', 'role_name', 'txn_limit']);

        return \Inertia\Inertia::render('Staff/Admin/Settings', [
            'accountTypes' => $accountTypes,
            'roles' => $rolesWithLimits
        ]);
    }

    // -------------------------------------------------------------------------
    // Branch Management
    // -------------------------------------------------------------------------

    public function updateBranch(Request $request, $id)
    {
        $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_code' => "nullable|string|max:10|unique:branches,branch_code,{$id}|regex:/^[A-Z0-9]+$/",
            'city'        => 'required|string|max:255',
            'address'     => 'required|string',
            'phone'       => 'required|string',
        ]);

        $branch      = Branch::findOrFail($id);
        $oldFullCode = $branch->full_code; // e.g. "101" (before prefix set)

        // branch_number is never changed — only the prefix can be updated.
        $branch->update($request->only('branch_name', 'branch_code', 'city', 'address', 'phone'));
        $branch->refresh();

        $newFullCode = $branch->full_code; // e.g. "MOG101" (after prefix set)

        // ── Auto-reformat existing account numbers when the prefix changes ──
        // e.g. "101000001" → "MOG101000001", "BOS106000001" → "MOG106000001"
        if ($newFullCode !== $oldFullCode && $newFullCode !== '') {
            $accounts = Account::where('home_branch_id', $branch->id)
                ->where('account_number', 'not like', 'VAULT-%')
                ->where('account_number', 'not like', 'TILL-%')
                ->orderBy('id')
                ->get(['id', 'account_number']);

            $seq = 1;
            foreach ($accounts as $account) {
                $newNumber = $newFullCode . str_pad($seq, 6, '0', STR_PAD_LEFT);
                // Collision guard
                while (Account::where('account_number', $newNumber)
                               ->where('id', '!=', $account->id)->exists()) {
                    $seq++;
                    $newNumber = $newFullCode . str_pad($seq, 6, '0', STR_PAD_LEFT);
                }
                $account->update(['account_number' => $newNumber]);
                $seq++;
            }
        }

        return back()->with('success', "Branch {$branch->branch_name} updated — Code: {$newFullCode}");
    }

    public function updateBranchStatus(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
        $branch->update(['status' => $newStatus]);

        return back()->with('success', "Branch {$branch->branch_name} is now {$newStatus}.");
    }

    // -------------------------------------------------------------------------
    // Role Management
    // -------------------------------------------------------------------------

    public function storeRole(Request $request)
    {
        $request->validate([
            'role_name'   => 'required|string|max:255|unique:roles,role_name',
            'tier'        => 'required|in:System,Branch,Staff',
            'description' => 'nullable|string',
            'txn_limit'   => 'nullable|numeric|min:0',
        ]);

        $role = Role::create([
            'role_name'         => $request->role_name,
            'tier'              => $request->tier,
            'description'       => $request->description,
            'txn_limit'         => $request->txn_limit,
            'branch_restricted' => $request->tier !== 'System',
        ]);

        return back()->with('success', "Role {$role->role_name} created successfully.");
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role_name'   => "required|string|max:255|unique:roles,role_name,{$id}",
            'description' => 'nullable|string',
            'txn_limit'   => 'nullable|numeric|min:0',
        ]);

        $role = Role::findOrFail($id);
        $role->update($request->only('role_name', 'description', 'txn_limit'));

        return back()->with('success', "Role {$role->role_name} updated.");
    }

    // -------------------------------------------------------------------------
    // Settings — Account Types & Role Limits
    // -------------------------------------------------------------------------

    public function storeAccountType(Request $request)
    {
        $request->validate([
            'type_name'        => 'required|string|max:255|unique:account_types,type_name',
            'interest_rate'    => 'required|numeric|min:0',
            'min_balance'      => 'required|numeric|min:0',
            'withdrawal_limit' => 'nullable|numeric|min:0',
            'overdraft_allowed'=> 'boolean',
            'overdraft_limit'  => 'nullable|numeric|min:0',
        ]);

        AccountType::create([
            'type_name'         => $request->type_name,
            'interest_rate'     => $request->interest_rate,
            'min_balance'       => $request->min_balance,
            'withdrawal_limit'  => $request->withdrawal_limit,
            'overdraft_allowed' => $request->boolean('overdraft_allowed'),
            'overdraft_limit'   => $request->boolean('overdraft_allowed') ? $request->overdraft_limit : null,
            'is_active'         => true,
        ]);

        return back()->with('success', "Account product '{$request->type_name}' added.");
    }

    public function updateAccountType(Request $request, $id)
    {
        $request->validate([
            'type_name'        => "required|string|max:255|unique:account_types,type_name,{$id}",
            'interest_rate'    => 'required|numeric|min:0',
            'min_balance'      => 'required|numeric|min:0',
            'withdrawal_limit' => 'nullable|numeric|min:0',
            'overdraft_allowed'=> 'boolean',
            'overdraft_limit'  => 'nullable|numeric|min:0',
        ]);

        $type = AccountType::findOrFail($id);
        $type->update([
            'type_name'         => $request->type_name,
            'interest_rate'     => $request->interest_rate,
            'min_balance'       => $request->min_balance,
            'withdrawal_limit'  => $request->withdrawal_limit,
            'overdraft_allowed' => $request->boolean('overdraft_allowed'),
            'overdraft_limit'   => $request->boolean('overdraft_allowed') ? $request->overdraft_limit : null,
        ]);

        return back()->with('success', "Account product '{$type->type_name}' updated.");
    }

    public function updateRoleLimit(Request $request, $id)
    {
        $request->validate([
            'txn_limit' => 'nullable|numeric|min:0',
        ]);

        $role = Role::findOrFail($id);
        $role->update(['txn_limit' => $request->txn_limit ?: null]);

        return back()->with('success', "Transaction limit for {$role->role_name} updated.");
    }

    // -------------------------------------------------------------------------
    // Customer Management
    // -------------------------------------------------------------------------

    public function customerIndex(\Illuminate\Http\Request $request)
    {
        $query = Customer::with('branch')
            ->where('phone', 'not like', 'SYS-%');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($kyc = $request->kyc_status) {
            $query->where('kyc_status', $kyc);
        }

        if ($branch = $request->branch_id) {
            $query->where('home_branch_id', $branch);
        }

        $customers = $query->orderBy('full_name')->paginate(25)->withQueryString();
        $branches  = \App\Models\Branch::orderBy('branch_name')->get(['id', 'branch_name']);

        return \Inertia\Inertia::render('Staff/Admin/Customers', [
            'customers' => $customers,
            'branches'  => $branches,
            'filters'   => $request->only('search', 'kyc_status', 'branch_id'),
        ]);
    }

    public function updateCustomerStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,suspended,closed',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update(['status' => $request->status]);

        return back()->with('success', "Customer {$customer->full_name} status set to {$request->status}.");
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/admin/portal-access
     *  Show how many verified customers have no password yet
     * ───────────────────────────────────────────── */
    public function portalAccessIndex()
    {
        $base = Customer::where('kyc_status', 'verified')
            ->where('phone', 'not like', 'SYS-%');

        $withoutAccess = (clone $base)->whereNull('password')->count();
        $totalVerified = (clone $base)->count();
        $withoutEmail  = (clone $base)->whereNull('password')->whereNull('email')->count();

        return \Inertia\Inertia::render('Staff/Admin/PortalAccess', [
            'without_access' => $withoutAccess,
            'total_verified' => $totalVerified,
            'without_email'  => $withoutEmail,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/admin/portal-access/bulk-send
     *  Generate temp passwords for all verified customers who have none
     * ───────────────────────────────────────────── */
    public function bulkSendPortalAccess()
    {
        $customers = Customer::where('kyc_status', 'verified')
            ->where('phone', 'not like', 'SYS-%')
            ->whereNull('password')
            ->get();

        if ($customers->isEmpty()) {
            return back()->with('success', 'All verified customers already have portal access.');
        }

        $notificationService = app(NotificationService::class);
        $sent      = 0;
        $noEmail   = 0;

        foreach ($customers as $customer) {
            $birthYear    = $customer->dob ? date('Y', strtotime($customer->dob)) : date('Y');
            $tempPassword = substr(preg_replace('/\D/', '', $customer->phone), -4) . $birthYear;

            // Always set the password so the customer can log in
            $customer->update([
                'password'             => Hash::make($tempPassword),
                'must_change_password' => true,
            ]);

            // Only send email if the customer has an email address
            if (!empty($customer->email)) {
                $notificationService->send(
                    recipientId:   $customer->id,
                    recipientType: 'customer',
                    message:       'Your Gobaad Bank customer portal has been activated. Log in with your phone number and the temporary password sent to your email.',
                    subject:       'Customer Portal Access — Temporary Password',
                    mailView:      'kyc-status',
                    mailData:      [
                        'customerName' => $customer->full_name,
                        'customerId'   => $customer->id,
                        'phone'        => $customer->phone,
                        'status'       => 'approved',
                        'decidedAt'    => now()->format('d M Y, H:i:s'),
                        'tempPassword' => $tempPassword,
                    ]
                );
                $sent++;
            } else {
                // Store in-app notification only (no email)
                \App\Models\Notification::create([
                    'recipient_id'   => $customer->id,
                    'recipient_type' => 'customer',
                    'channel'        => 'in_app',
                    'message'        => 'Your customer portal access has been activated. Log in with your phone number and your temporary password.',
                    'status'         => 'pending',
                ]);
                $noEmail++;
            }
        }

        $msg = "Passwords set for " . ($sent + $noEmail) . " customer(s). {$sent} email(s) sent.";
        if ($noEmail > 0) {
            $msg .= " {$noEmail} customer(s) had no email address — their password was set but no email was sent. Please update their profiles.";
        }

        return back()->with('success', $msg);
    }

    // -------------------------------------------------------------------------
    // Branch Manager Assignment
    // -------------------------------------------------------------------------

    public function assignBranchManager(Request $request, $id)
    {
        $request->validate([
            'manager_id' => 'nullable|exists:staff,id',
        ]);

        $branch = Branch::findOrFail($id);

        // Move the previous manager back to their original branch_id
        // (set their branch_id back to whatever branch they were assigned to,
        // but since we don't store "home branch" separately, we just leave them
        // in the branch — they lose the manager role but keep the branch).
        // More importantly: if the outgoing manager was scoped to THIS branch,
        // they keep that branch_id; nothing needs to change for them.

        // Move the new manager's branch_id to the branch they will manage
        // so that all branch-scoped queries (BranchManagerController, middleware,
        // etc.) resolve to the correct branch.
        $newManagerId = $request->manager_id ?: null;

        if ($newManagerId) {
            $newManager = Staff::findOrFail($newManagerId);
            // Update the staff member's branch_id to the managed branch
            $newManager->update(['branch_id' => $branch->id]);
        }

        $branch->update(['manager_id' => $newManagerId]);

        $name = $newManagerId
            ? Staff::find($newManagerId)->full_name
            : 'unassigned';

        return back()->with('success', "Branch manager set to {$name}.");
    }
}
