<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashAllocation;
use App\Models\CashCount;
use App\Models\LedgerEntry;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffAuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class BranchManagerController extends Controller
{
    /* ─────────────────────────────────────────────
     *  Shared: generate staff ID (branch-scoped)
     * ───────────────────────────────────────────── */
    private function generateStaffId(int $branchId): string
    {
        $branch = \App\Models\Branch::findOrFail($branchId);
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $branch->branch_name), 0, 3));
        $sequence = 120 + Staff::where('branch_id', $branchId)->count();
        while (Staff::where('ident_number', $prefix . $sequence)->exists()) {
            $sequence++;
        }
        return $prefix . $sequence;
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/branch/staff/{id}
     *  Branch Manager views a staff member's profile
     * ───────────────────────────────────────────── */
    public function staffShow(int $id)
    {
        $manager = Auth::guard('staff')->user();
        $target  = Staff::with(['role', 'branch'])
            ->where('branch_id', $manager->branch_id)
            ->findOrFail($id);

        // Recent audit activity for this staff member
        $recentActivity = StaffAuditLog::where('staff_id', $target->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['action', 'description', 'result', 'created_at']);

        // Stats
        $txCount        = Transaction::where('initiated_by', $target->id)->count();
        $cashCountCount = CashCount::where('staff_id', $target->id)->count();

        $activeTill  = CashAllocation::where('teller_id', $target->id)
            ->whereIn('status', ['pending', 'acknowledged'])
            ->latest()->first();
        $tillBalance = 0.0;
        if ($activeTill) {
            $tillBalance = (float) LedgerEntry::where('account_id', $activeTill->till_account_id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0.0;
        }

        return Inertia::render('Staff/BranchManager/StaffProfile', [
            'staff' => [
                'id'                => $target->id,
                'full_name'         => $target->full_name,
                'email'             => $target->email,
                'phone'             => $target->phone,
                'ident_number'      => $target->ident_number,
                'status'            => $target->status,
                'avatar'            => $target->avatar,
                'gender'            => $target->gender,
                'date_of_birth'     => $target->date_of_birth?->format('Y-m-d'),
                'address'           => $target->address,
                'emergency_contact' => $target->emergency_contact,
                'hired_at'          => $target->hired_at?->format('Y-m-d'),
                'role_name'         => $target->role?->role_name,
                'branch_name'       => $target->branch?->branch_name,
                'created_at'        => $target->created_at?->format('d M Y'),
            ],
            'stats' => [
                'transactions' => $txCount,
                'cash_counts'  => $cashCountCount,
                'till_balance' => $tillBalance,
                'till_open'    => $activeTill?->status === 'acknowledged',
            ],
            'recent_activity' => $recentActivity,
            'roles'           => Role::whereNotIn('role_name', ['Super Admin'])->get(['id', 'role_name'])->unique('role_name')->values(),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/branch/staff
     * ───────────────────────────────────────────── */
    public function staffIndex()
    {
        $manager = Auth::guard('staff')->user();

        $staffList = Staff::with('role')
            ->where('branch_id', $manager->branch_id)
            ->orderBy('role_id')
            ->orderBy('full_name')
            ->get()
            ->map(fn($s) => [
                'id'           => $s->id,
                'full_name'    => $s->full_name,
                'email'        => $s->email,
                'phone'        => $s->phone,
                'ident_number' => $s->ident_number,
                'status'       => $s->status,
                'role_id'      => $s->role_id,
                'role_name'    => $s->role?->role_name ?? '—',
            ]);

        // Roles the branch manager is allowed to assign (no system.admin), deduplicated
        $roles = Role::whereNotIn('role_name', ['Super Admin'])
            ->orderBy('role_name')
            ->get(['id', 'role_name'])
            ->unique('role_name')
            ->values();

        return Inertia::render('Staff/BranchManager/BranchStaff', [
            'staff_list' => $staffList,
            'roles'      => $roles,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/branch/staff
     * ───────────────────────────────────────────── */
    public function staffStore(Request $request)
    {
        $manager = Auth::guard('staff')->user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:staff,email',
            'phone'     => 'required|string|unique:staff,phone',
            'password'  => 'required|min:8',
            'role_id'   => 'required|exists:roles,id',
        ]);

        // Prevent assigning Super Admin role
        $role = Role::findOrFail($request->role_id);
        if ($role->role_name === 'Super Admin') {
            return back()->withErrors(['error' => 'You cannot create a Super Admin account.']);
        }

        $staffId = $this->generateStaffId($manager->branch_id);

        $staff = Staff::create([
            'full_name'              => $request->full_name,
            'email'                  => $request->email,
            'phone'                  => $request->phone,
            'password'               => Hash::make($request->password),
            'role_id'                => $request->role_id,
            'branch_id'              => $manager->branch_id,
            'ident_number'           => $staffId,
            'status'                 => 'active',
            'force_password_change'  => true,
            'temp_password'          => $request->password,  // plain-text; wiped on first self-change
        ]);

        return back()->with('success', "Staff account for {$staff->full_name} created. Login ID: {$staffId}");
    }

    /* ─────────────────────────────────────────────
     *  PUT /staff/branch/staff/{id}
     * ───────────────────────────────────────────── */
    public function staffUpdate(Request $request, int $id)
    {
        $manager = Auth::guard('staff')->user();

        $target = Staff::where('branch_id', $manager->branch_id)->findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:staff,email,' . $target->id,
            'phone'     => 'required|string|unique:staff,phone,' . $target->id,
            'role_id'   => 'required|exists:roles,id',
            'password'  => 'nullable|min:8',
        ]);

        $role = Role::findOrFail($request->role_id);
        if ($role->role_name === 'Super Admin') {
            return back()->withErrors(['error' => 'You cannot assign the Super Admin role.']);
        }

        $data = [
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'role_id'   => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $target->update($data);

        return back()->with('success', "{$target->full_name}'s profile updated.");
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/branch/staff/{id}/status
     * ───────────────────────────────────────────── */
    public function staffStatus(Request $request, int $id)
    {
        $manager = Auth::guard('staff')->user();

        // Cannot change own status
        if ($id === $manager->id) {
            return back()->withErrors(['error' => 'You cannot change your own status.']);
        }

        $target = Staff::where('branch_id', $manager->branch_id)->findOrFail($id);
        $newStatus = $request->status === 'active' ? 'active' : 'inactive';
        $target->update(['status' => $newStatus]);

        return back()->with('success', "{$target->full_name} is now {$newStatus}.");
    }

    /* ─────────────────────────────────────────────
     *  DELETE /staff/branch/staff/{id}
     * ───────────────────────────────────────────── */
    public function staffDestroy(int $id)
    {
        $manager = Auth::guard('staff')->user();

        if ($id === $manager->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $target = Staff::where('branch_id', $manager->branch_id)->findOrFail($id);
        $name = $target->full_name;
        $target->delete();

        return back()->with('success', "{$name}'s account has been deleted.");
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/branch/audit
     * ───────────────────────────────────────────── */
    public function auditIndex(Request $request)
    {
        $manager = Auth::guard('staff')->user();

        $query = StaffAuditLog::with('staff')
            ->where('branch_id', $manager->branch_id)
            ->orderByDesc('created_at');

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();

        $branchStaff = Staff::where('branch_id', $manager->branch_id)
            ->get(['id', 'full_name']);

        return Inertia::render('Staff/BranchManager/BranchAudit', [
            'logs'         => $logs,
            'branch_staff' => $branchStaff,
            'filters'      => $request->only('staff_id', 'action', 'date_from', 'date_to'),
        ]);
    }
}
