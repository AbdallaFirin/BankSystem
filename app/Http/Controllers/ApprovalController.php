<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LedgerEntry;
use App\Models\PendingApproval;
use App\Models\Staff;
use App\Models\Transaction;
use App\Services\LedgerService;
use App\Services\NotificationService;
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
        $pendingTxns = Transaction::with(['initiator.role', 'primaryAccount.customer', 'secondaryAccount.customer'])
            ->where('status', 'pending_approval')
            ->where('branch_id', $staff->branch_id)
            ->orderByDesc('created_at')
            ->get();

        // Compute live balances for all involved accounts in one query
        // (accounts table has no balance column — balances live in ledger_entries)
        $accountIds = $pendingTxns
            ->flatMap(fn($tx) => array_filter([
                $tx->primaryAccount?->id,
                $tx->secondaryAccount?->id,
            ]))
            ->unique()
            ->values();

        $balances = LedgerEntry::whereIn('account_id', $accountIds)
            ->selectRaw('account_id, SUM(CASE WHEN entry_type = "credit" THEN amount ELSE -amount END) as balance')
            ->groupBy('account_id')
            ->pluck('balance', 'account_id');

        $pending = $pendingTxns->map(fn($tx) => [
            'id'             => $tx->id,
            'reference'      => $tx->reference,
            'type'           => $tx->type,
            'amount'         => $tx->amount,
            'created_at'     => $tx->created_at,
            'initiated_by'   => $tx->initiator?->full_name ?? '—',
            'initiator_role' => $tx->initiator?->role?->role_name ?? '—',
            'account_number'    => $tx->primaryAccount?->account_number ?? '—',
            'customer_name'     => $tx->primaryAccount?->customer?->full_name ?? '—',
            'current_balance'   => (float) ($balances[$tx->primaryAccount?->id] ?? 0),
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

        $staff       = Auth::guard('staff')->user()->load('role');
        $transaction = Transaction::with(['primaryAccount.customer'])->findOrFail($id);

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

            $decidedAt = now()->format('d M Y, H:i:s');
            $amount    = number_format($transaction->amount, 2);

            // Notify the customer whose primary account was involved
            $customer = $transaction->primaryAccount?->customer;
            if ($customer) {
                app(NotificationService::class)->send(
                    recipientId:   $customer->id,
                    recipientType: 'customer',
                    message:       "Your transaction of \${$amount} (Ref: {$transaction->reference}) has been approved and processed.",
                    subject:       'Transaction Approved',
                    mailView:      'transaction-decision',
                    mailData:      [
                        'customerName'   => $customer->full_name,
                        'decision'       => 'approved',
                        'reference'      => $transaction->reference,
                        'type'           => ucfirst($transaction->type),
                        'amount'         => $transaction->amount,
                        'accountNumber'  => $transaction->primaryAccount?->account_number ?? '—',
                        'decidedAt'      => $decidedAt,
                        'decisionNote'   => $request->note,
                    ]
                );
            }

            // Notify the teller who submitted the transaction
            if ($transaction->initiated_by) {
                $teller = Staff::find($transaction->initiated_by);
                if ($teller) {
                    $typeLabel = ucfirst($transaction->type);
                    app(NotificationService::class)->send(
                        recipientId:   $teller->id,
                        recipientType: 'staff',
                        message:       "Your {$typeLabel} of \${$amount} (Ref: {$transaction->reference}) has been approved by {$staff->full_name}.",
                        subject:       "Transaction Approved — {$transaction->reference}",
                        mailView:      'staff-notification',
                        mailData:      [
                            'headerTag'  => 'Transaction Approved',
                            'greeting'   => "Hello {$teller->full_name},",
                            'body'       => "Good news! Your submitted transaction has been approved and the funds have been posted to the ledger.",
                            'details'    => [
                                'Reference'   => $transaction->reference,
                                'Type'        => $typeLabel,
                                'Amount'      => "\${$amount}",
                                'Decision'    => 'Approved ✓',
                                'Approved by' => $staff->full_name . ' (' . ($staff->role?->role_name ?? 'Supervisor') . ')',
                                'Approved at' => $decidedAt,
                                'Note'        => $request->note ?: '—',
                            ],
                            'actionNote' => null,
                        ]
                    );
                }
            }

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
        $staff       = Auth::guard('staff')->user()->load('role');
        $transaction = Transaction::with(['primaryAccount.customer'])->findOrFail($id);

        if ($transaction->status !== 'pending_approval') {
            return back()->withErrors(['error' => 'This transaction is no longer pending approval.']);
        }

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

        $decidedAt = now()->format('d M Y, H:i:s');
        $amount    = number_format($transaction->amount, 2);

        // Notify the customer
        $customer = $transaction->primaryAccount?->customer;
        if ($customer) {
            app(NotificationService::class)->send(
                recipientId:   $customer->id,
                recipientType: 'customer',
                message:       "Your transaction of \${$amount} (Ref: {$transaction->reference}) has been rejected. Reason: {$reason}",
                subject:       'Transaction Rejected',
                mailView:      'transaction-decision',
                mailData:      [
                    'customerName'   => $customer->full_name,
                    'decision'       => 'rejected',
                    'reference'      => $transaction->reference,
                    'type'           => ucfirst($transaction->type),
                    'amount'         => $transaction->amount,
                    'accountNumber'  => $transaction->primaryAccount?->account_number ?? '—',
                    'decidedAt'      => $decidedAt,
                    'decisionNote'   => $reason,
                ]
            );
        }

        // Notify the teller who submitted the transaction
        if ($transaction->initiated_by) {
            $teller = Staff::find($transaction->initiated_by);
            if ($teller) {
                $typeLabel = ucfirst($transaction->type);
                app(NotificationService::class)->send(
                    recipientId:   $teller->id,
                    recipientType: 'staff',
                    message:       "Your {$typeLabel} of \${$amount} (Ref: {$transaction->reference}) was rejected. Reason: {$reason}",
                    subject:       "Transaction Rejected — {$transaction->reference}",
                    mailView:      'staff-notification',
                    mailData:      [
                        'headerTag'  => 'Transaction Rejected',
                        'greeting'   => "Hello {$teller->full_name},",
                        'body'       => "Your submitted transaction has been rejected by {$staff->full_name}. No funds have been moved.",
                        'details'    => [
                            'Reference'   => $transaction->reference,
                            'Type'        => $typeLabel,
                            'Amount'      => "\${$amount}",
                            'Decision'    => 'Rejected ✗',
                            'Rejected by' => $staff->full_name . ' (' . ($staff->role?->role_name ?? 'Supervisor') . ')',
                            'Rejected at' => $decidedAt,
                            'Reason'      => $reason,
                        ],
                        'actionNote' => 'Please review the rejection reason and contact your supervisor if you have questions.',
                    ]
                );
            }
        }

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
