<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Staff;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/branch/reports
     * ───────────────────────────────────────────── */
    public function branchReport(Request $request)
    {
        $staff            = Auth::guard('staff')->user()->load('role', 'branch');
        $branchRestricted = $staff->role?->branch_restricted ?? true;

        // Date range — default to current month
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();
        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $base = Transaction::whereBetween('transactions.created_at', [$dateFrom, $dateTo]);

        if ($branchRestricted) {
            $base->where('transactions.branch_id', $staff->branch_id);
        } elseif ($request->branch_id) {
            $base->where('transactions.branch_id', $request->branch_id);
        }

        // ── Summary cards ──────────────────────────────────────────────────────
        $summary = [
            'total_transactions' => (clone $base)->count(),
            'total_volume'       => (float)(clone $base)->sum('transactions.amount'),
            'completed'          => (clone $base)->where('transactions.status', 'completed')->count(),
            'pending'            => (clone $base)->where('transactions.status', 'pending_approval')->count(),
            'flagged'            => (clone $base)->where('transactions.is_flagged', true)->count(),
            'avg_amount'         => (float)((clone $base)->avg('transactions.amount') ?? 0),
        ];

        // ── Type breakdown ─────────────────────────────────────────────────────
        $byType = (clone $base)
            ->select('transactions.type', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('transactions.type')
            ->get()
            ->map(fn($r) => [
                'type'  => $r->type,
                'count' => (int)$r->cnt,
                'total' => (float)$r->total,
            ]);

        // ── Daily trend ────────────────────────────────────────────────────────
        $dailyTrend = (clone $base)
            ->select(
                DB::raw('DATE(transactions.created_at) as date'),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(transactions.amount) as amt')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'date'   => $r->date,
                'count'  => (int)$r->cnt,
                'amount' => (float)$r->amt,
            ]);

        // ── Top tellers by volume ──────────────────────────────────────────────
        $topTellers = (clone $base)
            ->join('staff', 'transactions.initiated_by', '=', 'staff.id')
            ->select(
                'staff.full_name',
                'staff.ident_number',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('staff.id', 'staff.full_name', 'staff.ident_number')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Note: the $base query already scopes by transactions.created_at and transactions.branch_id

        // ── Largest transactions ───────────────────────────────────────────────
        $largestTxns = (clone $base)
            ->with(['primaryAccount.customer'])
            ->orderByDesc('amount')
            ->limit(10)
            ->get(['id', 'reference', 'type', 'amount', 'status', 'account_id', 'created_at']);

        // ── Vault balance (branch-restricted only) ─────────────────────────────
        $vaultBalance = null;
        if ($branchRestricted) {
            $vault = Account::where('account_number', 'VAULT-' . $staff->branch_id)->first();
            if ($vault) {
                $vaultBalance = (float) LedgerEntry::where('account_id', $vault->id)
                    ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                    ->value('bal') ?? 0.0;
            }
        }

        // Branches list (for Super Admin branch picker)
        $branches = $branchRestricted
            ? []
            : Branch::orderBy('branch_name')->get(['id', 'branch_name'])->toArray();

        return Inertia::render('Staff/BranchManager/Reports', [
            'summary'      => $summary,
            'by_type'      => $byType,
            'daily_trend'  => $dailyTrend,
            'top_tellers'  => $topTellers,
            'largest_txns' => $largestTxns,
            'vault_balance'=> $vaultBalance,
            'branch_name'  => $branchRestricted
                ? ($staff->branch?->branch_name ?? '—')
                : ($request->branch_id ? Branch::find($request->branch_id)?->branch_name : 'All Branches'),
            'swift_code'   => $branchRestricted
                ? ($staff->branch?->swift_code ?? null)
                : ($request->branch_id ? Branch::find($request->branch_id)?->swift_code : null),
            'branches'     => $branches,
            'date_from'    => $dateFrom->toDateString(),
            'date_to'      => $dateTo->toDateString(),
            'filters'      => $request->only('date_from', 'date_to', 'branch_id'),
            'is_restricted'=> $branchRestricted,
        ]);
    }
}
