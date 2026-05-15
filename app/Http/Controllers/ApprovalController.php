<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingApproval;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ApprovalController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/approvals
     * ───────────────────────────────────────────── */
    public function index()
    {
        $staff = Auth::guard('staff')->user();

        /* ── Pending queue ── */
        $pending = Transaction::with(['initiator', 'primaryAccount.customer', 'secondaryAccount.customer'])
            ->where('status', 'pending_approval')
            ->where('branch_id', $staff->branch_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($tx) => [
                'id'             => $tx->id,
                'reference'      => $tx->reference,
                'type'           => $tx->type,
                'amount'         => $tx->amount,
                'created_at'     => $tx->created_at,
                'initiated_by'   => $tx->initiator?->full_name ?? '—',
                'initiator_role' => $tx->initiator?->role?->role_name ?? '—',
                'account_number'    => $tx->primaryAccount?->account_number ?? '—',
                'customer_name'     => $tx->primaryAccount?->customer?->full_name ?? '—',
                'current_balance'   => $tx->primaryAccount?->balance ?? 0,
                'to_account_number' => $tx->type === 'transfer'
                    ? ($tx->secondaryAccount?->account_number ?? '—') : null,
                'to_customer_name'  => $tx->type === 'transfer'
                    ? ($tx->secondaryAccount?->customer?->full_name ?? '—') : null,
            ]);

        /* ── Decision history (decisions made by this supervisor) ── */
        $history = PendingApproval::with(['transaction.primaryAccount.customer', 'transaction.secondaryAccount.customer', 'transaction.initiator'])
            ->where('decided_by', $staff->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('decided_at')
            ->limit(50)
            ->get()
            ->map(fn($pa) => [
                'id'               => $pa->id,
                'reference'        => $pa->transaction?->reference ?? '—',
                'type'             => $pa->transaction?->type ?? '—',
                'amount'           => $pa->transaction?->amount ?? 0,
                'decision'         => $pa->status,
                'decision_note'    => $pa->decision_note,
                'rejection_reason' => $pa->transaction?->rejection_reason,
                'decided_at'       => $pa->decided_at,
                'submitted_at'     => $pa->created_at,
                'teller_name'      => $pa->transaction?->initiator?->full_name ?? '—',
                'teller_role'      => $pa->transaction?->initiator?->role?->role_name ?? '—',
                'customer_name'    => $pa->transaction?->primaryAccount?->customer?->full_name ?? '—',
                'account_number'   => $pa->transaction?->primaryAccount?->account_number ?? '—',
                'to_customer_name' => $pa->transaction?->type === 'transfer'
                    ? ($pa->transaction?->secondaryAccount?->customer?->full_name ?? '—') : null,
                'to_account_number'=> $pa->transaction?->type === 'transfer'
                    ? ($pa->transaction?->secondaryAccount?->account_number ?? '—') : null,
            ]);

        return \Inertia\Inertia::render('Staff/SupervisorQueue', [
            'transactions' => $pending,
            'history'      => $history,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/approvals/{id}/approve
     * ───────────────────────────────────────────── */
    public function approve(Request $request, int $id)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $staff       = Auth::guard('staff')->user();
        $transaction = Transaction::findOrFail($id);

        if ($transaction->status !== 'pending_approval') {
            return back()->withErrors(['error' => 'This transaction is no longer pending approval.']);
        }

        if ($transaction->branch_id !== $staff->branch_id) {
            return back()->withErrors(['error' => 'You can only approve transactions from your branch.']);
        }

        try {
            app(LedgerService::class)->postLedgerEntries($transaction);

            PendingApproval::where('transaction_id', $id)->update([
                'status'        => 'approved',
                'decided_by'    => $staff->id,
                'decided_at'    => now(),
                'decision_note' => $request->note ?: null,
            ]);

            return back()->with('success',
                "✓ {$transaction->reference} approved — \${$transaction->amount} posted to ledger."
            );

        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Could not post ledger: ' . $e->getMessage()]);
        }
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/approvals/{id}/reject
     * ───────────────────────────────────────────── */
    public function reject(Request $request, int $id)
    {
        $staff       = Auth::guard('staff')->user();
        $transaction = Transaction::findOrFail($id);

        if ($transaction->branch_id !== $staff->branch_id) {
            return back()->withErrors(['error' => 'You can only reject transactions from your branch.']);
        }

        $reason = $request->input('reason', 'Rejected by supervisor.');

        $transaction->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        PendingApproval::where('transaction_id', $id)->update([
            'status'        => 'rejected',
            'decided_by'    => $staff->id,
            'decided_at'    => now(),
            'decision_note' => $reason,
        ]);

        return back()->with('success',
            "✗ {$transaction->reference} rejected — no funds were moved."
        );
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/approvals/bulk-approve
     * ───────────────────────────────────────────── */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids'  => 'required|array|min:1',
            'ids.*' => 'integer',
            'note' => 'nullable|string|max:1000',
        ]);

        $staff   = Auth::guard('staff')->user();
        $approved = 0;
        $failed   = 0;

        DB::transaction(function () use ($request, $staff, &$approved, &$failed) {
            foreach ($request->ids as $id) {
                try {
                    $transaction = Transaction::where('id', $id)
                        ->where('branch_id', $staff->branch_id)
                        ->where('status', 'pending_approval')
                        ->first();

                    if (!$transaction) { $failed++; continue; }

                    app(LedgerService::class)->postLedgerEntries($transaction);

                    PendingApproval::where('transaction_id', $id)->update([
                        'status'        => 'approved',
                        'decided_by'    => $staff->id,
                        'decided_at'    => now(),
                        'decision_note' => $request->note ?: null,
                    ]);

                    $approved++;
                } catch (Exception $e) {
                    $failed++;
                }
            }
        });

        $msg = "✓ {$approved} transaction(s) approved and posted to ledger.";
        if ($failed) $msg .= " {$failed} could not be processed.";

        return back()->with('success', $msg);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/approvals/bulk-reject
     * ───────────────────────────────────────────── */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'reason' => 'nullable|string|max:1000',
        ]);

        $staff    = Auth::guard('staff')->user();
        $reason   = $request->reason ?: 'Bulk rejected by supervisor.';
        $rejected = 0;

        foreach ($request->ids as $id) {
            $transaction = Transaction::where('id', $id)
                ->where('branch_id', $staff->branch_id)
                ->where('status', 'pending_approval')
                ->first();

            if (!$transaction) continue;

            $transaction->update([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
            ]);

            PendingApproval::where('transaction_id', $id)->update([
                'status'        => 'rejected',
                'decided_by'    => $staff->id,
                'decided_at'    => now(),
                'decision_note' => $reason,
            ]);

            $rejected++;
        }

        return back()->with('success', "✗ {$rejected} transaction(s) rejected — no funds were moved.");
    }
}
