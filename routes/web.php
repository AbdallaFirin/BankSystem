<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

use Illuminate\Support\Facades\Mail;

Route::get('/send-test',function(){
    mail::raw(
        'This is Test Email',function($message){
            $message->to('abdallasport12@gmail.com')
            ->subject("Laravel Email Test");
        });

    return 'Test Email Sent Successfully';

});

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AuthController;

Route::get('/customer/register', [CustomerController::class, 'showRegistrationForm'])->name('customer.register');
Route::post('/customer/register', [CustomerController::class, 'register']);

// Staff Authentication (Defaulted to /login to satisfy Laravel's `auth` middleware redirect)
Route::get('/login', [AuthController::class, 'showStaffLogin'])->name('login');
Route::post('/login', [AuthController::class, 'staffLogin']);
Route::post('/logout', [AuthController::class, 'staffLogout'])->name('logout');

Route::get('/error/403', function () {
    return \Inertia\Inertia::render('Errors/403');
})->name('error.403');

use App\Http\Controllers\TransactionController;

use App\Models\Account;
use Illuminate\Support\Facades\Auth;

// Protected Staff Routes with RBAC Middleware demo
Route::middleware(['auth:staff'])->group(function () {
    Route::get('/staff/dashboard', function () {
        $staff = Auth::guard('staff')->user()->load('role', 'branch');

        // Vault balance
        $vault        = Account::where('account_number', 'VAULT-'.$staff->branch_id)->first();
        $vaultBalance = $vault ? (float)$vault->balance : 0.0;

        // Today's transactions by this staff
        $todayTxns   = \App\Models\Transaction::where('initiated_by', $staff->id)
                            ->whereDate('created_at', today())
                            ->count();
        $todayAmount = (float) \App\Models\Transaction::where('initiated_by', $staff->id)
                            ->whereDate('created_at', today())
                            ->sum('amount');

        // Month transactions
        $monthTxns = \App\Models\Transaction::where('initiated_by', $staff->id)
                         ->whereMonth('created_at', now()->month)
                         ->whereYear('created_at',  now()->year)
                         ->count();

        // Active till (tellers)
        $activeTill  = \App\Models\CashAllocation::where('teller_id', $staff->id)
                           ->whereIn('status', ['acknowledged'])
                           ->latest()->first();
        $tillBalance = 0.0;
        if ($activeTill) {
            $tillBalance = (float) \App\Models\LedgerEntry::where('account_id', $activeTill->till_account_id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0.0;
        }

        // Pending approvals (supervisors/managers) — join through transactions for branch scope
        $pendingApprovals = \App\Models\PendingApproval::where('status', 'pending')
                                ->whereHas('transaction', fn($q) => $q->where('branch_id', $staff->branch_id))
                                ->count();

        // Branch staff count (managers)
        $branchStaffCount = \App\Models\Staff::where('branch_id', $staff->branch_id)->count();

        // Pending KYC (compliance / customer care)
        $pendingKyc = \App\Models\KycDocument::where('status', 'pending')->count();

        // Recent 6 transactions by this staff
        $recentTxns = \App\Models\Transaction::where('initiated_by', $staff->id)
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'type'       => $t->type ?? '—',
                'amount'     => (float) $t->amount,
                'reference'  => $t->reference ?? '—',
                'description'=> $t->description ?? '—',
                'status'     => $t->status ?? 'completed',
                'created_at' => $t->created_at?->format('d M, H:i'),
                'teller'     => $staff->full_name,
            ]);

        // ── Branch Manager analytics ──────────────────────────────────────────
        $isBranchManager  = str_contains($staff->role?->role_name ?? '', 'Manager');
        $branchBreakdown  = [
            'deposit'  => ['count' => 0, 'amount' => 0.0],
            'withdraw' => ['count' => 0, 'amount' => 0.0],
            'transfer' => ['count' => 0, 'amount' => 0.0],
        ];
        $weeklyTrend       = [];
        $activeTillsList   = [];
        $branchTodayTxns   = $todayTxns;
        $branchTodayAmount = $todayAmount;
        $yesterdayTxns     = 0;
        $yesterdayAmount   = 0.0;
        $branchTotalCash   = $vaultBalance;
        $recentBranchTxns  = $recentTxns;

        if ($isBranchManager) {
            // Branch totals today (1 query)
            $branchToday = \App\Models\Transaction::where('branch_id', $staff->branch_id)
                ->whereDate('created_at', today())
                ->selectRaw("COUNT(*) as cnt, COALESCE(SUM(amount),0) as amt")
                ->first();
            $branchTodayTxns   = (int) ($branchToday->cnt ?? 0);
            $branchTodayAmount = (float) ($branchToday->amt ?? 0);

            // Yesterday comparison (1 query)
            $branchYesterday = \App\Models\Transaction::where('branch_id', $staff->branch_id)
                ->whereDate('created_at', today()->subDay())
                ->selectRaw("COUNT(*) as cnt, COALESCE(SUM(amount),0) as amt")
                ->first();
            $yesterdayTxns   = (int) ($branchYesterday->cnt ?? 0);
            $yesterdayAmount = (float) ($branchYesterday->amt ?? 0);

            // Breakdown by type today (1 query)
            $typeRows = \App\Models\Transaction::where('branch_id', $staff->branch_id)
                ->whereDate('created_at', today())
                ->selectRaw("type, COUNT(*) as cnt, COALESCE(SUM(amount),0) as amt")
                ->groupBy('type')
                ->get()->keyBy('type');
            foreach (['deposit', 'withdraw', 'transfer'] as $t) {
                $r = $typeRows->get($t);
                $branchBreakdown[$t] = ['count' => (int)($r->cnt ?? 0), 'amount' => (float)($r->amt ?? 0)];
            }

            // 7-day trend (1 query)
            $trendRows = \App\Models\Transaction::where('branch_id', $staff->branch_id)
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->selectRaw("DATE(created_at) as d, COUNT(*) as cnt, COALESCE(SUM(amount),0) as amt")
                ->groupBy('d')
                ->get()->keyBy('d');
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $r   = $trendRows->get($day->toDateString());
                $weeklyTrend[] = [
                    'label'  => $day->format('D'),
                    'date'   => $day->format('M d'),
                    'count'  => (int)($r->cnt ?? 0),
                    'amount' => (float)($r->amt ?? 0),
                    'is_today' => $i === 0,
                ];
            }

            // Active tills + total float in circulation
            $allActiveTills = \App\Models\CashAllocation::where('branch_id', $staff->branch_id)
                ->where('status', 'acknowledged')
                ->with('teller')
                ->get();
            $tillTotal = 0.0;
            foreach ($allActiveTills as $a) {
                $bal = (float) \App\Models\LedgerEntry::where('account_id', $a->till_account_id)
                    ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as b")
                    ->value('b') ?? 0.0;
                $tillTotal += $bal;
                $activeTillsList[] = [
                    'teller_name'   => $a->teller?->full_name ?? '—',
                    'opening_float' => (float) $a->amount,
                    'till_balance'  => $bal,
                    'since'         => $a->acknowledged_at?->format('H:i') ?? '—',
                ];
            }
            $branchTotalCash = $vaultBalance + $tillTotal;

            // Recent branch-wide transactions with teller name
            $recentBranchTxns = \App\Models\Transaction::where('transactions.branch_id', $staff->branch_id)
                ->join('staff as s', 'transactions.initiated_by', '=', 's.id')
                ->select('transactions.*', 's.full_name as teller_name')
                ->latest('transactions.created_at')
                ->limit(8)
                ->get()
                ->map(fn($t) => [
                    'id'         => $t->id,
                    'type'       => $t->type ?? '—',
                    'amount'     => (float) $t->amount,
                    'reference'  => $t->reference ?? '—',
                    'status'     => $t->status ?? 'completed',
                    'created_at' => $t->created_at?->format('d M, H:i'),
                    'teller'     => $t->teller_name ?? '—',
                ]);
        }

        return \Inertia\Inertia::render('Staff/Dashboard', [
            'vault_balance'       => $vaultBalance,
            'staff_info'          => [
                'full_name'    => $staff->full_name,
                'role_name'    => $staff->role?->role_name ?? '—',
                'ident_number' => $staff->ident_number,
                'branch_name'  => $staff->branch?->branch_name ?? '—',
                'avatar'       => $staff->avatar,
            ],
            'stats' => [
                'today_txns'        => $todayTxns,
                'today_amount'      => $todayAmount,
                'month_txns'        => $monthTxns,
                'till_balance'      => $tillBalance,
                'till_open'         => (bool) $activeTill,
                'pending_approvals' => $pendingApprovals,
                'branch_staff'      => $branchStaffCount,
                'pending_kyc'       => $pendingKyc,
            ],
            // Manager analytics
            'branch_breakdown'    => $branchBreakdown,
            'weekly_trend'        => $weeklyTrend,
            'active_tills'        => $activeTillsList,
            'branch_today_txns'   => $branchTodayTxns,
            'branch_today_amount' => $branchTodayAmount,
            'yesterday_txns'      => $yesterdayTxns,
            'yesterday_amount'    => $yesterdayAmount,
            'branch_total_cash'   => $branchTotalCash,
            'recent_transactions' => $recentBranchTxns,
        ]);
    })->name('staff.dashboard');
    
    // Teller sub-pages (GET)
    Route::get('/staff/teller/deposit',  [\App\Http\Controllers\TellerController::class, 'depositPage'])->name('staff.teller.deposit');
    Route::get('/staff/teller/withdraw', [\App\Http\Controllers\TellerController::class, 'withdrawPage'])->name('staff.teller.withdraw');
    Route::get('/staff/teller/transfer', [\App\Http\Controllers\TellerController::class, 'transferPage'])->name('staff.teller.transfer');
    Route::get('/staff/teller/lookup-account', [\App\Http\Controllers\TellerController::class, 'lookupAccount'])->name('staff.teller.lookup');
    Route::post('/staff/teller/reverse/{id}',  [\App\Http\Controllers\TellerController::class, 'reverseTransaction'])->name('staff.teller.reverse');
    Route::post('/staff/teller/cancel/{id}',   [\App\Http\Controllers\TellerController::class, 'cancelPending'])->name('staff.teller.cancel');

    // Cash Count
    Route::get( '/staff/teller/cash-count',                    [\App\Http\Controllers\CashCountController::class, 'index']     )->name('staff.teller.cash-count.index');
    Route::post('/staff/teller/cash-count',                    [\App\Http\Controllers\CashCountController::class, 'store']     )->name('staff.teller.cash-count.store');
    Route::get( '/staff/teller/cash-count/approvals',          [\App\Http\Controllers\CashCountController::class, 'approvals'] )->name('staff.teller.cash-count.approvals');
    Route::post('/staff/teller/cash-count/{id}/approve',       [\App\Http\Controllers\CashCountController::class, 'approve']   )->name('staff.teller.cash-count.approve');
    Route::post('/staff/teller/cash-count/{id}/flag',          [\App\Http\Controllers\CashCountController::class, 'flag']      )->name('staff.teller.cash-count.flag');
    Route::get( '/staff/teller/cash-count/{id}/print',         [\App\Http\Controllers\CashCountController::class, 'printSheet'])->name('staff.teller.cash-count.print');

    // Cash Allocation (Per-Teller Till — Option B)
    Route::get( '/staff/teller/cash-allocation',                           [\App\Http\Controllers\CashAllocationController::class, 'index']      )->name('staff.teller.cash-allocation.index');
    Route::post('/staff/teller/cash-allocation',                           [\App\Http\Controllers\CashAllocationController::class, 'store']      )->name('staff.teller.cash-allocation.store');
    Route::post('/staff/teller/cash-allocation/{id}/acknowledge',          [\App\Http\Controllers\CashAllocationController::class, 'acknowledge'])->name('staff.teller.cash-allocation.acknowledge');
    Route::post('/staff/teller/cash-allocation/{id}/collect',              [\App\Http\Controllers\CashAllocationController::class, 'collect']    )->name('staff.teller.cash-allocation.collect');
    Route::get( '/staff/teller/my-till',                                   [\App\Http\Controllers\CashAllocationController::class, 'myTill']     )->name('staff.teller.my-till');

    // Transaction processing (POST)
    Route::post('/staff/transactions/deposit',  [TransactionController::class, 'deposit'])->name('staff.deposit');
    Route::post('/staff/transactions/withdraw', [TransactionController::class, 'withdraw'])->name('staff.withdraw');
    Route::post('/staff/transactions/transfer', [TransactionController::class, 'transfer'])->name('staff.transfer');

    // Supervisor Approvals
    Route::get('/staff/approvals', [\App\Http\Controllers\ApprovalController::class, 'index'])->name('staff.approvals');
    Route::post('/staff/approvals/bulk-approve', [\App\Http\Controllers\ApprovalController::class, 'bulkApprove'])->name('staff.approvals.bulk-approve');
    Route::post('/staff/approvals/bulk-reject',  [\App\Http\Controllers\ApprovalController::class, 'bulkReject'])->name('staff.approvals.bulk-reject');
    Route::post('/staff/approvals/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approve'])->name('staff.approvals.approve');
    Route::post('/staff/approvals/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'reject'])->name('staff.approvals.reject');
    // Customer Care Operations
    Route::get('/staff/customer-care/register', [\App\Http\Controllers\CustomerCareController::class, 'create'])->name('staff.customer-care.register');
    Route::post('/staff/customer-care/register', [\App\Http\Controllers\CustomerCareController::class, 'store'])->name('staff.customer-care.store');
    Route::get('/staff/customer-care/customers', [\App\Http\Controllers\CustomerCareController::class, 'customersIndex'])->name('staff.customer-care.customers');
    Route::get('/staff/customer-care/customers/{id}', [\App\Http\Controllers\CustomerCareController::class, 'showProfile'])->name('staff.customer-care.profile');
    Route::get('/staff/customer-care/kyc-pending', [\App\Http\Controllers\CustomerCareController::class, 'kycIndex'])->name('staff.customer-care.kyc.list');
    Route::get('/staff/customer-care/accounts', [\App\Http\Controllers\CustomerCareController::class, 'accountsIndex'])->name('staff.customer-care.accounts.index');
    Route::get('/staff/account/{id}/transactions', [\App\Http\Controllers\CustomerCareController::class, 'accountTransactions'])->name('staff.account.transactions');
    Route::get('/staff/account/{id}/statement', [\App\Http\Controllers\CustomerCareController::class, 'downloadStatement'])->name('staff.account.statement');
    Route::get('/staff/customer-care/account-opening', [\App\Http\Controllers\CustomerCareController::class, 'createAccount'])->name('staff.customer-care.accounts.create');
    Route::post('/staff/customer-care/account-opening', [\App\Http\Controllers\CustomerCareController::class, 'storeAccount'])->name('staff.customer-care.accounts.store');
    Route::get('/staff/customer-care/kyc/{id}', [\App\Http\Controllers\CustomerCareController::class, 'kyc'])->name('staff.customer-care.kyc');
    Route::post('/staff/customer-care/kyc/{id}', [\App\Http\Controllers\CustomerCareController::class, 'uploadKyc'])->name('staff.customer-care.upload-kyc');
    Route::post('/staff/customer-care/kyc/{id}/submit-review', [\App\Http\Controllers\CustomerCareController::class, 'submitKycForReview'])->name('staff.customer-care.kyc.submit-review');

    // Customer-profile quick transactions (redirect back to customer profile)
    Route::post('/staff/customer-care/accounts/{id}/deposit',  [\App\Http\Controllers\CustomerCareController::class, 'quickDeposit'] )->name('staff.customer-care.quick.deposit');
    Route::post('/staff/customer-care/accounts/{id}/withdraw', [\App\Http\Controllers\CustomerCareController::class, 'quickWithdraw'])->name('staff.customer-care.quick.withdraw');
    Route::post('/staff/customer-care/accounts/{id}/transfer', [\App\Http\Controllers\CustomerCareController::class, 'quickTransfer'])->name('staff.customer-care.quick.transfer');
    Route::post('/staff/customer-care/accounts/{id}/status',   [\App\Http\Controllers\CustomerCareController::class, 'updateAccountStatus'])->name('staff.customer-care.accounts.status');
    // Lookup account number → return customer name (AJAX)
    Route::get('/staff/customer-care/lookup-account',          [\App\Http\Controllers\CustomerCareController::class, 'lookupAccount'] )->name('staff.customer-care.lookup-account');

    // Compliance Operations
    Route::get('/staff/compliance', [\App\Http\Controllers\ComplianceController::class, 'index'])->name('staff.compliance.index');
    Route::post('/staff/compliance/kyc/{id}/approve', [\App\Http\Controllers\ComplianceController::class, 'approveKyc'])->name('staff.compliance.approve-kyc');
    Route::post('/staff/compliance/kyc/{id}/reject', [\App\Http\Controllers\ComplianceController::class, 'rejectKyc'])->name('staff.compliance.reject-kyc');

    // Branch Manager Settings
    Route::get( '/staff/branch/settings',       [\App\Http\Controllers\BranchSettingsController::class, 'index']       )->name('staff.branch.settings');
    Route::post('/staff/branch/settings',       [\App\Http\Controllers\BranchSettingsController::class, 'update']      )->name('staff.branch.settings.update');
    Route::post('/staff/branch/vault/deposit',  [\App\Http\Controllers\BranchSettingsController::class, 'vaultDeposit'])->name('staff.branch.vault.deposit');

    // Branch Manager — Staff & Audit
    Route::get(   '/staff/branch/staff',             [\App\Http\Controllers\BranchManagerController::class, 'staffIndex']  )->name('staff.branch.staff.index');
    Route::post(  '/staff/branch/staff',             [\App\Http\Controllers\BranchManagerController::class, 'staffStore']  )->name('staff.branch.staff.store');
    Route::get(   '/staff/branch/staff/{id}',        [\App\Http\Controllers\BranchManagerController::class, 'staffShow']   )->name('staff.branch.staff.show');
    Route::put(   '/staff/branch/staff/{id}',        [\App\Http\Controllers\BranchManagerController::class, 'staffUpdate'] )->name('staff.branch.staff.update');
    Route::post(  '/staff/branch/staff/{id}/status', [\App\Http\Controllers\BranchManagerController::class, 'staffStatus'] )->name('staff.branch.staff.status');
    Route::delete('/staff/branch/staff/{id}',        [\App\Http\Controllers\BranchManagerController::class, 'staffDestroy'])->name('staff.branch.staff.destroy');
    Route::get(   '/staff/branch/audit',             [\App\Http\Controllers\BranchManagerController::class, 'auditIndex']  )->name('staff.branch.audit');

    // My Profile (all staff)
    Route::get( '/staff/my-profile',          [\App\Http\Controllers\StaffProfileController::class, 'show']           )->name('staff.profile');
    Route::post('/staff/my-profile/info',     [\App\Http\Controllers\StaffProfileController::class, 'updateInfo']     )->name('staff.profile.info');
    Route::post('/staff/my-profile/password', [\App\Http\Controllers\StaffProfileController::class, 'updatePassword'] )->name('staff.profile.password');
    Route::post('/staff/my-profile/avatar',   [\App\Http\Controllers\StaffProfileController::class, 'updateAvatar']   )->name('staff.profile.avatar');

    // Accounting Operations
    Route::get('/staff/accounting/ledger', [\App\Http\Controllers\AccountingController::class, 'ledger'])->name('staff.accounting.ledger');
    Route::get('/staff/accounting/trial-balance', [\App\Http\Controllers\AccountingController::class, 'trialBalance'])->name('staff.accounting.trial-balance');

    // HQ / Super Admin Operations
    Route::get('/staff/admin/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('staff.admin.dashboard');
    // Branch Management
    Route::get('/staff/admin/branches', [\App\Http\Controllers\AdminController::class, 'branchIndex'])->name('staff.admin.branches.index');
    Route::post('/staff/admin/branches', [\App\Http\Controllers\AdminController::class, 'storeBranch'])->name('staff.admin.branches.store');
    Route::put('/staff/admin/branches/{id}', [\App\Http\Controllers\AdminController::class, 'updateBranch'])->name('staff.admin.branches.update');
    Route::post('/staff/admin/branches/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateBranchStatus'])->name('staff.admin.branches.status');
    Route::post('/staff/admin/branches/{id}/manager', [\App\Http\Controllers\AdminController::class, 'assignBranchManager'])->name('staff.admin.branches.manager');

    // Role Management
    Route::get('/staff/admin/roles', [\App\Http\Controllers\AdminController::class, 'roleIndex'])->name('staff.admin.roles.index');
    Route::post('/staff/admin/roles', [\App\Http\Controllers\AdminController::class, 'storeRole'])->name('staff.admin.roles.store');
    Route::put('/staff/admin/roles/{id}', [\App\Http\Controllers\AdminController::class, 'updateRole'])->name('staff.admin.roles.update');
    Route::post('/staff/admin/roles/permissions', [\App\Http\Controllers\AdminController::class, 'updateRolePermissions'])->name('staff.admin.roles.permissions');

    // Staff Management
    Route::get('/staff/admin/staff', [\App\Http\Controllers\AdminController::class, 'staffIndex'])->name('staff.admin.staff.index');
    Route::post('/staff/admin/staff', [\App\Http\Controllers\AdminController::class, 'storeStaff'])->name('staff.admin.staff.store');
    Route::post('/staff/admin/staff/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateStaffStatus'])->name('staff.admin.staff.status');
    Route::get('/staff/admin/staff/preview-id', [\App\Http\Controllers\AdminController::class, 'previewStaffId'])->name('staff.admin.staff.preview-id');

    // Audit Log
    Route::get('/staff/admin/audit', [\App\Http\Controllers\AdminController::class, 'auditIndex'])->name('staff.admin.audit.index');

    // Settings
    Route::get('/staff/admin/settings', [\App\Http\Controllers\AdminController::class, 'settingsIndex'])->name('staff.admin.settings.index');
    Route::post('/staff/admin/settings/account-types', [\App\Http\Controllers\AdminController::class, 'storeAccountType'])->name('staff.admin.settings.account-types.store');
    Route::put('/staff/admin/settings/account-types/{id}', [\App\Http\Controllers\AdminController::class, 'updateAccountType'])->name('staff.admin.settings.account-types.update');
    Route::put('/staff/admin/settings/roles/{id}/limit', [\App\Http\Controllers\AdminController::class, 'updateRoleLimit'])->name('staff.admin.settings.role-limit');

    // Customer Status Control
    Route::get('/staff/admin/customers', [\App\Http\Controllers\AdminController::class, 'customerIndex'])->name('staff.admin.customers.index');
    Route::post('/staff/admin/customers/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateCustomerStatus'])->name('staff.admin.customers.status');
});
