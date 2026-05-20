<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Services\NotificationService;
use Carbon\Carbon;
use Inertia\Inertia;
use Exception;

class LoanController extends Controller
{
    private function staff()
    {
        return Auth::guard('staff')->user()->load('role', 'branch');
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/loans
     * ───────────────────────────────────────────── */
    public function index(Request $request)
    {
        $staff = $this->staff();

        $query = Loan::with(['customer', 'account', 'approvedBy', 'reviewedBy'])
            ->whereHas('account', fn($q) => $q->where('home_branch_id', $staff->branch_id));

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($search = $request->search) {
            $query->whereHas('customer', fn($q) =>
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
            );
        }

        $loans = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($loan) => $this->formatLoan($loan));

        $stats = [
            'applied'      => Loan::whereHas('account', fn($q) => $q->where('home_branch_id', $staff->branch_id))->where('status', 'applied')->count(),
            'under_review' => Loan::whereHas('account', fn($q) => $q->where('home_branch_id', $staff->branch_id))->where('status', 'under_review')->count(),
            'approved'     => Loan::whereHas('account', fn($q) => $q->where('home_branch_id', $staff->branch_id))->where('status', 'approved')->count(),
            'disbursed'    => Loan::whereHas('account', fn($q) => $q->where('home_branch_id', $staff->branch_id))->where('status', 'disbursed')->count(),
        ];

        return Inertia::render('Staff/Loans/Index', [
            'loans'   => $loans,
            'stats'   => $stats,
            'filters' => $request->only('status', 'search'),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/loans/{id}
     * ───────────────────────────────────────────── */
    public function show(int $id)
    {
        $staff = $this->staff();
        $loan  = Loan::with([
            'customer',
            'account.accountType',
            'account.homeBranch',
            'approvedBy',
            'reviewedBy',
            'repayments',
        ])->findOrFail($id);

        // Security: only show loans from this branch
        if ($loan->account?->home_branch_id !== $staff->branch_id) {
            abort(403, 'Access denied.');
        }

        return Inertia::render('Staff/Loans/Show', [
            'loan' => $this->formatLoanDetail($loan),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/loans/{id}/review
     *  Loan Officer acknowledges and starts review
     * ───────────────────────────────────────────── */
    public function review(Request $request, int $id)
    {
        $staff = $this->staff();
        $loan  = Loan::findOrFail($id);

        if ($loan->status !== 'applied') {
            return back()->withErrors(['error' => 'Loan is not in applied status.']);
        }

        $loan->update([
            'status'      => 'under_review',
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
            'notes'       => $request->notes,
        ]);

        app(NotificationService::class)->send(
            recipientId:   $loan->customer_id,
            recipientType: 'customer',
            message:       "Your loan application of \${$loan->amount} is now under review by our team.",
            subject:       'Loan Application Under Review',
        );

        return back()->with('success', 'Loan application moved to under review.');
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/loans/{id}/approve
     * ───────────────────────────────────────────── */
    public function approve(Request $request, int $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $staff = $this->staff();
        $loan  = Loan::with('customer')->findOrFail($id);

        if (!in_array($loan->status, ['applied', 'under_review'])) {
            return back()->withErrors(['error' => 'Loan cannot be approved from its current status.']);
        }

        $loan->update([
            'status'      => 'approved',
            'approved_by' => $staff->id,
            'approved_at' => now(),
            'notes'       => $request->notes,
        ]);

        $amount = number_format($loan->amount, 2);
        app(NotificationService::class)->send(
            recipientId:   $loan->customer_id,
            recipientType: 'customer',
            message:       "Congratulations! Your loan application of \${$amount} has been approved. Disbursement will follow shortly.",
            subject:       'Loan Application Approved',
        );

        return back()->with('success', "Loan #{$id} approved successfully.");
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/loans/{id}/reject
     * ───────────────────────────────────────────── */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $staff = $this->staff();
        $loan  = Loan::with('customer')->findOrFail($id);

        if (!in_array($loan->status, ['applied', 'under_review', 'approved'])) {
            return back()->withErrors(['error' => 'Loan cannot be rejected from its current status.']);
        }

        $loan->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
            'reviewed_by'      => $loan->reviewed_by ?? $staff->id,
            'reviewed_at'      => $loan->reviewed_at ?? now(),
        ]);

        $amount = number_format($loan->amount, 2);
        app(NotificationService::class)->send(
            recipientId:   $loan->customer_id,
            recipientType: 'customer',
            message:       "Your loan application of \${$amount} has been rejected. Reason: {$request->reason}",
            subject:       'Loan Application Rejected',
        );

        return back()->with('success', "Loan #{$id} rejected.");
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/loans/{id}/disburse
     *  Credits customer account, generates repayment schedule
     * ───────────────────────────────────────────── */
    public function disburse(Request $request, int $id)
    {
        $staff = $this->staff();
        $loan  = Loan::with(['customer', 'account'])->findOrFail($id);

        if ($loan->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved loans can be disbursed.']);
        }

        if ($loan->account?->home_branch_id !== $staff->branch_id) {
            return back()->withErrors(['error' => 'Access denied.']);
        }

        try {
            DB::transaction(function () use ($loan, $staff) {
                $vault = Account::where('account_number', 'VAULT-' . $staff->branch_id)->first();
                if (!$vault) {
                    throw new Exception('Branch vault account not found.');
                }

                $amount = (float) $loan->amount;

                // Create disbursement transaction
                $transaction = Transaction::create([
                    'reference'     => 'LDI-' . strtoupper(uniqid()),
                    'type'          => 'loan_disbursement',
                    'status'        => 'completed',
                    'amount'        => $amount,
                    'account_id'    => $loan->account_id,
                    'to_account_id' => $vault->id,
                    'initiated_by'  => $staff->id,
                    'branch_id'     => $staff->branch_id,
                    'description'   => "Loan disbursement — Loan #{$loan->id}",
                ]);

                // Debit vault, credit customer
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $vault->id,
                    'branch_id'      => $staff->branch_id,
                    'entry_type'     => 'debit',
                    'amount'         => $amount,
                    'balance_after'  => $vault->balance - $amount,
                ]);
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $loan->account_id,
                    'branch_id'      => $staff->branch_id,
                    'entry_type'     => 'credit',
                    'amount'         => $amount,
                    'balance_after'  => $loan->account->balance + $amount,
                ]);

                // Update loan status
                $loan->update([
                    'status'       => 'disbursed',
                    'disbursed_at' => now(),
                ]);

                // Generate repayment schedule
                $monthlyPayment = $loan->monthlyPayment();
                $startDate      = now()->addMonth()->startOfMonth();
                for ($i = 0; $i < $loan->term_months; $i++) {
                    LoanRepayment::create([
                        'loan_id'  => $loan->id,
                        'due_date' => $startDate->copy()->addMonths($i)->format('Y-m-d'),
                        'amount'   => $monthlyPayment,
                        'status'   => 'pending',
                    ]);
                }

                // Notify customer
                $amountFmt = number_format($amount, 2);
                app(NotificationService::class)->send(
                    recipientId:   $loan->customer_id,
                    recipientType: 'customer',
                    message:       "\${$amountFmt} has been credited to your account as a loan disbursement. Your repayment schedule starts next month.",
                    subject:       'Loan Disbursed',
                );
            });

            return back()->with('success', "Loan #{$id} disbursed successfully. Repayment schedule generated.");

        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Disbursement failed: ' . $e->getMessage()]);
        }
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/loans/{loan}/repayments/{repayment}/pay
     *  Record a manual repayment
     * ───────────────────────────────────────────── */
    public function recordRepayment(Request $request, int $loanId, int $repaymentId)
    {
        $staff      = $this->staff();
        $loan       = Loan::with('account')->findOrFail($loanId);
        $repayment  = LoanRepayment::findOrFail($repaymentId);

        if ($repayment->loan_id !== $loan->id) {
            abort(404);
        }

        if ($repayment->status === 'paid') {
            return back()->withErrors(['error' => 'This installment is already paid.']);
        }

        if ($loan->status !== 'disbursed') {
            return back()->withErrors(['error' => 'Loan is not in disbursed state.']);
        }

        try {
            DB::transaction(function () use ($loan, $repayment, $staff) {
                $vault  = Account::where('account_number', 'VAULT-' . $staff->branch_id)->firstOrFail();
                $amount = (float) $repayment->amount;

                // Customer's account must have enough balance
                if ($loan->account->balance < $amount) {
                    throw new Exception('Insufficient balance in customer account for repayment.');
                }

                // Repayment transaction
                $transaction = Transaction::create([
                    'reference'     => 'LRP-' . strtoupper(uniqid()),
                    'type'          => 'loan_repayment',
                    'status'        => 'completed',
                    'amount'        => $amount,
                    'account_id'    => $loan->account_id,
                    'to_account_id' => $vault->id,
                    'initiated_by'  => $staff->id,
                    'branch_id'     => $staff->branch_id,
                    'description'   => "Loan repayment — Loan #{$loan->id}, Installment #{$repayment->id}",
                ]);

                // Debit customer, credit vault
                $entry = LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $loan->account_id,
                    'branch_id'      => $staff->branch_id,
                    'entry_type'     => 'debit',
                    'amount'         => $amount,
                    'balance_after'  => $loan->account->balance - $amount,
                ]);
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $vault->id,
                    'branch_id'      => $staff->branch_id,
                    'entry_type'     => 'credit',
                    'amount'         => $amount,
                    'balance_after'  => $vault->balance + $amount,
                ]);

                $repayment->update([
                    'status'          => 'paid',
                    'paid_at'         => now(),
                    'ledger_entry_id' => $entry->id,
                ]);

                // Check if all installments paid → close loan
                $remaining = LoanRepayment::where('loan_id', $loan->id)
                    ->where('status', 'pending')->count();
                if ($remaining === 0) {
                    $loan->update(['status' => 'closed']);
                    app(NotificationService::class)->send(
                        recipientId:   $loan->customer_id,
                        recipientType: 'customer',
                        message:       "Congratulations! Your loan #{$loan->id} has been fully repaid and is now closed.",
                        subject:       'Loan Fully Repaid',
                    );
                } else {
                    $amtFmt = number_format($amount, 2);
                    app(NotificationService::class)->send(
                        recipientId:   $loan->customer_id,
                        recipientType: 'customer',
                        message:       "Loan repayment of \${$amtFmt} recorded for Loan #{$loan->id}. {$remaining} installment(s) remaining.",
                        subject:       'Loan Repayment Recorded',
                    );
                }
            });

            return back()->with('success', 'Repayment recorded successfully.');

        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Repayment failed: ' . $e->getMessage()]);
        }
    }

    /* ─────────────────────────────────────────────
     *  Helpers
     * ───────────────────────────────────────────── */
    private function formatLoan(Loan $loan): array
    {
        return [
            'id'              => $loan->id,
            'customer_name'   => $loan->customer?->full_name ?? '—',
            'customer_phone'  => $loan->customer?->phone ?? '—',
            'account_number'  => $loan->account?->account_number ?? '—',
            'amount'          => $loan->amount,
            'interest_rate'   => $loan->interest_rate,
            'term_months'     => $loan->term_months,
            'purpose'         => $loan->purpose,
            'status'          => $loan->status,
            'status_color'    => $loan->statusColor(),
            'created_at'      => $loan->created_at?->format('d M Y'),
            'disbursed_at'    => $loan->disbursed_at?->format('d M Y'),
            'approved_by'     => $loan->approvedBy?->full_name,
        ];
    }

    private function formatLoanDetail(Loan $loan): array
    {
        return array_merge($this->formatLoan($loan), [
            'notes'              => $loan->notes,
            'rejection_reason'   => $loan->rejection_reason,
            'reviewed_by'        => $loan->reviewedBy?->full_name,
            'reviewed_at'        => $loan->reviewed_at?->format('d M Y, H:i'),
            'approved_at'        => $loan->approved_at?->format('d M Y, H:i'),
            'monthly_payment'    => $loan->monthlyPayment(),
            'total_repayable'    => $loan->totalRepayable(),
            'amount_paid'        => $loan->amountPaid(),
            'remaining_balance'  => $loan->remainingBalance(),
            'account_type'       => $loan->account?->accountType?->type_name ?? '—',
            'branch_name'        => $loan->account?->homeBranch?->branch_name ?? '—',
            'repayments'         => $loan->repayments->map(fn($r) => [
                'id'       => $r->id,
                'due_date' => $r->due_date?->format('d M Y'),
                'amount'   => $r->amount,
                'status'   => $r->status,
                'paid_at'  => $r->paid_at?->format('d M Y, H:i'),
                'overdue'  => $r->isOverdue(),
            ]),
        ]);
    }
}
