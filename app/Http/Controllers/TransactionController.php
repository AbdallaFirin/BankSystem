<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\CashAllocation;
use App\Models\Customer;
use App\Models\PendingApproval;
use App\Models\Role;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Auth;
use Exception;

class TransactionController extends Controller
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /* ─────────────────────────────────────────────
     *  Check if this amount exceeds the staff's role limit.
     *  Returns true  → must go for supervisor approval.
     *  Returns false → process immediately.
     * ───────────────────────────────────────────── */
    private function requiresApproval($staff, float $amount): bool
    {
        $limit = $staff->role->txn_limit ?? null;
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
        ]);

        try {
            $staff           = Auth::guard('staff')->user()->load('role');
            $customerAccount = Account::with('customer')
                ->where('account_number', $validated['account_number'])->firstOrFail();
            $vaultAccount    = $this->getCashAccount($staff);
            $amount          = (float) $validated['amount'];
            $needsApproval   = $this->requiresApproval($staff, $amount);

            $transaction = $this->ledgerService->deposit(
                $vaultAccount, $customerAccount,
                $amount, $staff->id, $staff->branch_id,
                pendingApproval: $needsApproval
            );

            if ($needsApproval) {
                $this->createPendingApproval($transaction, $staff);

                return redirect()->route('staff.teller.deposit')->with('pending_receipt', [
                    'reference'    => $transaction->reference,
                    'type'         => 'Cash Deposit',
                    'amount'       => $amount,
                    'limit'        => $staff->role->txn_limit,
                    'account'      => $customerAccount->account_number,
                    'customer'     => $customerAccount->customer?->full_name ?? '—',
                    'submitted_at' => now()->format('d M Y, H:i:s'),
                    'teller'       => $staff->full_name,
                ]);
            }

            return redirect()->route('staff.teller.deposit')->with('receipt', [
                'reference'      => $transaction->reference,
                'type'           => 'Cash Deposit',
                'amount'         => $amount,
                'account_number' => $customerAccount->account_number,
                'customer_name'  => $customerAccount->customer?->full_name ?? '—',
                'balance_after'  => $customerAccount->fresh()->balance,
                'processed_at'   => now()->format('d M Y, H:i:s'),
                'teller'         => $staff->full_name,
                'branch'         => $staff->branch_id,
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
        ]);

        try {
            $staff           = Auth::guard('staff')->user()->load('role');
            $customerAccount = Account::with('customer')
                ->where('account_number', $validated['account_number'])->firstOrFail();
            $vaultAccount    = $this->getCashAccount($staff);
            $amount          = (float) $validated['amount'];
            $needsApproval   = $this->requiresApproval($staff, $amount);

            $transaction = $this->ledgerService->withdraw(
                $vaultAccount, $customerAccount,
                $amount, $staff->id, $staff->branch_id,
                pendingApproval: $needsApproval
            );

            if ($needsApproval) {
                $this->createPendingApproval($transaction, $staff);

                return redirect()->route('staff.teller.withdraw')->with('pending_receipt', [
                    'reference'    => $transaction->reference,
                    'type'         => 'Cash Withdrawal',
                    'amount'       => $amount,
                    'limit'        => $staff->role->txn_limit,
                    'account'      => $customerAccount->account_number,
                    'customer'     => $customerAccount->customer?->full_name ?? '—',
                    'submitted_at' => now()->format('d M Y, H:i:s'),
                    'teller'       => $staff->full_name,
                ]);
            }

            return redirect()->route('staff.teller.withdraw')->with('receipt', [
                'reference'      => $transaction->reference,
                'type'           => 'Cash Withdrawal',
                'amount'         => $amount,
                'account_number' => $customerAccount->account_number,
                'customer_name'  => $customerAccount->customer?->full_name ?? '—',
                'balance_after'  => $customerAccount->fresh()->balance,
                'processed_at'   => now()->format('d M Y, H:i:s'),
                'teller'         => $staff->full_name,
                'branch'         => $staff->branch_id,
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
        ]);

        try {
            $staff       = Auth::guard('staff')->user()->load('role');
            $fromAccount = Account::with('customer')
                ->where('account_number', $validated['from_account'])->firstOrFail();
            $toAccount   = Account::with('customer')
                ->where('account_number', $validated['to_account'])->firstOrFail();
            $amount      = (float) $validated['amount'];
            $needsApproval = $this->requiresApproval($staff, $amount);

            $transaction = $this->ledgerService->transfer(
                $fromAccount, $toAccount,
                $amount, $staff->id, $staff->branch_id,
                pendingApproval: $needsApproval
            );

            if ($needsApproval) {
                $this->createPendingApproval($transaction, $staff);

                return redirect()->route('staff.teller.transfer')->with('pending_receipt', [
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
                ]);
            }

            return redirect()->route('staff.teller.transfer')->with('receipt', [
                'reference'           => $transaction->reference,
                'type'                => 'Inter-Account Transfer',
                'amount'              => $amount,
                'from_account_number' => $fromAccount->account_number,
                'from_customer'       => $fromAccount->customer?->full_name ?? '—',
                'to_account_number'   => $toAccount->account_number,
                'to_customer'         => $toAccount->customer?->full_name ?? '—',
                'from_balance_after'  => $fromAccount->fresh()->balance,
                'processed_at'        => now()->format('d M Y, H:i:s'),
                'teller'              => $staff->full_name,
                'branch'              => $staff->branch_id,
            ]);

        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
