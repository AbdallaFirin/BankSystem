<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\CashAllocation;
use App\Models\Customer;
use App\Models\InterBranchClearing;
use App\Models\PendingApproval;
use App\Models\Role;
use App\Models\Staff;
use App\Services\LedgerService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Exception;

class TransactionController extends Controller
{
    protected $ledgerService;
    protected $notificationService;

    public function __construct(LedgerService $ledgerService, NotificationService $notificationService)
    {
        $this->ledgerService        = $ledgerService;
        $this->notificationService  = $notificationService;
    }

    /* ─────────────────────────────────────────────
     *  Check if this amount exceeds the staff's role limit.
     *  Returns true  → must go for supervisor approval.
     *  Returns false → process immediately.
     * ───────────────────────────────────────────── */
    private function requiresApproval($staff, float $amount): bool
    {
        $limit = $staff->role?->txn_limit ?? null;
        return $limit !== null && $amount > (float) $limit;
    }

    /* ─────────────────────────────────────────────
     *  Queue the transaction for supervisor approval
     *  and return a "pending" flash payload.
     * ───────────────────────────────────────────── */
    private function createPendingApproval($transaction, $staff): void
    {
        $supervisorRoleId = Role::where('role_name', 'Teller Supervisor')->value('id')
                         ?? Role::where('role_name', 'Branch Manager')->value('id');

        PendingApproval::create([
            'transaction_id'   => $transaction->id,
            'requested_by'     => $staff->id,
            'approver_role_id' => $supervisorRoleId,
            'status'           => 'pending',
        ]);

        // Notify all supervisors and branch managers in the same branch
        $supervisors = Staff::with('role')
            ->where('branch_id', $staff->branch_id)
            ->whereHas('role', fn($q) =>
                $q->whereIn('role_name', ['Teller Supervisor', 'Branch Manager'])
            )
            ->get();

        $typeLabels = [
            'deposit'    => 'Cash Deposit',
            'withdrawal' => 'Cash Withdrawal',
            'transfer'   => 'Inter-Account Transfer',
        ];
        $typeLabel = $typeLabels[$transaction->type] ?? ucfirst($transaction->type);
        $amount    = number_format($transaction->amount, 2);

        foreach ($supervisors as $supervisor) {
            $this->notificationService->send(
                recipientId:   $supervisor->id,
                recipientType: 'staff',
                message:       "New {$typeLabel} of \${$amount} submitted by {$staff->full_name} requires your approval. Ref: {$transaction->reference}.",
                subject:       "Approval Required — {$typeLabel} \${$amount}",
                mailView:      'staff-notification',
                mailData:      [
                    'headerTag'  => 'Approval Required',
                    'greeting'   => "Hello {$supervisor->full_name},",
                    'body'       => "A transaction has been submitted by {$staff->full_name} that exceeds the teller limit and requires your approval.",
                    'details'    => [
                        'Reference'   => $transaction->reference,
                        'Type'        => $typeLabel,
                        'Amount'      => "\${$amount}",
                        'Submitted by'=> $staff->full_name . ' (' . ($staff->role?->role_name ?? 'Teller') . ')',
                        'Submitted at'=> now()->format('d M Y, H:i:s'),
                    ],
                    'actionNote' => 'Please log in to the staff portal and review the pending approval queue.',
                ]
            );
        }
    }

    private function getVaultAccount($staff)
    {
        $branchId = $staff->branch_id;

        $vaultCustomer = Customer::firstOrCreate(
            ['phone' => 'SYS-VAULT-' . $branchId],
            [
                'full_name'            => 'Internal Bank Vault - Branch ' . $branchId,
                'first_name'           => 'Vault',
                'last_name'            => 'Branch ' . $branchId,
                'dob'                  => '2000-01-01',
                'gender'               => 'N/A',
                'nationality'          => 'N/A',
                'id_type'              => 'Internal',
                'id_number'            => 'SYS-VAULT-' . $branchId,
                'id_issue_date'        => '2000-01-01',
                'employment_status'    => 'N/A',
                'monthly_income_range' => 'N/A',
                'source_of_funds'      => 'N/A',
                'next_of_kin_name'     => 'N/A',
                'next_of_kin_relation' => 'N/A',
                'next_of_kin_phone'    => '000',
                'region_city'          => 'N/A',
                'home_branch_id'       => $branchId,
                'registered_by'        => 1,
                'kyc_status'           => 'verified',
            ]
        );

        return Account::firstOrCreate(
            ['account_number' => 'VAULT-' . $branchId],
            [
                'customer_id'     => $vaultCustomer->id,
                'account_type_id' => 1,
                'home_branch_id'  => $branchId,
                'status'          => 'active',
                'opened_at'       => now(),
            ]
        );
    }

