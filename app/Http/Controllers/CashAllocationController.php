<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\CashAllocation;
use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Staff;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CashAllocationController extends Controller
{
    public function __construct(private LedgerService $ledgerService) {}

    /* ─────────────────────────────────────────────
     *  Shared helpers
     * ───────────────────────────────────────────── */

    /** Get (or lazily create) the branch vault account */
    private function getVaultAccount(int $branchId): Account
    {
        $vaultCustomer = Customer::firstOrCreate(
            ['phone' => 'SYS-VAULT-' . $branchId],
            [
                'full_name'           => 'Internal Bank Vault - Branch ' . $branchId,
                'first_name'          => 'Vault',
                'last_name'           => 'Branch ' . $branchId,
                'dob'                 => '2000-01-01',
                'gender'              => 'N/A',
                'nationality'         => 'N/A',
                'id_type'             => 'Internal',
                'id_number'           => 'SYS-VAULT-' . $branchId,
                'id_issue_date'       => '2000-01-01',
                'employment_status'   => 'N/A',
                'monthly_income_range'=> 'N/A',
                'source_of_funds'     => 'N/A',
                'next_of_kin_name'    => 'N/A',
                'next_of_kin_relation'=> 'N/A',
                'next_of_kin_phone'   => '000',
                'region_city'         => 'N/A',
                'home_branch_id'      => $branchId,
                'registered_by'       => 1,
                'kyc_status'          => 'verified',
            ]
        );

        return Account::firstOrCreate(
            ['account_number' => 'VAULT-' . $branchId],
            [
                'customer_id'    => $vaultCustomer->id,
                'account_type_id'=> 1,
                'home_branch_id' => $branchId,
                'status'         => 'active',
                'opened_at'      => now(),
            ]
        );
    }

    /** Get (or lazily create) a teller's till account */
    public static function getOrCreateTillAccount(Staff $teller): Account
    {
        $tillCustomer = Customer::firstOrCreate(
            ['phone' => 'SYS-TILL-' . $teller->id],
            [
                'full_name'           => 'Teller Till — ' . $teller->full_name,
                'first_name'          => 'Till',
                'last_name'           => $teller->full_name,
                'dob'                 => '2000-01-01',
                'gender'              => 'N/A',
                'nationality'         => 'N/A',
                'id_type'             => 'Internal',
                'id_number'           => 'SYS-TILL-' . $teller->id,
                'id_issue_date'       => '2000-01-01',
                'employment_status'   => 'N/A',
                'monthly_income_range'=> 'N/A',
                'source_of_funds'     => 'N/A',
                'next_of_kin_name'    => 'N/A',
                'next_of_kin_relation'=> 'N/A',
                'next_of_kin_phone'   => '000',
                'region_city'         => 'N/A',
                'home_branch_id'      => $teller->branch_id,
                'registered_by'       => 1,
                'kyc_status'          => 'verified',
            ]
        );

        return Account::firstOrCreate(
            ['account_number' => 'TILL-' . $teller->id],
            [
                'customer_id'    => $tillCustomer->id,
                'account_type_id'=> 1,
                'home_branch_id' => $teller->branch_id,
                'status'         => 'active',
                'opened_at'      => now(),
            ]
        );
    }

    /** Compute ledger balance for an account (credits − debits) */
    private static function ledgerBalance(int $accountId): float
    {
        return (float) LedgerEntry::where('account_id', $accountId)
            ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
            ->value('bal') ?? 0.0;
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/teller/cash-allocation
     *  Vault Cashier dashboard — active + history
     * ───────────────────────────────────────────── */
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        // Only teller-role staff in this branch (Bank Teller + Teller Supervisor)
        $tellers = Staff::with('role')
            ->where('branch_id', $staff->branch_id)
            ->where('id', '!=', $staff->id)
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Bank Teller', 'Teller Supervisor']))
            ->get()
            ->map(function ($t) {
                $tillAccount  = Account::where('account_number', 'TILL-' . $t->id)->first();
                $tillBalance  = $tillAccount ? self::ledgerBalance($tillAccount->id) : 0.0;
                $activeTill   = CashAllocation::where('teller_id', $t->id)
                    ->whereIn('status', ['pending', 'acknowledged'])
                    ->latest('allocated_at')
                    ->first();

                return [
                    'id'           => $t->id,
                    'full_name'    => $t->full_name,
                    'role_name'    => $t->role?->role_name ?? '—',
                    'till_balance' => $tillBalance,
                    'active_alloc' => $activeTill ? [
                        'id'     => $activeTill->id,
                        'amount' => $activeTill->amount,
                        'status' => $activeTill->status,
                    ] : null,
                ];
            });

        // Vault balance
        $vault        = $this->getVaultAccount($staff->branch_id);
        $vaultBalance = self::ledgerBalance($vault->id);

        // Active (pending / acknowledged) allocations for this branch
        $active = CashAllocation::with(['teller', 'allocatedBy'])
            ->where('branch_id', $staff->branch_id)
            ->whereIn('status', ['pending', 'acknowledged'])
            ->orderByDesc('allocated_at')
            ->get()
            ->map(fn($a) => array_merge($a->toArray(), [
                'till_balance' => self::ledgerBalance($a->till_account_id),
            ]));

        // Historical (returned) allocations — paginated
        $history = CashAllocation::with(['teller', 'allocatedBy'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'returned')
            ->orderByDesc('returned_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Staff/Teller/CashAllocation', [
            'tellers'      => $tellers,
            'vault_balance'=> $vaultBalance,
            'active'       => $active,
            'history'      => $history,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/teller/cash-allocation
     *  Vault Cashier allocates float to a teller
     * ───────────────────────────────────────────── */
    public function store(Request $request)
    {
        $request->validate([
            'teller_id' => 'required|integer|exists:staff,id',
            'amount'    => 'required|numeric|min:0.01',
            'notes'     => 'nullable|string|max:500',
        ]);

        $cashier = Auth::guard('staff')->user();
        $teller  = Staff::with('role')->findOrFail($request->teller_id);

        // Teller must be in same branch
        if ($teller->branch_id !== $cashier->branch_id) {
            return back()->withErrors(['error' => 'Teller does not belong to your branch.']);
        }

        // Only Bank Teller or Teller Supervisor can receive a till allocation
        $allowedRoles = ['Bank Teller', 'Teller Supervisor'];
        if (!in_array($teller->role?->role_name, $allowedRoles)) {
            return back()->withErrors(['error' => $teller->full_name . ' is a ' . ($teller->role?->role_name ?? 'unknown role') . ' and cannot receive a till allocation.']);
        }

        // Check teller doesn't already have an open till
        $existing = CashAllocation::where('teller_id', $teller->id)
            ->whereIn('status', ['pending', 'acknowledged'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['error' => $teller->full_name . ' already has an open till allocation. Collect it first.']);
        }

        $vault = $this->getVaultAccount($cashier->branch_id);
        $vaultBalance = self::ledgerBalance($vault->id);

        if ($vaultBalance < (float) $request->amount) {
            return back()->withErrors(['error' => 'Vault balance (' . number_format($vaultBalance, 2) . ') is insufficient for this allocation.']);
        }

        $till = self::getOrCreateTillAccount($teller);

        DB::transaction(function () use ($cashier, $teller, $vault, $till, $request) {
            // Transfer VAULT → TILL
            $this->ledgerService->transfer(
                $vault,
                $till,
                (float) $request->amount,
                $cashier->id,
                $cashier->branch_id,
                'TILL ALLOCATION to ' . $teller->full_name . ' (Till-' . $teller->id . ')'
            );

            CashAllocation::create([
                'branch_id'      => $cashier->branch_id,
                'allocated_by'   => $cashier->id,
                'teller_id'      => $teller->id,
                'till_account_id'=> $till->id,
                'amount'         => (float) $request->amount,
                'status'         => 'pending',
                'allocated_at'   => now(),
                'notes'          => $request->notes,
            ]);
        });

        return back()->with('success', 'Float of $' . number_format($request->amount, 2) . ' allocated to ' . $teller->full_name . '. Awaiting acknowledgement.');
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/teller/cash-allocation/{id}/acknowledge
     *  Teller acknowledges receipt of their float
     * ───────────────────────────────────────────── */
    public function acknowledge(int $id)
    {
        $teller = Auth::guard('staff')->user();

        $allocation = CashAllocation::where('teller_id', $teller->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $allocation->update([
            'status'          => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        return back()->with('success', 'You have acknowledged receipt of $' . number_format($allocation->amount, 2) . ' float. Your till is now open.');
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/teller/cash-allocation/{id}/collect
     *  Vault Cashier collects remaining till balance back to vault
     * ───────────────────────────────────────────── */
    public function collect(int $id)
    {
        $cashier = Auth::guard('staff')->user();

        $allocation = CashAllocation::where('branch_id', $cashier->branch_id)
            ->whereIn('status', ['pending', 'acknowledged'])
            ->findOrFail($id);

        $till         = $allocation->tillAccount;
        $tillBalance  = self::ledgerBalance($till->id);
        $vault        = $this->getVaultAccount($cashier->branch_id);

        if ($tillBalance <= 0) {
            // Nothing to collect — just mark returned
            $allocation->update([
                'status'     => 'returned',
                'returned_at'=> now(),
            ]);
            return back()->with('success', 'Till closed — balance was already zero.');
        }

        DB::transaction(function () use ($cashier, $allocation, $till, $vault, $tillBalance) {
            $teller = $allocation->teller;

            // Transfer TILL → VAULT
            $this->ledgerService->transfer(
                $till,
                $vault,
                $tillBalance,
                $cashier->id,
                $cashier->branch_id,
                'TILL COLLECTION from ' . $teller->full_name . ' (Till-' . $teller->id . ')'
            );

            $allocation->update([
                'status'     => 'returned',
                'returned_at'=> now(),
            ]);
        });

        return back()->with('success', 'Collected $' . number_format($tillBalance, 2) . ' from ' . $allocation->teller->full_name . '\'s till back to vault.');
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/teller/my-till
     *  Teller's own till dashboard
     * ───────────────────────────────────────────── */
    public function myTill(Request $request)
    {
        $teller = Auth::guard('staff')->user();

        // Active allocation for this teller
        $allocation = CashAllocation::with(['allocatedBy', 'tillAccount'])
            ->where('teller_id', $teller->id)
            ->whereIn('status', ['pending', 'acknowledged'])
            ->latest('allocated_at')
            ->first();

        $tillBalance = 0.0;
        if ($allocation) {
            $tillBalance = self::ledgerBalance($allocation->till_account_id);
        }

        // Recent till transactions
        $tillTransactions = collect();
        if ($allocation) {
            $tillTransactions = LedgerEntry::with('transaction')
                ->where('account_id', $allocation->till_account_id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn($e) => [
                    'id'          => $e->id,
                    'entry_type'  => $e->entry_type,
                    'amount'      => (float) $e->amount,
                    'balance_after'=> (float) $e->balance_after,
                    'description' => $e->transaction?->description ?? '—',
                    'reference'   => $e->transaction?->reference ?? '—',
                    'created_at'  => $e->created_at?->format('d M Y H:i'),
                ]);
        }

        // Past allocations
        $history = CashAllocation::with('allocatedBy')
            ->where('teller_id', $teller->id)
            ->where('status', 'returned')
            ->orderByDesc('returned_at')
            ->limit(10)
            ->get();

        return Inertia::render('Staff/Teller/MyTill', [
            'allocation'       => $allocation,
            'till_balance'     => $tillBalance,
            'till_transactions'=> $tillTransactions,
            'history'          => $history,
        ]);
    }
}
