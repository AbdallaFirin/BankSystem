<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\CashAllocation;
use App\Models\CashCount;
use App\Models\LedgerEntry;
use App\Models\PendingApproval;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffAuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class BranchManagerController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/branch/dashboard
     * ───────────────────────────────────────────── */
    public function dashboard()
    {
        $manager  = Auth::guard('staff')->user()->load('branch');
        $branchId = $manager->branch_id;

        // ── Vault balance ──────────────────────────────────────────────────────
        $vault        = Account::where('account_number', 'VAULT-' . $branchId)->first();
        $vaultBalance = $vault
            ? (float) LedgerEntry::where('account_id', $vault->id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0.0
            : 0.0;

        // ── Today stats ────────────────────────────────────────────────────────
        $todayBase = Transaction::where('branch_id', $branchId)->whereDate('created_at', today());
        $todayCount  = (clone $todayBase)->count();
        $todayAmount = (float)(clone $todayBase)->sum('amount');

        // ── This month stats ───────────────────────────────────────────────────
        $monthBase   = Transaction::where('branch_id', $branchId)
                           ->whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year);
        $monthCount  = (clone $monthBase)->count();
        $monthAmount = (float)(clone $monthBase)->sum('amount');

        // ── Pending approvals ──────────────────────────────────────────────────
        $pendingApprovals = PendingApproval::where('status', 'pending')
            ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId))
            ->count();

        // ── Flagged transactions ───────────────────────────────────────────────
        $flaggedCount = Transaction::where('branch_id', $branchId)
            ->where('is_flagged', true)->count();

        // ── Staff counts ───────────────────────────────────────────────────────
        $staffTotal  = Staff::where('branch_id', $branchId)->count();
        $staffActive = Staff::where('branch_id', $branchId)->where('status', 'active')->count();

        // ── 7-day trend ────────────────────────────────────────────────────────
        $trend = collect(range(6, 0))->map(function ($daysAgo) use ($branchId) {
            $date = now()->subDays($daysAgo)->toDateString();
            $row  = Transaction::where('branch_id', $branchId)
                ->whereDate('created_at', $date)
                ->selectRaw('COUNT(*) as cnt, SUM(amount) as amt')
                ->first();
            return [
                'date'   => now()->subDays($daysAgo)->format('D'),
                'count'  => (int)($row->cnt ?? 0),
                'amount' => (float)($row->amt ?? 0),
            ];
        });

        // ── Transaction breakdown by type ──────────────────────────────────────
        $breakdown = Transaction::where('branch_id', $branchId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->select('type', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(amount) as amt'))
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->map(fn($r) => ['count' => (int)$r->cnt, 'amount' => (float)$r->amt]);

        // ── Top tellers today ──────────────────────────────────────────────────
        $topTellers = Transaction::where('transactions.branch_id', $branchId)
            ->whereDate('transactions.created_at', today())
            ->join('staff', 'transactions.initiated_by', '=', 'staff.id')
            ->select('staff.full_name', 'staff.ident_number', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(transactions.amount) as amt'))
            ->groupBy('staff.id', 'staff.full_name', 'staff.ident_number')
            ->orderByDesc('amt')
            ->limit(5)
            ->get();

        // ── Recent flagged transactions ────────────────────────────────────────
        $recentFlagged = Transaction::with(['primaryAccount.customer'])
            ->where('branch_id', $branchId)
            ->where('is_flagged', true)
            ->latest()->limit(5)->get();

        // ── Recent pending approvals ───────────────────────────────────────────
        $recentPending = PendingApproval::with(['transaction.primaryAccount.customer', 'requestedBy'])
            ->where('status', 'pending')
            ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId))
            ->latest()->limit(5)->get();

        return Inertia::render('Staff/BranchManager/Dashboard', [
            'branch_name'      => $manager->branch?->branch_name ?? '—',
            'vault_balance'    => $vaultBalance,
            'today_count'      => $todayCount,
            'today_amount'     => $todayAmount,
            'month_count'      => $monthCount,
            'month_amount'     => $monthAmount,
            'pending_approvals'=> $pendingApprovals,
            'flagged_count'    => $flaggedCount,
            'staff_total'      => $staffTotal,
            'staff_active'     => $staffActive,
            'trend'            => $trend->values(),
            'breakdown'        => $breakdown,
            'top_tellers'      => $topTellers,
            'recent_flagged'   => $recentFlagged,
            'recent_pending'   => $recentPending,
        ]);
    }

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
    public function staffIndex(Request $request)
    {
        $manager = Auth::guard('staff')->user();

        $query = Staff::with('role')->where('branch_id', $manager->branch_id);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('ident_number', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $staffList = $query->orderBy('role_id')->orderBy('full_name')->get()
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
            'filters'    => $request->only('search', 'role_id', 'status'),
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
            return back()->withErrors(['error' => 'You cannot deactivate your own account.']);
        }

        $target = Staff::where('branch_id', $manager->branch_id)->findOrFail($id);
        $name   = $target->full_name;

        // Banking systems never hard-delete staff — staff may have initiated
        // transactions, approved loans, and generated audit logs. Deleting
        // a staff record would orphan all that history and violate FK constraints.
        // Deactivate instead: the account becomes inaccessible but history is preserved.
        $target->update(['status' => 'inactive']);

        return back()->with('success', "{$name}'s account has been deactivated. Their history is preserved.");
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