    private function getCashAccount($staff): Account
    {
        $activeAlloc = CashAllocation::where('teller_id', $staff->id)
            ->where('status', 'acknowledged')
            ->latest('allocated_at')
            ->first();

        if ($activeAlloc) {
            return Account::findOrFail($activeAlloc->till_account_id);
        }

        return $this->getVaultAccount($staff);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/transactions/deposit
     * ───────────────────────────────────────────── */
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'account_number' => 'required|exists:accounts,account_number',
            'amount'         => 'required|numeric|min:0.01',
            'remarks'        => 'nullable|string|max:500',
        ]);

        try {
            $staff           = Auth::guard('staff')->user()->load('role');
            $customerAccount = Account::with('customer')
                ->where('account_number', $validated['account_number'])->firstOrFail();

            if ($customerAccount->is_frozen) {
                return back()->withErrors(['error' => 'This account is frozen and cannot accept deposits.']);
            }

            $vaultAccount    = $this->getCashAccount($staff);
            $amount          = (float) $validated['amount'];
            $needsApproval   = $this->requiresApproval($staff, $amount);
            $remarks         = trim($validated['remarks'] ?? '');

            $transaction = $this->ledgerService->deposit(
                $vaultAccount, $customerAccount,
                $amount, $staff->id, $staff->branch_id,
                description: $remarks ?: 'Cash Deposit',
                pendingApproval: $needsApproval
            );

            if ($needsApproval) {
                $this->createPendingApproval($transaction, $staff);

                return redirect()->route('staff.teller.deposit')->with('pending_receipt', [
                    'transaction_id' => $transaction->id,
                    'reference'    => $transaction->reference,
                    'type'         => 'Cash Deposit',
                    'amount'       => $amount,
                    'limit'        => $staff->role->txn_limit,
                    'account'      => $customerAccount->account_number,
                    'customer'     => $customerAccount->customer?->full_name ?? '—',
                    'submitted_at' => now()->format('d M Y, H:i:s'),
                    'teller'       => $staff->full_name,
                    'remarks'      => $remarks,
                ]);
            }

            $customer       = $customerAccount->customer;
            $balanceAfter   = $customerAccount->fresh()->balance;
            $processedAt    = now()->format('d M Y, H:i:s');

            if ($customer) {
                $this->notificationService->send(
                    recipientId:   $customer->id,
                    recipientType: 'customer',
                    message:       "Your Cash Deposit of \${$amount} to account {$customerAccount->account_number} has been processed. Ref: {$transaction->reference}.",
                    subject:       'Cash Deposit Confirmation',
                    mailView:      'transaction-completed',
                    mailData:      [
                        'customerName'  => $customer->full_name,
                        'type'          => 'Cash Deposit',
                        'typeKey'       => 'deposit',
                        'reference'     => $transaction->reference,
                        'accountNumber' => $customerAccount->account_number,
                        'amount'        => $amount,
                        'balanceAfter'  => $balanceAfter,
                        'teller'        => $staff->full_name,
                        'processedAt'   => $processedAt,
                        'remarks'       => $remarks,
                    ]
                );
            }

            return redirect()->route('staff.teller.deposit')->with('receipt', [
                'transaction_id' => $transaction->id,
                'reference'      => $transaction->reference,
                'type'           => 'Cash Deposit',
                'amount'         => $amount,
                'account_number' => $customerAccount->account_number,
                'customer_name'  => $customer?->full_name ?? '—',
                'balance_after'  => $balanceAfter,
                'processed_at'   => $processedAt,
                'teller'         => $staff->full_name,
                'branch'         => $staff->branch_id,
                'remarks'        => $remarks,
            ]);

        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/transactions/withdraw
     * ───────────────────────────────────────────── */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'account_number' => 'required|exists:accounts,account_number',
            'amount'         => 'required|numeric|min:0.01',
            'remarks'        => 'nullable|string|max:500',
        ]);

        try {
            $staff           = Auth::guard('staff')->user()->load('role');
            $customerAccount = Account::with('customer')
                ->where('account_number', $validated['account_number'])->firstOrFail();

            if ($customerAccount->is_frozen) {
                return back()->withErrors(['error' => 'This account is frozen and cannot process withdrawals.']);
            }

            $vaultAccount    = $this->getCashAccount($staff);
            $amount          = (float) $validated['amount'];
            $needsApproval   = $this->requiresApproval($staff, $amount);
            $remarks         = trim($validated['remarks'] ?? '');

            $transaction = $this->ledgerService->withdraw(
                $vaultAccount, $customerAccount,
                $amount, $staff->id, $staff->branch_id,
                description: $remarks ?: 'Cash Withdrawal',
                pendingApproval: $needsApproval
            );

            if ($needsApproval) {
                $this->createPendingApproval($transaction, $staff);

                return redirect()->route('staff.teller.withdraw')->with('pending_receipt', [
                    'transaction_id' => $transaction->id,
                    'reference'    => $transaction->reference,
                    'type'         => 'Cash Withdrawal',
                    'amount'       => $amount,
                    'limit'        => $staff->role->txn_limit,
                    'account'      => $customerAccount->account_number,
                    'customer'     => $customerAccount->customer?->full_name ?? '—',
                    'submitted_at' => now()->format('d M Y, H:i:s'),
                    'teller'       => $staff->full_name,
                    'remarks'      => $remarks,
                ]);
            }

            $customer       = $customerAccount->customer;
            $balanceAfter   = $customerAccount->fresh()->balance;
            $processedAt    = now()->format('d M Y, H:i:s');

            if ($customer) {
                $this->notificationService->send(
                    recipientId:   $customer->id,
                    recipientType: 'customer',
                    message:       "Your Cash Withdrawal of \${$amount} from account {$customerAccount->account_number} has been processed. Ref: {$transaction->reference}.",
                    subject:       'Cash Withdrawal Confirmation',
                    mailView:      'transaction-completed',
                    mailData:      [
                        'customerName'  => $customer->full_name,
                        'type'          => 'Cash Withdrawal',
                        'typeKey'       => 'withdrawal',
                        'reference'     => $transaction->reference,
                        'accountNumber' => $customerAccount->account_number,
                        'amount'        => $amount,
                        'balanceAfter'  => $balanceAfter,
                        'teller'        => $staff->full_name,
                        'processedAt'   => $processedAt,
                        'remarks'       => $remarks,
                    ]
                );
            }

            return redirect()->route('staff.teller.withdraw')->with('receipt', [
                'transaction_id' => $transaction->id,
                'reference'      => $transaction->reference,
                'type'           => 'Cash Withdrawal',
                'amount'         => $amount,
                'account_number' => $customerAccount->account_number,
                'customer_name'  => $customer?->full_name ?? '—',
                'balance_after'  => $balanceAfter,
                'processed_at'   => $processedAt,
                'teller'         => $staff->full_name,
                'branch'         => $staff->branch_id,
                'remarks'        => $remarks,
            ]);

        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/transactions/transfer
     * ───────────────────────────────────────────── */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_account' => 'required|exists:accounts,account_number',
            'to_account'   => 'required|exists:accounts,account_number|different:from_account',
            'amount'       => 'required|numeric|min:0.01',
            'remarks'      => 'nullable|string|max:500',
        ]);

        try {
            $staff       = Auth::guard('staff')->user()->load('role');
            $fromAccount = Account::with('customer')
                ->where('account_number', $validated['from_account'])->firstOrFail();
            $toAccount   = Account::with('customer')
                ->where('account_number', $validated['to_account'])->firstOrFail();

            if ($fromAccount->is_frozen) {
                return back()->withErrors(['error' => 'The source account is frozen and cannot send transfers.']);
            }
            if ($toAccount->is_frozen) {
                return back()->withErrors(['error' => 'The destination account is frozen and cannot receive transfers.']);
            }

            $amount        = (float) $validated['amount'];
            $needsApproval = $this->requiresApproval($staff, $amount);
            $remarks       = trim($validated['remarks'] ?? '');

            $transaction = $this->ledgerService->transfer(
                $fromAccount, $toAccount,
                $amount, $staff->id, $staff->branch_id,
                description: $remarks ?: 'Inter-Account Transfer',
                pendingApproval: $needsApproval
            );

            // If accounts belong to different branches, create an inter-branch clearing entry
            if ($fromAccount->home_branch_id && $toAccount->home_branch_id
                && $fromAccount->home_branch_id !== $toAccount->home_branch_id) {
                InterBranchClearing::create([
                    'from_branch_id' => $fromAccount->home_branch_id,
                    'to_branch_id'   => $toAccount->home_branch_id,
                    'transaction_id' => $transaction->id,
                    'amount'         => $amount,
                    'status'         => 'pending',
                ]);
            }

            if ($needsApproval) {
                $this->createPendingApproval($transaction, $staff);

                return redirect()->route('staff.teller.transfer')->with('pending_receipt', [
                    'transaction_id' => $transaction->id,
                    'reference'    => $transaction->reference,
                    'type'         => 'Inter-Account Transfer',
                    'amount'       => $amount,
                    'limit'        => $staff->role->txn_limit,
                    'account'      => $fromAccount->account_number,
                    'customer'     => $fromAccount->customer?->full_name ?? '—',
                    'to_account'   => $toAccount->account_number,
                    'to_customer'  => $toAccount->customer?->full_name ?? '—',
                    'submitted_at' => now()->format('d M Y, H:i:s'),
                    'teller'       => $staff->full_name,
                    'remarks'      => $remarks,
                ]);
            }

            $fromCustomer    = $fromAccount->customer;
            $toCustomer      = $toAccount->customer;
            $fromBalAfter    = $fromAccount->fresh()->balance;
            $processedAt     = now()->format('d M Y, H:i:s');

            // Notify sender
            if ($fromCustomer) {
                $this->notificationService->send(
                    recipientId:   $fromCustomer->id,
                    recipientType: 'customer',
                    message:       "Transfer of \${$amount} from account {$fromAccount->account_number} to {$toAccount->account_number} has been processed. Ref: {$transaction->reference}.",
                    subject:       'Transfer Confirmation',
                    mailView:      'transaction-completed',
                    mailData:      [
                        'customerName'    => $fromCustomer->full_name,
                        'type'            => 'Inter-Account Transfer',
                        'typeKey'         => 'transfer',
                        'reference'       => $transaction->reference,
                        'accountNumber'   => $fromAccount->account_number,
                        'toAccountNumber' => $toAccount->account_number,
                        'amount'          => $amount,
                        'balanceAfter'    => $fromBalAfter,
                        'teller'          => $staff->full_name,
                        'processedAt'     => $processedAt,
                        'remarks'         => $remarks,
                    ]
                );
            }

            // Notify recipient (if different customer)
            if ($toCustomer && (!$fromCustomer || $toCustomer->id !== $fromCustomer->id)) {
                $this->notificationService->send(
                    recipientId:   $toCustomer->id,
                    recipientType: 'customer',
                    message:       "You received a transfer of \${$amount} to account {$toAccount->account_number}. Ref: {$transaction->reference}.",
                    subject:       'Incoming Transfer Notification',
                    mailView:      'transaction-completed',
                    mailData:      [
                        'customerName'  => $toCustomer->full_name,
                        'type'          => 'Incoming Transfer',
                        'typeKey'       => 'deposit',
                        'reference'     => $transaction->reference,
                        'accountNumber' => $toAccount->account_number,
                        'amount'        => $amount,
                        'teller'        => $staff->full_name,
                        'processedAt'   => $processedAt,
                        'remarks'       => $remarks,
                    ]
                );
            }

            return redirect()->route('staff.teller.transfer')->with('receipt', [
                'transaction_id'      => $transaction->id,
                'reference'           => $transaction->reference,
                'type'                => 'Inter-Account Transfer',
                'amount'              => $amount,
                'from_account_number' => $fromAccount->account_number,
                'from_customer'       => $fromCustomer?->full_name ?? '—',
                'to_account_number'   => $toAccount->account_number,
                'to_customer'         => $toCustomer?->full_name ?? '—',
                'from_balance_after'  => $fromBalAfter,
                'processed_at'        => $processedAt,
                'teller'              => $staff->full_name,
                'branch'              => $staff->branch_id,
                'remarks'             => $remarks,
            ]);

        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
