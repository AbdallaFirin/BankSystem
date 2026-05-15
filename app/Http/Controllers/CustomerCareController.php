<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\AccountType;
use App\Models\Account;
use App\Models\KycDocument;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\StaffAuditLog;
use App\Services\LedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerCareController extends Controller
{
    public function __construct(private LedgerService $ledgerService) {}

    public function create()
    {
        // Get unique account types by name to avoid duplicates in UI
        $accountTypes = AccountType::where('is_active', true)
            ->get()
            ->unique('type_name');

        return \Inertia\Inertia::render('Staff/CustomerCare/Register', [
            'branches' => Branch::where('status', 'active')->get(),
            'accountTypes' => $accountTypes->values(),
        ]);
    }

    public function kycIndex()
    {
        $customers = Customer::whereIn('kyc_status', ['pending', 'under_review'])->latest()->get();
        return \Inertia\Inertia::render('Staff/CustomerCare/KYCPending', [
            'customers' => $customers
        ]);
    }

    /**
     * Generate a unique 12-digit account number.
     * Format: [3-letter branch prefix][3-digit branch ID][6-digit sequence]
     * Example: CAR002000001
     */
    private function generateAccountNumber(int $branchId): string
    {
        $branch = Branch::findOrFail($branchId);
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $branch->branch_name), 0, 3));
        $branchPart = str_pad($branchId, 3, '0', STR_PAD_LEFT);

        $existingCount = Account::where('home_branch_id', $branchId)->count();
        $sequence = str_pad($existingCount + 1, 6, '0', STR_PAD_LEFT);

        return $prefix . $branchPart . $sequence;
    }

    private function getVaultAccount(int $branchId): Account
    {
        $vaultCustomer = Customer::firstOrCreate(
            ['phone' => 'SYS-VAULT-' . $branchId],
            [
                'full_name'        => 'Internal Bank Vault - Branch ' . $branchId,
                'first_name'       => 'Vault',
                'last_name'        => 'Branch ' . $branchId,
                'dob'              => '2000-01-01',
                'gender'           => 'N/A',
                'nationality'      => 'N/A',
                'id_type'          => 'Internal',
                'id_number'        => 'SYS-VAULT-' . $branchId,
                'id_issue_date'    => '2000-01-01',
                'employment_status'=> 'N/A',
                'monthly_income_range' => 'N/A',
                'source_of_funds'  => 'N/A',
                'next_of_kin_name' => 'N/A',
                'next_of_kin_relation' => 'N/A',
                'next_of_kin_phone' => '000',
                'region_city'      => 'N/A',
                'home_branch_id'   => $branchId,
                'registered_by'    => 1,
                'kyc_status'       => 'verified',
            ]
        );

        return Account::firstOrCreate(
            ['account_number' => 'VAULT-' . $branchId],
            [
                'customer_id'     => $vaultCustomer->id,
                'account_type_id' => 1,
                'home_branch_id'  => $branchId,
                'status'          => 'active',
                'opened_at'       => now(),
            ]
        );
    }

    public function accountTransactions(Request $request, $accountId)
    {
        $account = Account::with(['customer', 'accountType', 'homeBranch'])->findOrFail($accountId);

        // Validate sort params
        $sortBy  = in_array($request->sort_by, ['created_at', 'amount', 'balance_after'])
                   ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $query = \App\Models\LedgerEntry::with(['transaction'])
            ->where('account_id', $accountId)
            ->orderBy($sortBy, $sortDir);

        if ($request->filled('type')) {
            $query->where('entry_type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', (float) $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', (float) $request->max_amount);
        }

        $entries = $query->paginate(25)->withQueryString();

        // Running totals for the filtered set
        $totalCredits = $entries->getCollection()->where('entry_type', 'credit')->sum('amount');
        $totalDebits  = $entries->getCollection()->where('entry_type', 'debit')->sum('amount');

        return \Inertia\Inertia::render('Staff/CustomerCare/TransactionHistory', [
            'account'       => $account,
            'entries'       => $entries,
            'total_credits' => (float) $totalCredits,
            'total_debits'  => (float) $totalDebits,
            'filters'       => $request->only('type', 'date_from', 'date_to', 'min_amount', 'max_amount', 'sort_by', 'sort_dir'),
        ]);
    }

    public function downloadStatement(Request $request, $accountId)
    {
        $account = Account::with(['customer', 'accountType', 'homeBranch'])->findOrFail($accountId);

        $dateFrom = $request->date_from
            ? \Carbon\Carbon::parse($request->date_from)->startOfDay()
            : \Carbon\Carbon::now()->startOfMonth();

        $dateTo = $request->date_to
            ? \Carbon\Carbon::parse($request->date_to)->endOfDay()
            : \Carbon\Carbon::now()->endOfDay();

        // Eager-load transaction (reference, type, description, initiator role + branch)
        $entries = LedgerEntry::with([
            'transaction.initiator.role',
            'transaction.initiator.branch',
        ])
            ->where('account_id', $accountId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'asc')
            ->get();

        // Opening balance: sum of all entries BEFORE the period
        $openingBalance = LedgerEntry::where('account_id', $accountId)
            ->where('created_at', '<', $dateFrom)
            ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as balance")
            ->value('balance') ?? 0;

        $totalCredits   = $entries->where('entry_type', 'credit')->sum('amount');
        $totalDebits    = $entries->where('entry_type', 'debit')->sum('amount');
        $closingBalance = $openingBalance + $totalCredits - $totalDebits;

        // Group entries by month (YYYY-MM) for month-divider rows in the template
        $grouped = $entries->groupBy(fn($e) => \Carbon\Carbon::parse($e->created_at)->format('Y-m'));

        // Who physically printed the statement
        $printedBy = Auth::guard('staff')->user()?->load('role', 'branch');

        // The SERVING branch = where the customer currently is (the branch of the staff printing it)
        $servingBranchId   = $printedBy?->branch_id ?? $account->home_branch_id;
        $servingBranchName = $printedBy?->branch?->branch_name
                             ?? $account->homeBranch?->branch_name
                             ?? 'Main Branch';

        // ── Customer Relations Officer ───────────────────────────────────────────
        // Use the SERVING branch (where the customer walked in), not the home branch
        // Priority 1: active Customer Care Officer in the serving branch
        $customerOfficer = \App\Models\Staff::with('role')
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Customer%'))
            ->where('branch_id', $servingBranchId)
            ->where('status', 'active')
            ->first();

        // Priority 2: any active non-manager staff in the serving branch
        if (!$customerOfficer) {
            $customerOfficer = \App\Models\Staff::with('role')
                ->whereHas('role', fn($q) => $q->where('role_name', 'not like', '%Manager%')
                                               ->where('role_name', 'not like', '%Admin%'))
                ->where('branch_id', $servingBranchId)
                ->where('status', 'active')
                ->first();
        }

        // Priority 3: the person who printed (always has a name)
        if (!$customerOfficer) {
            $customerOfficer = $printedBy;
        }

        // ── Branch Manager ───────────────────────────────────────────────────────
        // Use the SERVING branch's manager
        // Priority 1: active Branch Manager in the serving branch
        $branchManager = \App\Models\Staff::with('role')
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Manager%'))
            ->where('branch_id', $servingBranchId)
            ->where('status', 'active')
            ->first();

        // Priority 2: the person who printed
        if (!$branchManager) {
            $branchManager = $printedBy;
        }

        // Deterministic statement reference
        $stmtRef = 'STMT-' . strtoupper(
            substr(md5($account->account_number . $dateFrom->format('Ymd') . $dateTo->format('Ymd')), 0, 8)
        );

        $pdf = Pdf::loadView('statements.account', [
            'account'           => $account,
            'entries'           => $entries,
            'grouped'           => $grouped,
            'dateFrom'          => $dateFrom,
            'dateTo'            => $dateTo,
            'openingBalance'    => $openingBalance,
            'closingBalance'    => $closingBalance,
            'totalCredits'      => $totalCredits,
            'totalDebits'       => $totalDebits,
            'customerOfficer'   => $customerOfficer,   // serving-branch customer care officer
            'branchManager'     => $branchManager,     // serving-branch manager
            'servingBranchName' => $servingBranchName, // branch where customer walked in
            'printedBy'         => $printedBy,         // who clicked print (audit)
            'stmtRef'           => $stmtRef,
        ])->setPaper('a4', 'portrait');

        $filename = 'Statement_' . $account->account_number . '_' . $dateFrom->format('Ymd') . '_' . $dateTo->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function accountsIndex()
    {
        $accounts = Account::with(['customer', 'accountType', 'homeBranch'])
            ->where('account_number', 'not like', 'VAULT-%')
            ->where('account_number', 'not like', 'TILL-%')
            ->where('account_number', 'not like', 'SYS-%')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Append live balance from ledger entries
        $accounts->getCollection()->transform(function ($account) {
            $account->balance = \App\Models\LedgerEntry::where('account_id', $account->id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0;
            return $account;
        });

        return \Inertia\Inertia::render('Staff/CustomerCare/Accounts', [
            'accounts' => $accounts,
        ]);
    }

    public function createAccount()
    {
        $staff = Auth::guard('staff')->user();
        
        // Only show verified customers who don't yet have an active account
        $customers = Customer::where('kyc_status', 'verified')
            ->orderBy('full_name')
            ->get();
            
        $accountTypes = \App\Models\AccountType::all();
        $branches = \App\Models\Branch::where('status', 'active')->get();

        return \Inertia\Inertia::render('Staff/CustomerCare/AccountOpening', [
            'customers' => $customers,
            'accountTypes' => $accountTypes,
            'branches' => $branches,
            // Auto-populate: set default branch to the logged-in staff's branch
            'executing_branch_id' => $staff->branch_id,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'customer_id'     => 'required|exists:customers,id',
            'account_type_id' => 'required|exists:account_types,id',
            'home_branch_id'  => 'required|exists:branches,id',
            'initial_deposit' => 'required|numeric|min:0',
        ]);

        $staff      = Auth::guard('staff')->user();
        $customer   = Customer::findOrFail($request->customer_id);
        $branchId   = (int) $request->home_branch_id;

        $accountNumber = $this->generateAccountNumber($branchId);

        DB::transaction(function () use ($request, $customer, $branchId, $accountNumber, $staff) {
            $account = Account::create([
                'customer_id'     => $customer->id,
                'account_type_id' => $request->account_type_id,
                'home_branch_id'  => $branchId,
                'account_number'  => $accountNumber,
                'status'          => 'active',
                'opened_at'       => now(),
            ]);

            if ((float) $request->initial_deposit > 0) {
                $vault = $this->getVaultAccount($branchId);
                $this->ledgerService->deposit(
                    $vault,
                    $account,
                    (float) $request->initial_deposit,
                    $staff->id,
                    $branchId,
                    'Initial Deposit at Account Opening'
                );
            }
        });

        return redirect()->route('staff.customer-care.accounts.create')
            ->with('success', "Account {$accountNumber} opened for {$customer->full_name}.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Personal Info
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'marital_status' => 'nullable|string',
            'nationality' => 'required|string',
            'religion' => 'nullable|string',
            
            // Identity
            'id_type' => 'required|string',
            'id_number' => 'required|string|unique:customers,id_number',
            'id_issue_date' => 'required|date',
            'id_expiry_date' => 'nullable|date',
            
            // Contact
            'phone' => 'required|string|unique:customers,phone',
            'secondary_phone' => 'nullable|string',
            'email' => 'nullable|email|unique:customers,email',
            'preferred_contact_method' => 'required|string',
            
            // Address
            'region_city' => 'required|string',
            'district' => 'nullable|string',
            'street_neighbourhood' => 'nullable|string',
            'address' => 'nullable|string',
            
            // Employment
            'employment_status' => 'required|string',
            'employer_name' => 'nullable|string',
            'monthly_income_range' => 'required|string',
            'source_of_funds' => 'required|string',
            'expected_monthly_transactions' => 'nullable|string',
            
            // Next of Kin
            'next_of_kin_name' => 'required|string',
            'next_of_kin_relation' => 'required|string',
            'next_of_kin_parent_type' => 'nullable|string|required_if:next_of_kin_relation,Parent',
            'next_of_kin_phone' => 'required|string',
            
            // Account Selection
            'account_type_id' => 'required|exists:account_types,id',
            'home_branch_id' => 'required|exists:branches,id',
            'initial_deposit' => 'nullable|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $staff = Auth::guard('staff')->user();
                
                // If Parent relationship, append the type (Father/Mother)
                if ($validated['next_of_kin_relation'] === 'Parent' && isset($validated['next_of_kin_parent_type'])) {
                    $validated['next_of_kin_relation'] .= " ({$validated['next_of_kin_parent_type']})";
                }

                $customerData = \Illuminate\Support\Arr::except($validated, [
                    'account_type_id', 
                    'initial_deposit', 
                    'next_of_kin_parent_type'
                ]);
                
                $customer = Customer::create(array_merge($customerData, [
                    'full_name' => $validated['first_name'] . ' ' . ($validated['middle_name'] ? $validated['middle_name'] . ' ' : '') . $validated['last_name'],
                    'registered_by' => $staff->id,
                    'kyc_status' => 'pending',
                ]));

                // Create initial account (inactive until KYC approved)
                $account = Account::create([
                    'customer_id' => $customer->id,
                    'account_type_id' => $validated['account_type_id'],
                    'home_branch_id' => $validated['home_branch_id'], // Fixed: was branch_id
                    'account_number' => 'ACC-' . strtoupper(Str::random(8)),
                    'status' => 'inactive',
                ]);

                return redirect()->route('staff.customer-care.kyc', $customer->id)
                    ->with('success', "Customer {$customer->full_name} registered successfully. Please upload KYC documents.");
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function customersIndex(Request $request)
    {
        $query = Customer::with(['branch', 'accounts'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('id_number', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        $customers = $query->paginate(20)->withQueryString();

        return \Inertia\Inertia::render('Staff/CustomerCare/Customers', [
            'customers' => $customers,
            'filters'   => $request->only('search', 'kyc_status'),
        ]);
    }

    public function showProfile($id)
    {
        $customer = Customer::with([
            'branch',
            'registeredBy',
            'kycDocuments',
            'accounts.accountType',
        ])->findOrFail($id);

        return \Inertia\Inertia::render('Staff/CustomerCare/CustomerProfile', [
            'customer' => $customer,
        ]);
    }

    public function kyc($id)
    {
        $customer = Customer::with('kycDocuments')->findOrFail($id);
        return \Inertia\Inertia::render('Staff/CustomerCare/KYCUpload', [
            'customer' => $customer
        ]);
    }

    public function uploadKyc(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $request->validate([
            'doc_type' => 'required|string',
            'file' => 'required|file|mimes:jpg,png,pdf|max:2048',
        ]);

        $path = $request->file('file')->store('kyc_documents', 'public');

        KycDocument::create([
            'customer_id' => $customer->id,
            'doc_type' => $request->doc_type,
            'file_path' => $path,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        // Check for completion
        $docs = $customer->kycDocuments()->pluck('doc_type')->toArray();
        $isComplete = false;

        if ($customer->id_type === 'Passport') {
            if (in_array('Passport Copy', $docs) && in_array('Passport Photo', $docs)) {
                $isComplete = true;
            }
        } else {
            if (in_array('National ID Front', $docs) && in_array('National ID Back', $docs) && in_array('Passport Photo', $docs)) {
                $isComplete = true;
            }
        }

        if ($isComplete) {
            $customer->update(['kyc_status' => 'under_review']);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function submitKycForReview($id)
    {
        $customer = Customer::findOrFail($id);

        // Guard: only allow progression from 'pending' status
        if (!in_array($customer->kyc_status, ['pending', 'incomplete'])) {
            return back()->with('success', 'This customer has already been submitted for review.');
        }

        $customer->update(['kyc_status' => 'under_review']);

        return redirect()->route('staff.customer-care.kyc.list')
            ->with('success', "{$customer->full_name} has been sent to Pending Reviews for compliance verification.");
    }

    /* ──────────────────────────────────────────────────────────────────
     |  Quick-action helpers — transactions from customer profile page
     | ─────────────────────────────────────────────────────────────── */

    /** Shared: resolve the teller's active till or fall back to vault */
    private function getCashAccount(\App\Models\Staff $staff): Account
    {
        $alloc = \App\Models\CashAllocation::where('teller_id', $staff->id)
            ->where('status', 'acknowledged')
            ->latest('allocated_at')
            ->first();

        if ($alloc) {
            return Account::findOrFail($alloc->till_account_id);
        }

        // Vault fallback
        $branchId = $staff->branch_id;
        $vc = Customer::firstOrCreate(
            ['phone' => 'SYS-VAULT-' . $branchId],
            [
                'full_name'             => 'Internal Bank Vault - Branch ' . $branchId,
                'first_name'            => 'Vault', 'last_name' => 'Branch ' . $branchId,
                'dob'                   => '2000-01-01', 'gender' => 'N/A',
                'nationality'           => 'N/A', 'id_type'  => 'Internal',
                'id_number'             => 'SYS-VAULT-' . $branchId,
                'id_issue_date'         => '2000-01-01',
                'employment_status'     => 'N/A', 'monthly_income_range' => 'N/A',
                'source_of_funds'       => 'N/A', 'next_of_kin_name'     => 'N/A',
                'next_of_kin_relation'  => 'N/A', 'next_of_kin_phone'    => '000',
                'region_city'           => 'N/A', 'home_branch_id'       => $branchId,
                'registered_by'         => 1,     'kyc_status'           => 'verified',
            ]
        );
        return Account::firstOrCreate(
            ['account_number' => 'VAULT-' . $branchId],
            ['customer_id' => $vc->id, 'account_type_id' => 1,
             'home_branch_id' => $branchId, 'status' => 'active', 'opened_at' => now()]
        );
    }

    /** Redirect back to the customer profile page for this account */
    private function redirectToProfile(Account $account, string $message)
    {
        return redirect()
            ->route('staff.customer-care.profile', $account->customer_id)
            ->with('success', $message);
    }

    /* ── POST /staff/customer-care/accounts/{id}/deposit ── */
    public function quickDeposit(Request $request, int $id)
    {
        $account = Account::with('customer')->findOrFail($id);
        if ($account->is_frozen) {
            return back()->withErrors(['error' => "Account {$account->account_number} is frozen and cannot receive deposits."]);
        }
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $staff = Auth::guard('staff')->user();
        try {
            $txn = $this->ledgerService->deposit(
                $this->getCashAccount($staff),
                $account,
                (float) $request->amount,
                $staff->id,
                $staff->branch_id,
                $request->input('note', 'Counter Deposit')
            );
            return $this->redirectToProfile($account,
                "Deposit of \${$request->amount} credited to {$account->account_number}. Ref: {$txn->reference}");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ── POST /staff/customer-care/accounts/{id}/withdraw ── */
    public function quickWithdraw(Request $request, int $id)
    {
        $account = Account::with('customer')->findOrFail($id);
        if ($account->is_frozen) {
            return back()->withErrors(['error' => "Account {$account->account_number} is frozen and no withdrawals are permitted."]);
        }
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $staff = Auth::guard('staff')->user();
        try {
            $txn = $this->ledgerService->withdraw(
                $this->getCashAccount($staff),
                $account,
                (float) $request->amount,
                $staff->id,
                $staff->branch_id,
                $request->input('note', 'Counter Withdrawal')
            );
            return $this->redirectToProfile($account,
                "Withdrawal of \${$request->amount} debited from {$account->account_number}. Ref: {$txn->reference}");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ── POST /staff/customer-care/accounts/{id}/transfer ── */
    public function quickTransfer(Request $request, int $id)
    {
        $fromAccount = Account::with('customer')->findOrFail($id);
        if ($fromAccount->is_frozen) {
            return back()->withErrors(['error' => "Account {$fromAccount->account_number} is frozen and cannot initiate transfers."]);
        }
        $request->validate([
            'to_account' => 'required|exists:accounts,account_number',
            'amount'     => 'required|numeric|min:0.01',
        ]);

        if ($fromAccount->account_number === $request->to_account) {
            return back()->withErrors(['error' => 'Sender and receiver account cannot be the same.']);
        }

        $toAccount = Account::with('customer')
            ->where('account_number', $request->to_account)
            ->firstOrFail();

        if ($toAccount->is_frozen) {
            return back()->withErrors(['error' => "Destination account {$toAccount->account_number} is frozen and cannot receive transfers."]);
        }

        $staff = Auth::guard('staff')->user();
        try {
            $txn = $this->ledgerService->transfer(
                $fromAccount,
                $toAccount,
                (float) $request->amount,
                $staff->id,
                $staff->branch_id
            );
            return $this->redirectToProfile($fromAccount,
                "Transfer of \${$request->amount} from {$fromAccount->account_number} to {$toAccount->account_number} ({$toAccount->customer?->full_name}). Ref: {$txn->reference}");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ── POST /staff/customer-care/accounts/{id}/status ── */
    public function updateAccountStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended,closed',
            'reason' => 'nullable|string|max:500',
        ]);

        $account = Account::with('customer')->findOrFail($id);

        // Closed is a terminal state — no transitions allowed
        if ($account->status === 'closed') {
            return back()->withErrors(['error' => 'A closed account cannot be changed. Closure is permanent.']);
        }

        // Cannot revert an active/suspended account back to inactive
        if ($request->status === 'inactive') {
            return back()->withErrors(['error' => 'Accounts cannot be manually set to inactive.']);
        }

        $oldStatus = $account->status;
        $account->update(['status' => $request->status]);

        $label = ucfirst($request->status);
        return redirect()
            ->route('staff.customer-care.profile', $account->customer_id)
            ->with('success', "Account {$account->account_number} has been {$label}. (was: {$oldStatus})");
    }

    /* ── GET /staff/customer-care/lookup-account?account_number=ACC-XXX ── */
    public function lookupAccount(Request $request)
    {
        $acc = Account::with('customer')
            ->where('account_number', $request->account_number)
            ->where('account_number', 'not like', 'VAULT-%')
            ->where('account_number', 'not like', 'TILL-%')
            ->first();

        if (!$acc) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'          => true,
            'account_number' => $acc->account_number,
            'customer_name'  => $acc->customer?->full_name ?? '—',
            'status'         => $acc->status,
        ]);
    }

    /* ── POST /staff/customer-care/accounts/{id}/freeze ── */
    public function freeze(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $account = Account::with('customer')->findOrFail($id);
        $staff   = Auth::guard('staff')->user();

        if ($account->is_frozen) {
            return back()->withErrors(['error' => 'This account is already frozen.']);
        }

        if ($account->status === 'closed') {
            return back()->withErrors(['error' => 'A closed account cannot be frozen.']);
        }

        $account->update([
            'is_frozen'     => true,
            'frozen_reason' => $request->reason,
            'frozen_by'     => $staff->id,
            'frozen_at'     => now(),
        ]);

        StaffAuditLog::create([
            'staff_id'       => $staff->id,
            'branch_id'      => $staff->branch_id,
            'action'         => 'account_freeze',
            'description'    => "Froze account {$account->account_number} ({$account->customer?->full_name}). Reason: {$request->reason}",
            'target_table'   => 'accounts',
            'target_id'      => $account->id,
            'ip_address'     => $request->ip(),
            'result'         => 'success',
        ]);

        return redirect()
            ->route('staff.customer-care.profile', $account->customer_id)
            ->with('success', "Account {$account->account_number} has been frozen.");
    }

    /* ── POST /staff/customer-care/accounts/{id}/unfreeze ── */
    public function unfreeze(Request $request, int $id)
    {
        $account = Account::with('customer')->findOrFail($id);
        $staff   = Auth::guard('staff')->user();

        if (!$account->is_frozen) {
            return back()->withErrors(['error' => 'This account is not frozen.']);
        }

        $account->update([
            'is_frozen'     => false,
            'frozen_reason' => null,
            'frozen_by'     => null,
            'frozen_at'     => null,
        ]);

        StaffAuditLog::create([
            'staff_id'       => $staff->id,
            'branch_id'      => $staff->branch_id,
            'action'         => 'account_unfreeze',
            'description'    => "Unfroze account {$account->account_number} ({$account->customer?->full_name}).",
            'target_table'   => 'accounts',
            'target_id'      => $account->id,
            'ip_address'     => $request->ip(),
            'result'         => 'success',
        ]);

        return redirect()
            ->route('staff.customer-care.profile', $account->customer_id)
            ->with('success', "Account {$account->account_number} has been unfrozen.");
    }

    /* ── GET /staff/transactions/{id}/receipt ── */
    public function generateReceipt(int $id)
    {
        $transaction = Transaction::with([
            'primaryAccount.customer',
            'secondaryAccount.customer',
            'initiator.role',
            'initiator.branch',
        ])->findOrFail($id);

        $staff = Auth::guard('staff')->user()->load('role', 'branch');

        $pdf = Pdf::loadView('receipts.transaction', [
            'transaction' => $transaction,
            'staff'       => $staff,
            'printDate'   => now()->format('d M Y'),
            'printTime'   => now()->format('H:i'),
        ])->setPaper([0, 0, 226.77, 566.93], 'portrait'); // 80mm wide receipt paper

        $filename = 'receipt-' . $transaction->reference . '.pdf';
        return $pdf->stream($filename);
    }
}
