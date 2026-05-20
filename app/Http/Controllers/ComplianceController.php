<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Customer;
use App\Models\KycDocument;
use App\Models\SuspiciousActivityReport;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ComplianceController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/compliance/dashboard
     * ───────────────────────────────────────────── */
    public function dashboard()
    {
        $staff = Auth::guard('staff')->user();

        $branchRestricted = $staff->load('role')->role?->branch_restricted ?? true;

        $custBase = Customer::where('phone', 'not like', 'SYS-%');
        $txnBase  = Transaction::query();
        $sarBase  = SuspiciousActivityReport::query();

        if ($branchRestricted) {
            $custBase->where('home_branch_id', $staff->branch_id);
            $txnBase->where('branch_id', $staff->branch_id);
            $sarBase->where('branch_id', $staff->branch_id);
        }

        $stats = [
            'pending_kyc'    => (clone $custBase)->whereIn('kyc_status', ['pending', 'under_review'])->count(),
            'verified_month' => (clone $custBase)->where('kyc_status', 'verified')
                                    ->whereMonth('updated_at', now()->month)
                                    ->whereYear('updated_at', now()->year)->count(),
            'rejected_total' => (clone $custBase)->where('kyc_status', 'rejected')->count(),
            'flagged_txn'    => (clone $txnBase)->where('is_flagged', true)->count(),
            'open_sars'      => (clone $sarBase)->whereIn('status', ['submitted', 'under_review'])->count(),
            'critical_sars'  => (clone $sarBase)->where('risk_level', 'critical')
                                    ->whereIn('status', ['submitted', 'under_review'])->count(),
        ];

        $recentSars = SuspiciousActivityReport::with(['customer', 'reporter'])
            ->where('branch_id', $staff->branch_id)
            ->latest()->limit(5)->get();

        $recentFlagged = Transaction::with(['primaryAccount.customer', 'flaggedBy'])
            ->where('branch_id', $staff->branch_id)
            ->where('is_flagged', true)
            ->latest()->limit(5)->get();

        return Inertia::render('Staff/Compliance/Dashboard', [
            'stats'          => $stats,
            'recent_sars'    => $recentSars,
            'recent_flagged' => $recentFlagged,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/compliance  (KYC queue)
     * ───────────────────────────────────────────── */
    public function index()
    {
        $pendingCustomers = Customer::with(['kycDocuments', 'branch'])
            ->whereIn('kyc_status', ['pending', 'under_review'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Staff/Compliance/Index', [
            'customers' => $pendingCustomers,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/compliance/kyc/{id}/approve
     * ───────────────────────────────────────────── */
    public function approveKyc(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $staff    = Auth::guard('staff')->user();

        // Generate temporary password: last 4 digits of phone + birth year
        $birthYear   = $customer->dob ? date('Y', strtotime($customer->dob)) : date('Y');
        $tempPassword = substr(preg_replace('/\D/', '', $customer->phone), -4) . $birthYear;

        DB::transaction(function () use ($customer, $staff, $tempPassword) {
            $customer->update([
                'kyc_status'          => 'verified',
                'rejection_reason'    => null,
                'password'            => Hash::make($tempPassword),
                'must_change_password'=> true,
            ]);

            KycDocument::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->update([
                    'status'      => 'verified',
                    'verified_by' => $staff->id,
                    'uploaded_at' => now(),
                ]);

            Account::where('customer_id', $customer->id)
                ->where('status', 'inactive')
                ->update(['status' => 'active', 'opened_at' => now()]);
        });

        // Notify customer with login credentials
        app(NotificationService::class)->send(
            recipientId:   $customer->id,
            recipientType: 'customer',
            message:       'Your KYC has been approved. You can now log in to the customer portal using your phone number and your temporary password.',
            subject:       'KYC Application Approved — Account Activated',
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

        return back()->with('success', "Customer {$customer->full_name} has been verified and accounts activated.");
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/compliance/kyc/{id}/reject
     * ───────────────────────────────────────────── */
    public function rejectKyc(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update([
            'kyc_status'       => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Notify customer of rejection
        app(NotificationService::class)->send(
            recipientId:   $customer->id,
            recipientType: 'customer',
            message:       "Your KYC application has been rejected. Reason: {$request->rejection_reason}",
            subject:       'KYC Application Rejected',
            mailView:      'kyc-status',
            mailData:      [
                'customerName' => $customer->full_name,
                'customerId'   => $customer->id,
                'phone'        => $customer->phone,
                'status'       => 'rejected',
                'decidedAt'    => now()->format('d M Y, H:i:s'),
                'rejectReason' => $request->rejection_reason,
            ]
        );

        return back()->with('success', "Customer {$customer->full_name} registration has been rejected.");
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/compliance/customers
     * ───────────────────────────────────────────── */
    public function customers(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branchRestricted = $staff->load('role')->role?->branch_restricted ?? true;

        $query = Customer::with(['accounts'])
            ->where('phone', 'not like', 'SYS-%');

        if ($branchRestricted) {
            $query->where('home_branch_id', $staff->branch_id);
        }

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->kyc_status) {
            $query->where('kyc_status', $status);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        return Inertia::render('Staff/Compliance/Customers', [
            'customers' => $customers,
            'filters'   => $request->only('search', 'kyc_status'),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/compliance/customers/{id}
     * ───────────────────────────────────────────── */
    public function customerDetail($id)
    {
        $customer = Customer::with(['kycDocuments', 'accounts.accountType'])->findOrFail($id);

        $recentTransactions = Transaction::with(['primaryAccount', 'initiator'])
            ->where(function ($q) use ($customer) {
                $q->whereHas('primaryAccount', fn($sq) => $sq->where('customer_id', $customer->id))
                  ->orWhereHas('secondaryAccount', fn($sq) => $sq->where('customer_id', $customer->id));
            })
            ->latest()->limit(15)->get();

        return Inertia::render('Staff/Compliance/CustomerDetail', [
            'customer'            => $customer,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/compliance/transactions
     * ───────────────────────────────────────────── */
    public function transactions(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branchRestricted = $staff->load('role')->role?->branch_restricted ?? true;

        $query = Transaction::with(['primaryAccount.customer', 'initiator', 'flaggedBy']);

        if ($branchRestricted) {
            $query->where('branch_id', $staff->branch_id);
        }

        if ($request->is_flagged === 'true') {
            $query->where('is_flagged', true);
        } elseif ($request->is_flagged === 'false') {
            $query->where('is_flagged', false);
        }

        if ($type = $request->type) {
            $query->where('type', $type);
        }

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('primaryAccount', fn($sq) => $sq->where('account_number', 'like', "%{$search}%"));
            });
        }

        if ($from = $request->date_from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->date_to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $transactions = $query->latest()->paginate(30)->withQueryString();

        return Inertia::render('Staff/Compliance/Transactions', [
            'transactions' => $transactions,
            'filters'      => $request->only('search', 'is_flagged', 'type', 'date_from', 'date_to'),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/compliance/transactions/{id}/flag
     * ───────────────────────────────────────────── */
    public function flagTransaction(Request $request, $id)
    {
        $request->validate([
            'flag_reason' => 'required|string|max:1000',
        ]);

        $staff = Auth::guard('staff')->user();
        $branchRestricted = $staff->load('role')->role?->branch_restricted ?? true;

        $txnQuery = Transaction::query();
        if ($branchRestricted) {
            $txnQuery->where('branch_id', $staff->branch_id);
        }
        $transaction = $txnQuery->findOrFail($id);

        $transaction->update([
            'is_flagged'  => true,
            'flag_reason' => $request->flag_reason,
            'flagged_by'  => $staff->id,
        ]);

        return back()->with('success', "Transaction {$transaction->reference} flagged for review.");
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/compliance/transactions/{id}/unflag
     * ───────────────────────────────────────────── */
    public function unflagTransaction($id)
    {
        $staff = Auth::guard('staff')->user();
        $branchRestricted = $staff->load('role')->role?->branch_restricted ?? true;

        $txnQuery = Transaction::query();
        if ($branchRestricted) {
            $txnQuery->where('branch_id', $staff->branch_id);
        }
        $transaction = $txnQuery->findOrFail($id);

        $transaction->update([
            'is_flagged'  => false,
            'flag_reason' => null,
            'flagged_by'  => null,
        ]);

        return back()->with('success', "Flag removed from {$transaction->reference}.");
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/compliance/reports
     * ───────────────────────────────────────────── */
    public function sarIndex(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $branchRestricted = $staff->load('role')->role?->branch_restricted ?? true;

        $query = SuspiciousActivityReport::with(['customer', 'reporter', 'transaction']);
        if ($branchRestricted) {
            $query->where('branch_id', $staff->branch_id);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($risk = $request->risk_level) {
            $query->where('risk_level', $risk);
        }

        $reports = $query->latest()->paginate(20)->withQueryString();

        $custQuery = Customer::where('phone', 'not like', 'SYS-%');
        if ($branchRestricted) {
            $custQuery->where('home_branch_id', $staff->branch_id);
        }

        $customers = $custQuery->select('id', 'full_name', 'id_number')->orderBy('full_name')->get();

        $flaggedTxnQuery = Transaction::where('is_flagged', true);
        if ($branchRestricted) {
            $flaggedTxnQuery->where('branch_id', $staff->branch_id);
        }
        $flaggedTxns = $flaggedTxnQuery->select('id', 'reference', 'amount', 'type')->latest()->get();

        return Inertia::render('Staff/Compliance/Reports', [
            'reports'      => $reports,
            'customers'    => $customers,
            'flagged_txns' => $flaggedTxns,
            'filters'      => $request->only('status', 'risk_level'),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/compliance/reports
     * ───────────────────────────────────────────── */
    public function sarStore(Request $request)
    {
        $validated = $request->validate([
            'customer_id'    => 'nullable|exists:customers,id',
            'transaction_id' => 'nullable|exists:transactions,id',
            'risk_level'     => 'required|in:low,medium,high,critical',
            'type'           => 'required|in:structuring,money_laundering,fraud,identity_theft,unusual_activity,other',
            'description'    => 'required|string|max:5000',
            'status'         => 'required|in:draft,submitted',
        ]);

        $staff = Auth::guard('staff')->user();

        SuspiciousActivityReport::create(array_merge($validated, [
            'reference'   => 'SAR-' . strtoupper(uniqid()),
            'reported_by' => $staff->id,
            'branch_id'   => $staff->branch_id,
        ]));

        return back()->with('success', 'Suspicious Activity Report filed successfully.');
    }

    /* ─────────────────────────────────────────────
     *  PUT /staff/compliance/reports/{id}
     * ───────────────────────────────────────────── */
    public function sarUpdate(Request $request, $id)
    {
        $staff  = Auth::guard('staff')->user();
        $report = SuspiciousActivityReport::where('branch_id', $staff->branch_id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:draft,submitted,under_review,closed',
            'notes'  => 'nullable|string|max:2000',
        ]);

        $extra = [];
        if ($validated['status'] === 'closed' && $report->status !== 'closed') {
            $extra = ['reviewed_by' => $staff->id, 'reviewed_at' => now()];
        }

        $report->update(array_merge($validated, $extra));

        return back()->with('success', 'SAR status updated.');
    }
}
