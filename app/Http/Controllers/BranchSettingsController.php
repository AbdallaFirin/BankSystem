<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CashAllocation;
use App\Models\LedgerEntry;
use App\Models\Staff;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BranchSettingsController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/branch/settings
     * ───────────────────────────────────────────── */
    public function index()
    {
        $staff  = Auth::guard('staff')->user();
        $branch = Branch::with('manager')->findOrFail($staff->branch_id);

        // Live vault balance
        $vault        = Account::where('account_number', 'VAULT-' . $branch->id)->first();
        $vaultBalance = $vault
            ? (float) LedgerEntry::where('account_id', $vault->id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0.0
            : 0.0;

        // Quick branch stats
        $stats = [
            'staff_count'         => Staff::where('branch_id', $branch->id)->count(),
            'customer_count'      => Customer::where('home_branch_id', $branch->id)->count(),
            'account_count'       => Account::where('home_branch_id', $branch->id)
                                        ->whereNotIn(DB::raw('LEFT(account_number,5)'), ['VAULT', 'TILL-'])
                                        ->count(),
            'pending_approvals'   => Transaction::where('branch_id', $branch->id)
                                        ->where('status', 'pending_approval')->count(),
            'active_tills'        => CashAllocation::where('branch_id', $branch->id)
                                        ->whereIn('status', ['pending', 'acknowledged'])->count(),
            'vault_balance'       => $vaultBalance,
        ];

        // Branch staff list (for manager reassignment)
        $staffList = Staff::with('role')
            ->where('branch_id', $branch->id)
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'full_name' => $s->full_name, 'role' => $s->role?->role_name]);

        return Inertia::render('Staff/BranchManager/BranchSettings', [
            'branch'    => $branch,
            'stats'     => $stats,
            'staff_list'=> $staffList,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/branch/settings
     *  Update branch profile & configuration
     * ───────────────────────────────────────────── */
    public function update(Request $request)
    {
        $staff  = Auth::guard('staff')->user();
        $branch = Branch::findOrFail($staff->branch_id);

        $validated = $request->validate([
            'branch_name'          => 'required|string|max:255',
            'city'                 => 'required|string|max:100',
            'address'              => 'required|string|max:500',
            'phone'                => 'required|string|max:30',
            'email'                => 'nullable|email|max:150',
            'swift_code'           => 'nullable|string|max:20',
            'routing_number'       => 'nullable|string|max:30',
            'description'          => 'nullable|string|max:1000',
            'working_hours_start'  => 'required|string|max:10',
            'working_hours_end'    => 'required|string|max:10',
            'working_days'         => 'required|array|min:1',
            'working_days.*'       => 'string|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'vault_min_threshold'  => 'nullable|numeric|min:0',
            'currency_code'        => 'required|string|max:10',
            'timezone'             => 'required|string|max:60',
        ]);

        $branch->update($validated);

        return back()->with('success', 'Branch settings updated successfully.');
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/branch/vault/deposit
     *  Branch Manager direct vault deposit
     * ───────────────────────────────────────────── */
    public function vaultDeposit(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $staff = Auth::guard('staff')->user();
        $vault = Account::where('account_number', 'VAULT-' . $staff->branch_id)->first();

        if (!$vault) {
            return back()->withErrors(['error' => 'Vault account not found for this branch.']);
        }

        DB::transaction(function () use ($staff, $vault, $request) {
            // Create a transaction record for audit
            $ref = 'VLT-' . strtoupper(substr(md5(uniqid()), 0, 8));

            $transaction = \App\Models\Transaction::create([
                'reference'   => $ref,
                'type'        => 'vault_deposit',
                'amount'      => (float) $request->amount,
                'status'      => 'completed',
                'branch_id'   => $staff->branch_id,
                'initiated_by'=> $staff->id,
                'description' => $request->description ?? 'Vault Deposit by Branch Manager',
            ]);

            // Credit the vault
            $currentBal = (float) LedgerEntry::where('account_id', $vault->id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0.0;

            LedgerEntry::create([
                'account_id'    => $vault->id,
                'transaction_id'=> $transaction->id,
                'entry_type'    => 'credit',
                'amount'        => (float) $request->amount,
                'balance_after' => $currentBal + (float) $request->amount,
                'branch_id'     => $staff->branch_id,
            ]);
        });

        return back()->with('success', 'Vault deposit of $' . number_format($request->amount, 2) . ' recorded successfully.');
    }
}
