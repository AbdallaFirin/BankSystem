<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Models\PendingApproval;
use App\Models\Account;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;

class TellerController extends Controller
{
    public function __construct(private LedgerService $ledgerService) {}

    /* ─────────────── REVERSE ─────────────── */
    public function reverseTransaction(Request $request, int $id)
    {
        $staff = Auth::guard('staff')->user();

        $original = Transaction::where('id', $id)
            ->where('initiated_by', $staff->id)   // only own transactions
            ->where('is_reversed', false)
            ->whereNull('reversal_of')             // can't reverse a reversal
            ->firstOrFail();

        $entries = LedgerEntry::with('account.customer')
            ->where('transaction_id', $original->id)
            ->get();

        try {
            DB::transaction(function () use ($original, $entries, $staff) {
                $desc = 'REVERSAL OF: ' . $original->reference;

                if ($original->type === 'deposit') {
                    // Reverse deposit = withdraw from customer back to vault
                    $customerAccount = $entries->firstWhere('entry_type', 'credit')?->account;
                    $vaultAccount    = $entries->firstWhere('entry_type', 'debit')?->account;

                    if (!$customerAccount || !$vaultAccount) throw new Exception('Ledger entries not found.');
                    if ($customerAccount->balance < $original->amount)
                        throw new Exception('Insufficient balance to reverse this deposit.');

                    $reversal = $this->ledgerService->withdraw($vaultAccount, $customerAccount, $original->amount, $staff->id, $staff->branch_id, $desc);

                } elseif ($original->type === 'withdrawal') {
                    // Reverse withdrawal = deposit back to customer
                    $customerAccount = $entries->firstWhere('entry_type', 'debit')?->account;
                    $vaultAccount    = $entries->firstWhere('entry_type', 'credit')?->account;

                    if (!$customerAccount || !$vaultAccount) throw new Exception('Ledger entries not found.');

                    $reversal = $this->ledgerService->deposit($vaultAccount, $customerAccount, $original->amount, $staff->id, $staff->branch_id, $desc);

                } elseif ($original->type === 'transfer') {
                    // Reverse transfer = transfer back from to_account to from_account
                    $fromAccount = $entries->firstWhere('entry_type', 'debit')?->account;
                    $toAccount   = $entries->firstWhere('entry_type', 'credit')?->account;

                    if (!$fromAccount || !$toAccount) throw new Exception('Ledger entries not found.');
                    if ($toAccount->balance < $original->amount)
                        throw new Exception('Insufficient balance to reverse this transfer.');

                    $reversal = $this->ledgerService->transfer($toAccount, $fromAccount, $original->amount, $staff->id, $staff->branch_id, $desc);

                } else {
                    throw new Exception('Unknown transaction type.');
                }

                // Mark original as reversed and link the reversal back
                $original->update(['is_reversed' => true]);
                $reversal->update(['reversal_of' => $original->id]);
            });

            return back()->with('success', "Transaction {$original->reference} has been reversed successfully.");

        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    /* ─────────────── CANCEL PENDING ─────────────── */
    public function cancelPending(Request $request, int $id)
    {
        $staff = Auth::guard('staff')->user();

        $txn = Transaction::where('id', $id)
            ->where('initiated_by', $staff->id)   // only own transactions
            ->where('status', 'pending_approval')  // only pending ones
            ->firstOrFail();

        DB::transaction(function () use ($txn, $staff) {
            $txn->update([
                'status'           => 'cancelled',
                'rejection_reason' => 'Cancelled by teller before supervisor action.',
            ]);

            PendingApproval::where('transaction_id', $txn->id)
                ->where('status', 'pending')
                ->update([
                    'status'     => 'cancelled',
                    'decided_by' => $staff->id,
                    'decided_at' => now(),
                ]);
        });

        return back()->with('success', "Request {$txn->reference} has been cancelled.");
    }

    /**
     * Build paginated transaction history for this teller + type.
     * Attaches account/customer info from ledger entries.
     */
    private function buildHistory(int $staffId, string $type, Request $request)
    {
        $query = Transaction::where('initiated_by', $staffId)
            ->where('type', $type)
            ->orderBy('created_at', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $paginated = $query->paginate(10)->withQueryString();

        $paginated->getCollection()->transform(function ($txn) use ($type) {
            // Can only reverse a fully completed, non-reversed, non-reversal transaction
            $txn->can_reverse = $txn->status === 'completed'
                && !$txn->is_reversed
                && is_null($txn->reversal_of);
            $entries = LedgerEntry::with('account.customer')
                ->where('transaction_id', $txn->id)
                ->get();

            if ($type === 'deposit') {
                $acc = $entries->firstWhere('entry_type', 'credit')?->account;
                $txn->customer_name   = $acc?->customer?->full_name ?? '—';
                $txn->account_number  = $acc?->account_number ?? '—';
                $txn->balance_after   = $entries->firstWhere('entry_type', 'credit')?->balance_after ?? 0;
            } elseif ($type === 'withdrawal') {
                $acc = $entries->firstWhere('entry_type', 'debit')?->account;
                $txn->customer_name   = $acc?->customer?->full_name ?? '—';
                $txn->account_number  = $acc?->account_number ?? '—';
                $txn->balance_after   = $entries->firstWhere('entry_type', 'debit')?->balance_after ?? 0;
            } elseif ($type === 'transfer') {
                $from = $entries->firstWhere('entry_type', 'debit')?->account;
                $to   = $entries->firstWhere('entry_type', 'credit')?->account;
                $txn->from_account_number = $from?->account_number ?? '—';
                $txn->from_customer       = $from?->customer?->full_name ?? '—';
                $txn->to_account_number   = $to?->account_number ?? '—';
                $txn->to_customer         = $to?->customer?->full_name ?? '—';
            }

            return $txn;
        });

        return $paginated;
    }

    /** Totals for today */
    private function todayTotal(int $staffId, string $type): float
    {
        return (float) Transaction::where('initiated_by', $staffId)
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    /** Account lookup — used by the form to verify before submitting */
    public function lookupAccount(Request $request)
    {
        $request->validate(['account_number' => 'required|string']);

        $account = Account::with('customer', 'accountType')
            ->where('account_number', $request->account_number)
            ->first();

        if (!$account) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'          => true,
            'account_number' => $account->account_number,
            'customer_name'  => $account->customer?->full_name ?? '—',
            'account_type'   => $account->accountType?->type_name ?? '—',
            'balance'        => $account->balance,
            'status'         => $account->status,
        ]);
    }

    /* ─────────────── TRANSACTION HISTORY PAGE ─────────────── */
    public function historyPage(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $query = Transaction::where('initiated_by', $staff->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $paginated = $query->paginate(20)->withQueryString();

        $paginated->getCollection()->transform(function ($txn) {
            $txn->can_reverse = $txn->status === 'completed'
                && !$txn->is_reversed
                && is_null($txn->reversal_of);

            $entries = LedgerEntry::with('account.customer')
                ->where('transaction_id', $txn->id)
                ->get();

            if ($txn->type === 'deposit') {
                $acc = $entries->firstWhere('entry_type', 'credit')?->account;
                $txn->customer_name  = $acc?->customer?->full_name ?? '—';
                $txn->account_number = $acc?->account_number ?? '—';
            } elseif ($txn->type === 'withdrawal') {
                $acc = $entries->firstWhere('entry_type', 'debit')?->account;
                $txn->customer_name  = $acc?->customer?->full_name ?? '—';
                $txn->account_number = $acc?->account_number ?? '—';
            } elseif ($txn->type === 'transfer') {
                $from = $entries->firstWhere('entry_type', 'debit')?->account;
                $to   = $entries->firstWhere('entry_type', 'credit')?->account;
                $txn->customer_name  = $from?->customer?->full_name ?? '—';
                $txn->account_number = $from?->account_number ?? '—';
                $txn->to_account_number = $to?->account_number ?? '—';
                $txn->to_customer       = $to?->customer?->full_name ?? '—';
            }

            return $txn;
        });

        // Summary counts for today
        $todaySummary = Transaction::where('initiated_by', $staff->id)
            ->whereDate('created_at', today())
            ->selectRaw("type, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total")
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return Inertia::render('Staff/Teller/TransactionHistory', [
            'history'      => $paginated,
            'filters'      => $request->only('type', 'status', 'date_from', 'date_to'),
            'today_summary' => [
                'deposit'    => ['count' => $todaySummary['deposit']->cnt    ?? 0, 'total' => $todaySummary['deposit']->total    ?? 0],
                'withdrawal' => ['count' => $todaySummary['withdrawal']->cnt ?? 0, 'total' => $todaySummary['withdrawal']->total ?? 0],
                'transfer'   => ['count' => $todaySummary['transfer']->cnt   ?? 0, 'total' => $todaySummary['transfer']->total   ?? 0],
            ],
        ]);
    }

    /* ─────────────── DEPOSIT PAGE ─────────────── */
    public function depositPage(Request $request)
    {
        $staff   = Auth::guard('staff')->user();
        $history = $this->buildHistory($staff->id, 'deposit', $request);

        return Inertia::render('Staff/Teller/Deposit', [
            'history'         => $history,
            'today_total'     => $this->todayTotal($staff->id, 'deposit'),
            'filters'         => $request->only('date_from', 'date_to'),
            'receipt'         => session('receipt'),
            'pending_receipt' => session('pending_receipt'),
        ]);
    }

    /* ─────────────── WITHDRAW PAGE ─────────────── */
    public function withdrawPage(Request $request)
    {
        $staff   = Auth::guard('staff')->user();
        $history = $this->buildHistory($staff->id, 'withdrawal', $request);

        return Inertia::render('Staff/Teller/Withdraw', [
            'history'         => $history,
            'today_total'     => $this->todayTotal($staff->id, 'withdrawal'),
            'filters'         => $request->only('date_from', 'date_to'),
            'receipt'         => session('receipt'),
            'pending_receipt' => session('pending_receipt'),
        ]);
    }

    /* ─────────────── TRANSFER PAGE ─────────────── */
    public function transferPage(Request $request)
    {
        $staff   = Auth::guard('staff')->user();
        $history = $this->buildHistory($staff->id, 'transfer', $request);

        return Inertia::render('Staff/Teller/Transfer', [
            'history'         => $history,
            'today_total'     => $this->todayTotal($staff->id, 'transfer'),
            'filters'         => $request->only('date_from', 'date_to'),
            'receipt'         => session('receipt'),
            'pending_receipt' => session('pending_receipt'),
        ]);
    }
}
