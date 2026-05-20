<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Models\Account;
use Exception;

class LedgerService
{
    /**
     * Cash Deposit: vault/till → customer account.
     * If $pendingApproval = true, the transaction is created but ledger entries are NOT posted.
     * The supervisor must call postLedgerEntries() after approving.
     */
    public function deposit(
        Account $vaultAccount,
        Account $customerAccount,
        float $amount,
        int $staffId,
        int $branchId,
        string $description = 'Deposit',
        bool $pendingApproval = false
    ): Transaction {
        return DB::transaction(function () use (
            $vaultAccount, $customerAccount, $amount,
            $staffId, $branchId, $description, $pendingApproval
        ) {
            $transaction = Transaction::create([
                'reference'    => 'DEP-' . strtoupper(uniqid()),
                'type'         => 'deposit',
                'status'       => $pendingApproval ? 'pending_approval' : 'completed',
                'amount'       => $amount,
                'account_id'   => $customerAccount->id,   // primary  = customer
                'to_account_id'=> $vaultAccount->id,      // secondary = vault/till
                'initiated_by' => $staffId,
                'branch_id'    => $branchId,
                'description'  => $description,
            ]);

            if (!$pendingApproval) {
                // Debit vault (asset decreases — cash leaves bank's hand)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $vaultAccount->id,
                    'branch_id'      => $branchId,
                    'entry_type'     => 'debit',
                    'amount'         => $amount,
                    'balance_after'  => $vaultAccount->balance - $amount,
                ]);
                // Credit customer (liability increases — bank owes customer more)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $customerAccount->id,
                    'branch_id'      => $branchId,
                    'entry_type'     => 'credit',
                    'amount'         => $amount,
                    'balance_after'  => $customerAccount->balance + $amount,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Cash Withdrawal: customer account → vault/till.
     * Respects overdraft_allowed + overdraft_limit on the account's product type.
     */
    public function withdraw(
        Account $vaultAccount,
        Account $customerAccount,
        float $amount,
        int $staffId,
        int $branchId,
        string $description = 'Withdrawal',
        bool $pendingApproval = false
    ): Transaction {
        $this->assertWithdrawalAllowed($customerAccount, $amount);

        return DB::transaction(function () use (
            $vaultAccount, $customerAccount, $amount,
            $staffId, $branchId, $description, $pendingApproval
        ) {
            $transaction = Transaction::create([
                'reference'     => 'WD-' . strtoupper(uniqid()),
                'type'          => 'withdrawal',
                'status'        => $pendingApproval ? 'pending_approval' : 'completed',
                'amount'        => $amount,
                'account_id'    => $customerAccount->id,  // primary  = customer
                'to_account_id' => $vaultAccount->id,     // secondary = vault/till
                'initiated_by'  => $staffId,
                'branch_id'     => $branchId,
                'description'   => $description,
            ]);

            if (!$pendingApproval) {
                // Debit customer (liability decreases)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $customerAccount->id,
                    'branch_id'      => $branchId,
                    'entry_type'     => 'debit',
                    'amount'         => $amount,
                    'balance_after'  => $customerAccount->balance - $amount,
                ]);
                // Credit vault (asset increases — cash returns to bank)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $vaultAccount->id,
                    'branch_id'      => $branchId,
                    'entry_type'     => 'credit',
                    'amount'         => $amount,
                    'balance_after'  => $vaultAccount->balance + $amount,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Inter-Account Transfer: fromAccount → toAccount.
     */
    public function transfer(
        Account $fromAccount,
        Account $toAccount,
        float $amount,
        int $staffId,
        int $branchId,
        string $description = 'Transfer',
        bool $pendingApproval = false
    ): Transaction {
        $this->assertWithdrawalAllowed($fromAccount, $amount);

        return DB::transaction(function () use (
            $fromAccount, $toAccount, $amount,
            $staffId, $branchId, $description, $pendingApproval
        ) {
            $transaction = Transaction::create([
                'reference'     => 'TRF-' . strtoupper(uniqid()),
                'type'          => 'transfer',
                'status'        => $pendingApproval ? 'pending_approval' : 'completed',
                'amount'        => $amount,
                'account_id'    => $fromAccount->id,  // primary  = sender
                'to_account_id' => $toAccount->id,    // secondary = receiver
                'initiated_by'  => $staffId,
                'branch_id'     => $branchId,
                'description'   => $description,
            ]);

            if (!$pendingApproval) {
                // Debit sender (belongs to initiating branch)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $fromAccount->id,
                    'branch_id'      => $branchId,
                    'entry_type'     => 'debit',
                    'amount'         => $amount,
                    'balance_after'  => $fromAccount->balance - $amount,
                ]);
                // Credit receiver — use the receiver account's home branch,
                // not the initiating branch, so the entry appears on the
                // correct branch's ledger (critical for inter-branch transfers).
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $toAccount->id,
                    'branch_id'      => $toAccount->home_branch_id ?? $branchId,
                    'entry_type'     => 'credit',
                    'amount'         => $amount,
                    'balance_after'  => $toAccount->balance + $amount,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Post ledger entries for a previously queued (pending_approval) transaction.
     * Called by ApprovalController when a supervisor approves.
     */
    public function postLedgerEntries(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            if (!$transaction->account_id || !$transaction->to_account_id) {
                throw new Exception('Transaction is missing account references and cannot be posted.');
            }

            $primary   = Account::findOrFail($transaction->account_id);
            $secondary = Account::findOrFail($transaction->to_account_id);

            if ($transaction->type === 'deposit') {
                // Debit vault (secondary), credit customer (primary)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $secondary->id,
                    'branch_id'      => $transaction->branch_id,
                    'entry_type'     => 'debit',
                    'amount'         => $transaction->amount,
                    'balance_after'  => $secondary->balance - $transaction->amount,
                ]);
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $primary->id,
                    'branch_id'      => $transaction->branch_id,
                    'entry_type'     => 'credit',
                    'amount'         => $transaction->amount,
                    'balance_after'  => $primary->balance + $transaction->amount,
                ]);

            } elseif ($transaction->type === 'withdrawal') {
                // Re-check balance at approval time (customer may have withdrawn elsewhere)
                // Respects overdraft rules on the account product type.
                $this->assertWithdrawalAllowed($primary, $transaction->amount, suffix: ' Customer account cannot cover this withdrawal anymore.');
                // Debit customer (primary), credit vault (secondary)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $primary->id,
                    'branch_id'      => $transaction->branch_id,
                    'entry_type'     => 'debit',
                    'amount'         => $transaction->amount,
                    'balance_after'  => $primary->balance - $transaction->amount,
                ]);
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $secondary->id,
                    'branch_id'      => $transaction->branch_id,
                    'entry_type'     => 'credit',
                    'amount'         => $transaction->amount,
                    'balance_after'  => $secondary->balance + $transaction->amount,
                ]);

            } elseif ($transaction->type === 'transfer') {
                $this->assertWithdrawalAllowed($primary, $transaction->amount, suffix: ' Sender account cannot cover this transfer anymore.');
                // Debit sender (primary) — belongs to the initiating branch
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $primary->id,
                    'branch_id'      => $transaction->branch_id,
                    'entry_type'     => 'debit',
                    'amount'         => $transaction->amount,
                    'balance_after'  => $primary->balance - $transaction->amount,
                ]);
                // Credit receiver (secondary) — use its own home branch so the
                // entry appears on the correct branch's ledger.
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $secondary->id,
                    'branch_id'      => $secondary->home_branch_id ?? $transaction->branch_id,
                    'entry_type'     => 'credit',
                    'amount'         => $transaction->amount,
                    'balance_after'  => $secondary->balance + $transaction->amount,
                ]);
            }

            $transaction->update(['status' => 'completed']);
        });
    }

    /**
     * Central balance / overdraft guard.
     * Throws an Exception if the requested amount would breach the account's allowed floor.
     *
     * - overdraft_allowed = false  →  balance must be >= amount  (no going negative)
     * - overdraft_allowed = true   →  balance - amount >= -overdraft_limit
     *                                  (NULL overdraft_limit = unlimited overdraft)
     */
    private function assertWithdrawalAllowed(Account $account, float $amount, string $suffix = ''): void
    {
        $type = $account->accountType;

        if (!$type?->overdraft_allowed) {
            if ($account->balance < $amount) {
                throw new Exception('Insufficient balance in customer account.' . $suffix);
            }
            return;
        }

        // Overdraft is allowed — check the overdraft ceiling
        if ($type->overdraft_limit !== null) {
            $floor = -(float) $type->overdraft_limit;
            if (($account->balance - $amount) < $floor) {
                $limit = number_format($type->overdraft_limit, 2);
                throw new Exception("This transaction would exceed the overdraft limit of \${$limit}." . $suffix);
            }
        }
        // overdraft_limit === null → unlimited overdraft, no check needed
    }
}
