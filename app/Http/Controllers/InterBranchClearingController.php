<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\InterBranchClearing;
use App\Models\Branch;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InterBranchClearingController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/branch/clearing
     * ───────────────────────────────────────────── */
    public function index(Request $request)
    {
        $staff            = Auth::guard('staff')->user()->load('role');
        $branchRestricted = $staff->role?->branch_restricted ?? true;

        $query = InterBranchClearing::with([
            'fromBranch', 'toBranch',
            'transaction.primaryAccount.customer',
            'settledBy',
        ]);

        // Branch-restricted staff only see entries involving their branch
        if ($branchRestricted) {
            $query->where(function ($q) use ($staff) {
                $q->where('from_branch_id', $staff->branch_id)
                  ->orWhere('to_branch_id', $staff->branch_id);
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($from = $request->date_from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->date_to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $entries = $query->latest()->paginate(25)->withQueryString();

        // Summary stats
        $statsBase = InterBranchClearing::query();
        if ($branchRestricted) {
            $statsBase->where(function ($q) use ($staff) {
                $q->where('from_branch_id', $staff->branch_id)
                  ->orWhere('to_branch_id', $staff->branch_id);
            });
        }

        $stats = [
            'pending'     => (clone $statsBase)->where('status', 'pending')->count(),
            'settled'     => (clone $statsBase)->where('status', 'settled')->count(),
            'total_value' => (float)(clone $statsBase)->where('status', 'pending')->sum('amount'),
        ];

        $branches = Branch::orderBy('branch_name')->get(['id', 'branch_name', 'branch_code']);

        return Inertia::render('Staff/BranchManager/Clearing', [
            'entries'  => $entries,
            'stats'    => $stats,
            'branches' => $branches,
            'filters'  => $request->only('status', 'date_from', 'date_to'),
            'my_branch_id' => $staff->branch_id,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/branch/clearing/{id}/settle
     * ───────────────────────────────────────────── */
    public function settle(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $staff            = Auth::guard('staff')->user()->load('role');
        $branchRestricted = $staff->role?->branch_restricted ?? true;

        $query = InterBranchClearing::where('status', 'pending');
        if ($branchRestricted) {
            $query->where(function ($q) use ($staff) {
                $q->where('from_branch_id', $staff->branch_id)
                  ->orWhere('to_branch_id', $staff->branch_id);
            });
        }

        $entry = $query->findOrFail($id);

        DB::transaction(function () use ($entry, $staff, $request) {
            /* ── Vault accounts for both branches ── */
            $fromVault = Account::where('account_number', 'VAULT-' . $entry->from_branch_id)->first();
            $toVault   = Account::where('account_number', 'VAULT-' . $entry->to_branch_id)->first();

            if (!$fromVault || !$toVault) {
                throw new \Exception(
                    'Vault account not found for one or both branches. ' .
                    'Make sure at least one teller transaction has been processed in each branch.'
                );
            }

            /* ── GL: DR Sending-Vault / CR Receiving-Vault ──────────────────
             *  When the Branch Manager confirms cash was physically delivered,
             *  we record the vault movement:
             *   • Bosaso vault  → DEBIT  (cash physically left Bosaso)
             *   • Garowe vault  → CREDIT (cash physically arrived at Garowe)
             * ─────────────────────────────────────────────────────────────── */
            LedgerEntry::create([
                'transaction_id' => $entry->transaction_id,
                'account_id'     => $fromVault->id,
                'branch_id'      => $entry->from_branch_id,
                'entry_type'     => 'debit',
                'amount'         => $entry->amount,
                'balance_after'  => $fromVault->balance - $entry->amount,
            ]);

            LedgerEntry::create([
                'transaction_id' => $entry->transaction_id,
                'account_id'     => $toVault->id,
                'branch_id'      => $entry->to_branch_id,
                'entry_type'     => 'credit',
                'amount'         => $entry->amount,
                'balance_after'  => $toVault->balance + $entry->amount,
            ]);

            /* ── Update vault balances ── */
            $fromVault->decrement('balance', $entry->amount);
            $toVault->increment('balance', $entry->amount);

            /* ── Mark clearing entry settled ── */
            $entry->update([
                'status'     => 'settled',
                'settled_at' => now(),
                'settled_by' => $staff->id,
                'notes'      => $request->notes,
            ]);
        });

        $fromCode = $entry->fromBranch?->full_code ?: ($entry->fromBranch?->branch_name ?? "Branch #{$entry->from_branch_id}");
        $toCode   = $entry->toBranch?->full_code  ?: ($entry->toBranch?->branch_name   ?? "Branch #{$entry->to_branch_id}");

        return back()->with(
            'success',
            "Clearing #{$entry->id} settled — vault transfer {$fromCode} → {$toCode} posted to ledger."
        );
    }
}
