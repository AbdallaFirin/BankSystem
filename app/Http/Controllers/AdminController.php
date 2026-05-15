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
use Illuminate\Support\Facades\DB;

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
            'city' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string',
        ]);

        Branch::create($request->all() + ['status' => 'active', 'opened_at' => now()]);

        return back()->with('success', "Branch {$request->branch_name} successfully established.");
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
            'city'        => 'required|string|max:255',
            'address'     => 'required|string',
            'phone'       => 'required|string',
        ]);

        $branch = Branch::findOrFail($id);
        $branch->update($request->only('branch_name', 'city', 'address', 'phone'));

        return back()->with('success', "Branch {$branch->branch_name} updated successfully.");
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
        ]);

        AccountType::create([
            'type_name'         => $request->type_name,
            'interest_rate'     => $request->interest_rate,
            'min_balance'       => $request->min_balance,
            'withdrawal_limit'  => $request->withdrawal_limit,
            'overdraft_allowed' => $request->boolean('overdraft_allowed'),
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
        ]);

        $type = AccountType::findOrFail($id);
        $type->update([
            'type_name'         => $request->type_name,
            'interest_rate'     => $request->interest_rate,
            'min_balance'       => $request->min_balance,
            'withdrawal_limit'  => $request->withdrawal_limit,
            'overdraft_allowed' => $request->boolean('overdraft_allowed'),
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

    public function customerIndex()
    {
        $customers = Customer::with('branch')
            ->orderBy('full_name')
            ->paginate(25);

        return \Inertia\Inertia::render('Staff/Admin/Customers', [
            'customers' => $customers,
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

    // -------------------------------------------------------------------------
    // Branch Manager Assignment
    // -------------------------------------------------------------------------

    public function assignBranchManager(Request $request, $id)
    {
        $request->validate([
            'manager_id' => 'nullable|exists:staff,id',
        ]);

        $branch = Branch::findOrFail($id);
        $branch->update(['manager_id' => $request->manager_id ?: null]);

        $name = $request->manager_id
            ? Staff::find($request->manager_id)->full_name
            : 'unassigned';

        return back()->with('success', "Branch manager set to {$name}.");
    }
}
