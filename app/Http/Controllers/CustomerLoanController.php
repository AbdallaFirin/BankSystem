<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Loan;
use App\Models\Account;
use App\Services\NotificationService;
use Inertia\Inertia;

class CustomerLoanController extends Controller
{
    private function customer()
    {
        return Auth::guard('customers')->user();
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/loans
     * ───────────────────────────────────────────── */
    public function index()
    {
        $customer = $this->customer();

        $loans = Loan::with(['account.accountType', 'repayments'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($loan) => $this->formatLoan($loan));

        return Inertia::render('Customer/Loans/Index', [
            'loans' => $loans,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/loans/apply
     * ───────────────────────────────────────────── */
    public function applyForm()
    {
        $customer = $this->customer()->load('accounts.accountType');

        $eligibleAccounts = $customer->accounts
            ->where('status', 'active')
            ->whereNull('is_frozen')
            ->filter(fn($acc) => !$acc->is_frozen)
            ->values()
            ->map(fn($acc) => [
                'id'             => $acc->id,
                'account_number' => $acc->account_number,
                'type_name'      => $acc->accountType?->type_name ?? '—',
                'balance'        => $acc->balance,
            ]);

        return Inertia::render('Customer/Loans/Apply', [
            'accounts' => $eligibleAccounts,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/loans/apply
     * ───────────────────────────────────────────── */
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'account_id'    => 'required|integer|exists:accounts,id',
            'amount'        => 'required|numeric|min:100|max:500000',
            'term_months'   => 'required|integer|in:3,6,12,18,24,36,48,60',
            'purpose'       => 'required|string|max:500',
        ]);

        $customer = $this->customer();

        // Ensure the account belongs to this customer
        $account = Account::where('id', $validated['account_id'])
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->first();

        if (!$account) {
            return back()->withErrors(['account_id' => 'Invalid account selected.']);
        }

        // Prevent multiple active loan applications on the same account
        $existingActive = Loan::where('account_id', $account->id)
            ->whereIn('status', ['applied', 'under_review', 'approved', 'disbursed'])
            ->exists();
        if ($existingActive) {
            return back()->withErrors(['account_id' => 'This account already has an active loan application.']);
        }

        Loan::create([
            'customer_id'   => $customer->id,
            'account_id'    => $account->id,
            'amount'        => $validated['amount'],
            'interest_rate' => 15.0, // Default rate; loan officer may adjust
            'term_months'   => $validated['term_months'],
            'purpose'       => $validated['purpose'],
            'status'        => 'applied',
        ]);

        app(NotificationService::class)->send(
            recipientId:   $customer->id,
            recipientType: 'customer',
            message:       "Your loan application of \$" . number_format($validated['amount'], 2) . " has been submitted and is being processed.",
            subject:       'Loan Application Received',
        );

        return redirect()->route('customer.loans.index')
            ->with('success', 'Your loan application has been submitted successfully.');
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/loans/{id}
     * ───────────────────────────────────────────── */
    public function show(int $id)
    {
        $customer = $this->customer();

        $loan = Loan::with(['account.accountType', 'repayments'])
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        return Inertia::render('Customer/Loans/Show', [
            'loan' => $this->formatLoanDetail($loan),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  Helpers
     * ───────────────────────────────────────────── */
    private function formatLoan(Loan $loan): array
    {
        return [
            'id'             => $loan->id,
            'amount'         => $loan->amount,
            'interest_rate'  => $loan->interest_rate,
            'term_months'    => $loan->term_months,
            'purpose'        => $loan->purpose,
            'status'         => $loan->status,
            'status_color'   => $loan->statusColor(),
            'account_number' => $loan->account?->account_number ?? '—',
            'type_name'      => $loan->account?->accountType?->type_name ?? '—',
            'monthly_payment'=> $loan->monthlyPayment(),
            'total_repayable'=> $loan->totalRepayable(),
            'amount_paid'    => $loan->amountPaid(),
            'remaining'      => $loan->remainingBalance(),
            'disbursed_at'   => $loan->disbursed_at?->format('d M Y'),
            'created_at'     => $loan->created_at?->format('d M Y'),
            'repayments_count' => $loan->repayments->count(),
            'paid_count'     => $loan->repayments->where('status', 'paid')->count(),
        ];
    }

    private function formatLoanDetail(Loan $loan): array
    {
        return array_merge($this->formatLoan($loan), [
            'rejection_reason' => $loan->rejection_reason,
            'repayments' => $loan->repayments->map(fn($r) => [
                'id'       => $r->id,
                'due_date' => $r->due_date?->format('d M Y'),
                'amount'   => $r->amount,
                'status'   => $r->status,
                'paid_at'  => $r->paid_at?->format('d M Y'),
                'overdue'  => $r->isOverdue(),
            ]),
        ]);
    }
}
