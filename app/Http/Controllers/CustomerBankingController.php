<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Services\NotificationService;
use Inertia\Inertia;

class CustomerBankingController extends Controller
{
    private function customer()
    {
        return Auth::guard('customers')->user();
    }

    private function activeAccounts()
    {
        return Account::where('customer_id', $this->customer()->id)
            ->with('accountType')
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->get()
            ->map(fn($acc) => [
                'id'             => $acc->id,
                'account_number' => $acc->account_number,
                'type_name'      => $acc->accountType?->type_name ?? '—',
                'balance'        => $acc->balance,
            ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/deposit
     * ───────────────────────────────────────────── */
    public function showDeposit()
    {
        return Inertia::render('Customer/Deposit', [
            'accounts' => $this->activeAccounts(),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/deposit
     *  Creates a pending deposit request — a teller
     *  will process it when the customer arrives at
     *  the branch with physical cash.
     * ───────────────────────────────────────────── */
    public function submitDeposit(Request $request)
    {
        $customer = $this->customer();

        $request->validate([
            'account_id'  => ['required', 'integer'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::where('id', $request->account_id)
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->firstOrFail();

        $amount      = (float) $request->amount;
        $description = $request->description ?: 'Customer deposit request';

        // Create a pending deposit transaction (no ledger entries — teller posts them)
        $transaction = Transaction::create([
            'reference'    => 'CDEP-' . strtoupper(uniqid()),
            'type'         => 'deposit',
            'status'       => 'pending',
            'amount'       => $amount,
            'account_id'   => $account->id,
            'to_account_id'=> null,
            'initiated_by' => null,
            'branch_id'    => $account->home_branch_id,
            'description'  => $description,
        ]);

        // Notify customer
        app(NotificationService::class)->send(
            $customer->id,
            'customer',
            'Your deposit request of $' . number_format($amount, 2) . ' for account ' . $account->account_number . ' has been received. Please visit any branch to complete the deposit.',
            'Deposit Request Received',
            'transaction-completed',
            [
                'customer_name' => $customer->full_name,
                'type'          => 'Deposit Request',
                'amount'        => number_format($amount, 2),
                'reference'     => $transaction->reference,
                'description'   => $description,
            ]
        );

        return redirect()->route('customer.deposit')
            ->with('success', 'Deposit request of $' . number_format($amount, 2) . ' submitted. Please visit any branch to complete.');
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/withdraw
     * ───────────────────────────────────────────── */
    public function showWithdraw()
    {
        return Inertia::render('Customer/Withdraw', [
            'accounts' => $this->activeAccounts(),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/withdraw
     *  Validates balance, debits account immediately,
     *  creates a completed withdrawal transaction.
     * ───────────────────────────────────────────── */
    public function submitWithdraw(Request $request)
    {
        $customer = $this->customer();

        $request->validate([
            'account_id'  => ['required', 'integer'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::where('id', $request->account_id)
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->with('accountType')
            ->firstOrFail();

        $amount = (float) $request->amount;

        // Balance / overdraft check
        $type = $account->accountType;
        if (!$type?->overdraft_allowed) {
            if ($account->balance < $amount) {
                return back()->withErrors([
                    'amount' => 'Insufficient balance. Available: $' . number_format($account->balance, 2),
                ])->withInput();
            }
        } else {
            $floor = $type->overdraft_limit !== null ? -(float)$type->overdraft_limit : -INF;
            if (($account->balance - $amount) < $floor) {
                return back()->withErrors([
                    'amount' => 'Withdrawal exceeds overdraft limit of $' . number_format($type->overdraft_limit, 2),
                ])->withInput();
            }
        }

        $description = $request->description ?: 'Customer online withdrawal request';

        try {
            DB::transaction(function () use ($account, $amount, $description, $customer) {
                $transaction = Transaction::create([
                    'reference'     => 'CWTH-' . strtoupper(uniqid()),
                    'type'          => 'withdraw',
                    'status'        => 'completed',
                    'amount'        => $amount,
                    'account_id'    => $account->id,
                    'to_account_id' => null,
                    'initiated_by'  => null,
                    'branch_id'     => $account->home_branch_id,
                    'description'   => $description,
                ]);

                // Debit customer account
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $account->id,
                    'branch_id'      => $account->home_branch_id,
                    'entry_type'     => 'debit',
                    'amount'         => $amount,
                    'balance_after'  => $account->balance - $amount,
                ]);

                // Notify
                app(NotificationService::class)->send(
                    $customer->id,
                    'customer',
                    'Withdrawal of $' . number_format($amount, 2) . ' from account ' . $account->account_number . ' completed. Visit a branch to collect your cash.',
                    'Withdrawal Processed',
                    'transaction-completed',
                    [
                        'customer_name' => $customer->full_name,
                        'type'          => 'Withdrawal',
                        'amount'        => number_format($amount, 2),
                        'reference'     => $transaction->reference,
                        'description'   => $description,
                    ]
                );
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Withdrawal failed: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('customer.withdraw')
            ->with('success', 'Withdrawal of $' . number_format($amount, 2) . ' processed. Visit any branch to collect your cash.');
    }
}
