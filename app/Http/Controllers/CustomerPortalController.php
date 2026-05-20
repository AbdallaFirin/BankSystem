<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Models\Notification;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class CustomerPortalController extends Controller
{
    private function customer()
    {
        return Auth::guard('customers')->user();
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/dashboard
     * ───────────────────────────────────────────── */
    public function dashboard()
    {
        $customer = $this->customer()->load(['accounts.accountType', 'accounts.homeBranch']);

        $accountIds = $customer->accounts->pluck('id');

        $recentTransactions = Transaction::with(['primaryAccount', 'secondaryAccount', 'initiator'])
            ->where(function ($q) use ($accountIds) {
                $q->whereIn('account_id', $accountIds)
                  ->orWhereIn('to_account_id', $accountIds);
            })
            ->whereIn('status', ['completed', 'approved'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($tx) => $this->formatTransaction($tx, $accountIds));

        $unreadCount = $customer->unreadNotificationsCount();

        return Inertia::render('Customer/Dashboard', [
            'accounts'            => $customer->accounts->map(fn($acc) => $this->formatAccount($acc)),
            'recent_transactions' => $recentTransactions,
            'unread_count'        => $unreadCount,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/accounts
     * ───────────────────────────────────────────── */
    public function accounts()
    {
        $customer = $this->customer()->load(['accounts.accountType', 'accounts.homeBranch']);

        return Inertia::render('Customer/Accounts', [
            'accounts' => $customer->accounts->map(fn($acc) => $this->formatAccount($acc)),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/transactions
     * ───────────────────────────────────────────── */
    public function transactions(Request $request)
    {
        $customer   = $this->customer();
        $accountIds = Account::where('customer_id', $customer->id)->pluck('id');

        $query = Transaction::with(['primaryAccount', 'secondaryAccount', 'initiator'])
            ->where(function ($q) use ($accountIds) {
                $q->whereIn('account_id', $accountIds)
                  ->orWhereIn('to_account_id', $accountIds);
            })
            ->whereIn('status', ['completed', 'approved']);

        // Filters
        if ($accountId = $request->account_id) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                  ->orWhere('to_account_id', $accountId);
            });
        }

        if ($type = $request->type) {
            $query->where('type', $type);
        }

        if ($from = $request->date_from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date_to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $transactions = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($tx) => $this->formatTransaction($tx, $accountIds));

        return Inertia::render('Customer/Transactions', [
            'transactions' => $transactions,
            'accounts'     => Account::where('customer_id', $customer->id)
                ->select('id', 'account_number')->get(),
            'filters'      => $request->only('account_id', 'type', 'date_from', 'date_to'),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/notifications
     * ───────────────────────────────────────────── */
    public function notifications(Request $request)
    {
        $customer = $this->customer();

        $notifications = Notification::forCustomer($customer->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Customer/Notifications', [
            'notifications' => $notifications,
            'unread_count'  => $customer->unreadNotificationsCount(),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/notifications/read
     * ───────────────────────────────────────────── */
    public function markNotificationsRead()
    {
        $customer = $this->customer();

        Notification::forCustomer($customer->id)
            ->where('status', 'pending')
            ->update(['status' => 'read']);

        return back()->with('success', 'All notifications marked as read.');
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/profile
     * ───────────────────────────────────────────── */
    public function profile()
    {
        $customer = $this->customer()->load([
            'accounts.accountType',
            'accounts.homeBranch',
            'branch',
            'registeredBy',
            'kycDocuments',
        ]);

        return Inertia::render('Customer/Profile', [
            'forced'   => (bool) $customer->must_change_password,
            'customer' => [
                'id'                           => $customer->id,
                'full_name'                    => $customer->full_name,
                'first_name'                   => $customer->first_name,
                'middle_name'                  => $customer->middle_name,
                'last_name'                    => $customer->last_name,
                'phone'                        => $customer->phone,
                'secondary_phone'              => $customer->secondary_phone,
                'email'                        => $customer->email,
                'dob'                          => $customer->dob,
                'gender'                       => $customer->gender,
                'marital_status'               => $customer->marital_status,
                'nationality'                  => $customer->nationality,
                'religion'                     => $customer->religion,
                'id_type'                      => $customer->id_type,
                'id_number'                    => $customer->id_number,
                'id_issue_date'                => $customer->id_issue_date,
                'id_expiry_date'               => $customer->id_expiry_date,
                'region_city'                  => $customer->region_city,
                'district'                     => $customer->district,
                'street_neighbourhood'         => $customer->street_neighbourhood,
                'address'                      => $customer->address,
                'employment_status'            => $customer->employment_status,
                'employer_name'                => $customer->employer_name,
                'monthly_income_range'         => $customer->monthly_income_range,
                'source_of_funds'              => $customer->source_of_funds,
                'expected_monthly_transactions'=> $customer->expected_monthly_transactions,
                'next_of_kin_name'             => $customer->next_of_kin_name,
                'next_of_kin_relation'         => $customer->next_of_kin_relation,
                'next_of_kin_phone'            => $customer->next_of_kin_phone,
                'preferred_contact_method'     => $customer->preferred_contact_method,
                'status'                       => $customer->status,
                'kyc_status'                   => $customer->kyc_status,
                'branch_name'                  => $customer->branch?->branch_name ?? '—',
                'registered_by'                => $customer->registeredBy?->full_name ?? '—',
                'created_at'                   => $customer->created_at?->format('d M Y'),
                'accounts'                     => $customer->accounts->map(fn($acc) => $this->formatAccount($acc)),
                'kyc_documents'                => $customer->kycDocuments->map(fn($doc) => [
                    'id'         => $doc->id,
                    'doc_type'   => $doc->doc_type,
                    'status'     => $doc->status,
                    'created_at' => $doc->created_at?->format('d M Y'),
                ]),
            ],
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/reports
     * ───────────────────────────────────────────── */
    public function reports(Request $request)
    {
        $customer   = $this->customer();
        $accountIds = Account::where('customer_id', $customer->id)->pluck('id');

        $query = Transaction::with(['primaryAccount', 'secondaryAccount'])
            ->where(function ($q) use ($accountIds) {
                $q->whereIn('account_id', $accountIds)
                  ->orWhereIn('to_account_id', $accountIds);
            })
            ->whereIn('status', ['completed', 'approved']);

        if ($accountId = $request->account_id) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                  ->orWhere('to_account_id', $accountId);
            });
        }

        if ($type = $request->type) {
            $query->where('type', $type);
        }

        if ($from = $request->date_from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date_to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $transactions = $query->orderByDesc('created_at')
            ->get()
            ->map(fn($tx) => $this->formatTransaction($tx, $accountIds));

        $accounts = Account::where('customer_id', $customer->id)
            ->select('id', 'account_number')->get();

        // Summary stats
        $totalIn  = $transactions->where('sign', '+')->sum('amount');
        $totalOut = $transactions->where('sign', '-')->sum('amount');

        return Inertia::render('Customer/Reports', [
            'transactions' => $transactions,
            'accounts'     => $accounts,
            'filters'      => $request->only('account_id', 'type', 'date_from', 'date_to'),
            'summary'      => [
                'total_in'    => $totalIn,
                'total_out'   => $totalOut,
                'net'         => $totalIn - $totalOut,
                'count'       => $transactions->count(),
            ],
            'customer_name'=> $customer->full_name,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/reports/export
     *  Download CSV of the current report filters
     * ───────────────────────────────────────────── */
    public function exportCsv(Request $request)
    {
        $customer   = $this->customer();
        $accountIds = Account::where('customer_id', $customer->id)->pluck('id');

        $query = Transaction::with(['primaryAccount', 'secondaryAccount'])
            ->where(function ($q) use ($accountIds) {
                $q->whereIn('account_id', $accountIds)
                  ->orWhereIn('to_account_id', $accountIds);
            })
            ->whereIn('status', ['completed', 'approved']);

        if ($accountId = $request->account_id) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                  ->orWhere('to_account_id', $accountId);
            });
        }
        if ($type = $request->type)         { $query->where('type', $type); }
        if ($from = $request->date_from)    { $query->whereDate('created_at', '>=', $from); }
        if ($to   = $request->date_to)      { $query->whereDate('created_at', '<=', $to); }

        $transactions = $query->orderByDesc('created_at')->get()
            ->map(fn($tx) => $this->formatTransaction($tx, $accountIds));

        $filename = 'transactions-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($transactions, $customer) {
            $handle = fopen('php://output', 'w');

            // Report header
            fputcsv($handle, ['Gobaad Bank — Transaction Report']);
            fputcsv($handle, ['Customer:', $customer->full_name]);
            fputcsv($handle, ['Generated:', now()->format('d M Y H:i')]);
            fputcsv($handle, []);

            // Column headers
            fputcsv($handle, ['Date', 'Reference', 'Type', 'Account', 'Description', 'Sign', 'Amount (USD)']);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx['created_at'],
                    $tx['reference'],
                    $tx['type'],
                    $tx['account_number'],
                    $tx['description'] ?? '',
                    $tx['sign'],
                    number_format($tx['amount'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/transactions/{id}/receipt
     *  Stream a PDF receipt for the given transaction
     * ───────────────────────────────────────────── */
    public function receipt(int $id)
    {
        $customer   = $this->customer();
        $accountIds = Account::where('customer_id', $customer->id)->pluck('id')->toArray();

        $transaction = Transaction::with([
            'primaryAccount.customer',
            'secondaryAccount.customer',
            'initiator.role',
            'initiator.branch',
        ])->findOrFail($id);

        // Security — only allow the customer to download their own receipts
        $ownsTransaction = in_array($transaction->account_id, $accountIds)
                        || in_array($transaction->to_account_id, $accountIds);

        if (!$ownsTransaction) {
            abort(403, 'Access denied.');
        }

        // Only completed/approved transactions
        if (!in_array($transaction->status, ['completed', 'approved'])) {
            abort(404, 'Receipt not available for this transaction.');
        }

        $pdf = Pdf::loadView('receipts.customer-receipt', [
            'transaction' => $transaction,
            'customer'    => $customer,
            'printDate'   => now()->format('d M Y'),
            'printTime'   => now()->format('H:i'),
        ])->setPaper([0, 0, 226.77, 566.93], 'portrait'); // 80mm receipt paper

        $filename = 'receipt-' . $transaction->reference . '.pdf';
        return $pdf->stream($filename);
    }

    /* ─────────────────────────────────────────────
     *  Helpers
     * ───────────────────────────────────────────── */
    private function formatAccount(Account $acc): array
    {
        return [
            'id'             => $acc->id,
            'account_number' => $acc->account_number,
            'type_name'      => $acc->accountType?->type_name ?? '—',
            'balance'        => $acc->balance,
            'status'         => $acc->status,
            'is_frozen'      => $acc->is_frozen,
            'opened_at'      => $acc->opened_at?->format('d M Y'),
            'branch_name'    => $acc->homeBranch?->branch_name ?? '—',
        ];
    }

    private function formatTransaction(Transaction $tx, $myAccountIds): array
    {
        // Determine sign from the perspective of this customer's account
        // A transfer where to_account_id is ours = money coming IN
        $isCredit = in_array($tx->to_account_id, $myAccountIds->toArray())
                    && $tx->type === 'transfer';
        $sign     = ($tx->type === 'deposit' || $isCredit) ? '+' : '-';

        return [
            'id'             => $tx->id,
            'reference'      => $tx->reference,
            'type'           => ucfirst($tx->type),
            'amount'         => $tx->amount,
            'sign'           => $sign,
            'status'         => $tx->status,
            'created_at'     => $tx->created_at?->format('d M Y, H:i'),
            'account_number' => $tx->primaryAccount?->account_number ?? '—',
            'teller'         => $tx->initiator?->full_name ?? '—',
            'description'    => $tx->description,
        ];
    }
}
