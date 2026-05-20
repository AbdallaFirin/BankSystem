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

class CustomerTransferController extends Controller
{
    private function customer()
    {
        return Auth::guard('customers')->user();
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/transfer
     * ───────────────────────────────────────────── */
    public function show()
    {
        $customer = $this->customer();
        $accounts = Account::where('customer_id', $customer->id)
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

        return Inertia::render('Customer/Transfer', [
            'accounts' => $accounts,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/transfer/lookup?account=xxx
     * ───────────────────────────────────────────── */
    public function lookup(Request $request)
    {
        $customer = $this->customer();
        $number   = trim($request->query('account', ''));

        if (!$number) {
            return response()->json(['found' => false]);
        }

        $account = Account::with('customer')
            ->where('account_number', $number)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where('account_number', 'not like', 'VAULT-%')
            ->where('account_number', 'not like', 'TILL-%')
            ->where('account_number', 'not like', 'SYS-%')
            ->first();

        if (!$account) {
            return response()->json(['found' => false, 'message' => 'Account not found or inactive.']);
        }

        return response()->json([
            'found'          => true,
            'account_number' => $account->account_number,
            'holder_name'    => $account->customer?->full_name ?? '—',
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/transfer
     * ───────────────────────────────────────────── */
    public function submit(Request $request)
    {
        $customer = $this->customer();

        $request->validate([
            'from_account_id'    => ['required', 'integer'],
            'transfer_type'      => ['required', 'in:own,external'],
            'to_account_number'  => ['required_if:transfer_type,external', 'nullable', 'string'],
            'to_own_account_id'  => ['required_if:transfer_type,own', 'nullable', 'integer'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'description'        => ['nullable', 'string', 'max:255'],
        ]);

        // Validate source account belongs to this customer and is active
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->with('accountType')
            ->firstOrFail();

        // Resolve destination account
        if ($request->transfer_type === 'own') {
            $toAccount = Account::where('id', $request->to_own_account_id)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->where('is_frozen', false)
                ->firstOrFail();
        } else {
            $toAccount = Account::where('account_number', $request->to_account_number)
                ->where('status', 'active')
                ->where('is_frozen', false)
                ->first();

            if (!$toAccount) {
                return back()->withErrors(['to_account_number' => 'Destination account not found or inactive.'])->withInput();
            }
        }

        // Cannot transfer to same account
        if ($fromAccount->id === $toAccount->id) {
            return back()->withErrors(['to_own_account_id' => 'Source and destination accounts must be different.'])->withInput();
        }

        $amount = (float) $request->amount;

        // Overdraft / balance check
        $type  = $fromAccount->accountType;
        if (!$type?->overdraft_allowed) {
            if ($fromAccount->balance < $amount) {
                return back()->withErrors(['amount' => 'Insufficient balance. Available: $' . number_format($fromAccount->balance, 2)])->withInput();
            }
        } else {
            $floor = $type->overdraft_limit !== null ? -(float)$type->overdraft_limit : -INF;
            if (($fromAccount->balance - $amount) < $floor) {
                return back()->withErrors(['amount' => 'This transfer exceeds your overdraft limit of $' . number_format($type->overdraft_limit, 2)])->withInput();
            }
        }

        $description = $request->description ?: 'Customer Online Transfer';

        try {
            DB::transaction(function () use ($fromAccount, $toAccount, $amount, $description, $customer) {
                $transaction = Transaction::create([
                    'reference'     => 'CTRF-' . strtoupper(uniqid()),
                    'type'          => 'transfer',
                    'status'        => 'completed',
                    'amount'        => $amount,
                    'account_id'    => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'initiated_by'  => null,        // customer-initiated
                    'branch_id'     => $fromAccount->home_branch_id,
                    'description'   => $description,
                ]);

                // Debit sender
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $fromAccount->id,
                    'branch_id'      => $fromAccount->home_branch_id,
                    'entry_type'     => 'debit',
                    'amount'         => $amount,
                    'balance_after'  => $fromAccount->balance - $amount,
                ]);

                // Credit receiver
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $toAccount->id,
                    'branch_id'      => $toAccount->home_branch_id ?? $fromAccount->home_branch_id,
                    'entry_type'     => 'credit',
                    'amount'         => $amount,
                    'balance_after'  => $toAccount->balance + $amount,
                ]);
            });

        } catch (\Exception $e) {
            return back()->with('error', 'Transfer failed: ' . $e->getMessage())->withInput();
        }

        // Send notifications AFTER the catch block — failures here must never roll back the transfer
        try {
            app(NotificationService::class)->send(
                $customer->id,
                'customer',
                'Transfer of $' . number_format($amount, 2) . ' from account ' . $fromAccount->account_number . ' to ' . $toAccount->account_number . ' was completed successfully.',
                'Transfer Confirmed',
                'transaction-completed',
                [
                    'customer_name'    => $customer->full_name,
                    'type'             => 'Transfer',
                    'amount'           => number_format($amount, 2),
                    'account_number'   => $fromAccount->account_number,
                    'to_account_number'=> $toAccount->account_number,
                    'reference'        => 'See your transactions page',
                    'description'      => $description,
                ]
            );
        } catch (\Throwable) { /* swallow — transfer already succeeded */ }

        try {
            if ($toAccount->customer_id && $toAccount->customer_id !== $customer->id) {
                app(NotificationService::class)->send(
                    $toAccount->customer_id,
                    'customer',
                    'You have received a transfer of $' . number_format($amount, 2) . ' into account ' . $toAccount->account_number . '.',
                    'Transfer Received',
                    'transaction-completed',
                    [
                        'customer_name'  => $toAccount->customer?->full_name ?? '',
                        'type'           => 'Transfer Received',
                        'amount'         => number_format($amount, 2),
                        'account_number' => $toAccount->account_number,
                        'description'    => 'Incoming transfer',
                    ]
                );
            }
        } catch (\Throwable) { /* swallow */ }

        return redirect()->route('customer.transfer')
            ->with('success', 'Transfer of $' . number_format($amount, 2) . ' completed successfully.');
    }
}
