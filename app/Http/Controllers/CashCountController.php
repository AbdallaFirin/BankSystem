<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashAllocation;
use App\Models\CashCount;
use App\Models\CashCountDenomination;
use App\Models\Account;
use App\Models\LedgerEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CashCountController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/teller/cash-count
     *  Show denomination entry form + recent counts
     * ───────────────────────────────────────────── */
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        // If the teller has an active (acknowledged) allocation, show their till balance.
        // Otherwise show the branch vault balance (fallback / supervisor view).
        $activeAlloc   = CashAllocation::where('teller_id', $staff->id)
            ->where('status', 'acknowledged')
            ->latest('allocated_at')
            ->first();

        if ($activeAlloc) {
            $systemBalance = (float) LedgerEntry::where('account_id', $activeAlloc->till_account_id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0.0;
            $balanceLabel = 'My Till Balance';
        } else {
            $vault         = Account::where('account_number', 'VAULT-' . $staff->branch_id)->first();
            $systemBalance = $vault
                ? (float) LedgerEntry::where('account_id', $vault->id)
                    ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                    ->value('bal') ?? 0
                : 0.0;
            $balanceLabel = 'Vault Balance';
        }

        // Recent cash counts for this branch (any staff)
        $query = CashCount::with('staff')
            ->where('branch_id', $staff->branch_id)
            ->orderByDesc('counted_at');

        if ($request->filled('date_from')) {
            $query->whereDate('counted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('counted_at', '<=', $request->date_to);
        }

        $counts = $query->paginate(15)->withQueryString();

        return Inertia::render('Staff/Teller/CashCount', [
            'counts'         => $counts,
            'system_balance' => $systemBalance,
            'balance_label'  => $balanceLabel,
            'active_alloc'   => $activeAlloc ? [
                'id'     => $activeAlloc->id,
                'amount' => $activeAlloc->amount,
                'status' => $activeAlloc->status,
            ] : null,
            'filters'        => $request->only('date_from', 'date_to'),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/teller/cash-count
     *  Save denomination counts + reconcile
     * ───────────────────────────────────────────── */
    public function store(Request $request)
    {
        $request->validate([
            'session_type'                      => 'required|in:opening,midday,closing',
            'denominations'                     => 'required|array|min:1',
            'denominations.*.denomination'      => 'required|numeric|min:0',
            'denominations.*.quantity'          => 'required|integer|min:0',
            'notes'                             => 'nullable|string|max:500',
        ]);

        $staff = Auth::guard('staff')->user();

        // Physical total from denomination entries
        $physicalTotal = collect($request->denominations)
            ->sum(fn($d) => (float)$d['denomination'] * (int)$d['quantity']);

        // Vault / system balance at this moment
        $vault         = Account::where('account_number', 'VAULT-' . $staff->branch_id)->first();
        $systemBalance = $vault
            ? (float) LedgerEntry::where('account_id', $vault->id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0
            : 0.0;

        $difference = round($physicalTotal - $systemBalance, 2);

        $status = match(true) {
            abs($difference) < 0.01 => 'balanced',
            $difference > 0          => 'overage',
            default                  => 'shortage',
        };

        DB::transaction(function () use ($request, $staff, $physicalTotal, $systemBalance, $difference, $status) {
            $cashCount = CashCount::create([
                'staff_id'       => $staff->id,
                'branch_id'      => $staff->branch_id,
                'session_type'   => $request->session_type,
                'physical_total' => $physicalTotal,
                'system_balance' => $systemBalance,
                'difference'     => $difference,
                'status'         => $status,
                'notes'          => $request->notes,
                'counted_at'     => now(),
            ]);

            foreach ($request->denominations as $d) {
                $qty = (int)$d['quantity'];
                if ($qty > 0) {
                    CashCountDenomination::create([
                        'cash_count_id' => $cashCount->id,
                        'denomination'  => (float)$d['denomination'],
                        'quantity'      => $qty,
                        'subtotal'      => round((float)$d['denomination'] * $qty, 2),
                    ]);
                }
            }
        });

        $diffLabel = '$' . number_format(abs($difference), 2);
        $msg = match($status) {
            'balanced' => 'Cash count recorded — Balanced ✓',
            'overage'  => "Cash count recorded — Overage of {$diffLabel}",
            'shortage' => "Cash count recorded — Shortage of {$diffLabel}",
        };

        return redirect()->route('staff.teller.cash-count.index')->with('success', $msg);
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/teller/cash-count/approvals
     *  Supervisor view — pending + reviewed counts
     * ───────────────────────────────────────────── */
    public function approvals(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $pending = CashCount::with(['staff', 'denominations'])
            ->where('branch_id', $staff->branch_id)
            ->where('approval_status', 'pending')
            ->orderByDesc('counted_at')
            ->paginate(20, ['*'], 'pending_page')
            ->withQueryString();

        $reviewed = CashCount::with(['staff', 'verifiedBy'])
            ->where('branch_id', $staff->branch_id)
            ->whereIn('approval_status', ['verified', 'flagged'])
            ->orderByDesc('verified_at')
            ->paginate(20, ['*'], 'reviewed_page')
            ->withQueryString();

        $pendingCount = CashCount::where('branch_id', $staff->branch_id)
            ->where('approval_status', 'pending')
            ->count();

        return Inertia::render('Staff/Teller/CashCountApprovals', [
            'pending'      => $pending,
            'reviewed'     => $reviewed,
            'pendingCount' => $pendingCount,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/teller/cash-count/{id}/approve
     *  Supervisor approves a cash count
     * ───────────────────────────────────────────── */
    public function approve(int $id)
    {
        $staff = Auth::guard('staff')->user();

        $count = CashCount::where('branch_id', $staff->branch_id)
            ->where('approval_status', 'pending')
            ->findOrFail($id);

        $count->update([
            'approval_status' => 'verified',
            'verified_by'     => $staff->id,
            'verified_at'     => now(),
            'flag_reason'     => null,
        ]);

        return back()->with('success', "Cash Count #{$count->id} verified by {$staff->full_name}.");
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/teller/cash-count/{id}/flag
     *  Supervisor flags a count with a reason
     * ───────────────────────────────────────────── */
    public function flag(Request $request, int $id)
    {
        $request->validate([
            'flag_reason' => 'required|string|max:500',
        ]);

        $staff = Auth::guard('staff')->user();

        $count = CashCount::where('branch_id', $staff->branch_id)
            ->where('approval_status', 'pending')
            ->findOrFail($id);

        $count->update([
            'approval_status' => 'flagged',
            'verified_by'     => $staff->id,
            'verified_at'     => now(),
            'flag_reason'     => $request->flag_reason,
        ]);

        return back()->with('success', "Cash Count #{$count->id} flagged for follow-up.");
    }

    /* ─────────────────────────────────────────────
     *  GET /staff/teller/cash-count/{id}/print
     *  Download PDF cash count sheet
     * ───────────────────────────────────────────── */
    public function printSheet(int $id)
    {
        $staff = Auth::guard('staff')->user();

        $count = CashCount::with(['staff', 'branch', 'denominations'])
            ->where('branch_id', $staff->branch_id)
            ->findOrFail($id);

        $pdf = Pdf::loadView('cash-counts.sheet', ['count' => $count])
            ->setPaper('a4', 'portrait');

        $filename = 'CashCount_' . $count->id . '_' . $count->counted_at->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}
