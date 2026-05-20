<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountingController extends Controller
{
    /* ─────────────────────────────────────────────
     *  Classify an account into vault / customer / internal
     * ───────────────────────────────────────────── */
    private function classifyAccount(?Account $account): array
    {
        if (!$account) {
            return ['label' => 'Unknown Account', 'type' => 'internal'];
        }
        $num = $account->account_number ?? '';
        if (str_starts_with($num, 'VAULT-')) {
            return ['label' => 'Cash / Vault Asset', 'type' => 'vault'];
        }
        if ($account->customer_id) {
            return ['label' => 'Customer Deposit Account', 'type' => 'customer'];
        }
        return ['label' => 'Internal Account', 'type' => 'internal'];
    }

    private function formatEntry(LedgerEntry $entry): array
    {
        $acc = $entry->account;
        $cls = $this->classifyAccount($acc);
        return [
            'id'             => $entry->id,
            'entry_type'     => $entry->entry_type,
            'amount'         => (float) $entry->amount,
            'balance_after'  => (float) $entry->balance_after,
            'account_number' => $acc?->account_number ?? '—',
            'account_holder' => $cls['type'] === 'vault'
                                    ? 'Branch Vault'
                                    : ($acc?->customer?->full_name ?? '—'),
            'account_label'  => $cls['label'],
            'account_type'   => $cls['type'],
        ];
    }

    private static array $TYPE_LABELS = [
        'deposit'    => 'Cash Deposit',
        'withdrawal' => 'Cash Withdrawal',
        'transfer'   => 'Inter-Account Transfer',
    ];

    /* ─────────────────────────────────────────────
     *  GET /staff/accounting/ledger
     *  Paginated list of transactions, each with their GL entries
     * ───────────────────────────────────────────── */
    public function ledger(Request $request)
    {
        $staff            = Auth::guard('staff')->user()->load('role');
        $branchRestricted = $staff->role?->branch_restricted ?? true;

        $query = Transaction::with([
            'ledgerEntries.account.customer',
            'initiator.role',
        ])->orderBy('created_at', 'desc');

        if ($branchRestricted) {
            $query->where('branch_id', $staff->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginated = $query->paginate(20)->withQueryString();

        $paginated->getCollection()->transform(function (Transaction $txn) {
            $entries = $txn->ledgerEntries->map(fn ($e) => $this->formatEntry($e))->values();

            return [
                'id'          => $txn->id,
                'reference'   => $txn->reference,
                'type'        => $txn->type,
                'type_label'  => self::$TYPE_LABELS[$txn->type] ?? ucfirst((string) $txn->type),
                'amount'      => (float) $txn->amount,
                'status'      => $txn->status,
                'description' => $txn->description,
                'created_at'  => $txn->created_at?->format('d M Y, H:i'),
                'teller'      => $txn->initiator?->full_name ?? '—',
                'teller_role' => $txn->initiator?->role?->role_name ?? '—',
                'is_reversed' => (bool) $txn->is_reversed,
                'is_flagged'  => (bool) $txn->is_flagged,
                'entries'     => $entries,
            ];
        });

        $branches = $branchRestricted
            ? []
            : Branch::orderBy('branch_name')->get(['id', 'branch_name'])->toArray();

        return Inertia::render('Staff/Accounting/Ledger', [
            'transactions'  => $paginated,
            'filters'       => $request->only('date_from', 'date_to', 'type', 'status', 'branch_id'),
            'is_restricted' => $branchRestricted,
            'branches'      => $branches,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/accounting/trial-balance
     * ───────────────────────────────────────────── */
    public function trialBalance()
    {
        $staff = Auth::guard('staff')->user();

        $trialBalance = DB::table('ledger_entries')
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->select(
                'accounts.account_number',
                'accounts.account_name',
                DB::raw("SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE 0 END) as total_debit"),
                DB::raw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE 0 END) as total_credit")
            )
            ->where('ledger_entries.branch_id', $staff->branch_id)
            ->groupBy('accounts.id', 'accounts.account_number', 'accounts.account_name')
            ->get();

        $grandTotalDebit  = $trialBalance->sum('total_debit');
        $grandTotalCredit = $trialBalance->sum('total_credit');

        return Inertia::render('Staff/Accounting/TrialBalance', [
            'trialBalance' => $trialBalance,
            'totals' => [
                'debit'       => $grandTotalDebit,
                'credit'      => $grandTotalCredit,
                'is_balanced' => abs($grandTotalDebit - $grandTotalCredit) < 0.01,
            ],
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/accounting/gl/{id}
     *  Full GL journal entry detail for one transaction
     * ───────────────────────────────────────────── */
    public function glDetail(int $id)
    {
        $transaction = Transaction::with([
            'initiator.role',
            'initiator.branch',
        ])->findOrFail($id);

        $rawEntries = LedgerEntry::with('account.customer')
            ->where('transaction_id', $id)
            ->orderBy('id')
            ->get();

        $entries     = $rawEntries->map(fn ($e) => $this->formatEntry($e))->values();
        $totalDebit  = $entries->where('entry_type', 'debit')->sum('amount');
        $totalCredit = $entries->where('entry_type', 'credit')->sum('amount');

        return Inertia::render('Staff/Accounting/GlDetail', [
            'transaction' => [
                'id'          => $transaction->id,
                'reference'   => $transaction->reference,
                'type'        => $transaction->type,
                'type_label'  => self::$TYPE_LABELS[$transaction->type] ?? ucfirst((string) $transaction->type),
                'amount'      => (float) $transaction->amount,
                'status'      => $transaction->status,
                'description' => $transaction->description,
                'created_at'  => $transaction->created_at?->format('d M Y, H:i:s'),
                'teller'      => $transaction->initiator?->full_name ?? '—',
                'teller_role' => $transaction->initiator?->role?->role_name ?? '—',
                'branch'      => $transaction->initiator?->branch?->branch_name ?? '—',
                'is_reversed' => (bool) $transaction->is_reversed,
                'reversal_of' => $transaction->reversal_of,
                'is_flagged'  => (bool) $transaction->is_flagged,
                'flag_reason' => $transaction->flag_reason,
            ],
            'entries'      => $entries,
            'is_balanced'  => abs($totalDebit - $totalCredit) < 0.01,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
        ]);
    }
}
