<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LedgerEntry;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    public function ledger()
    {
        $staff = Auth::guard('staff')->user();
        
        // Show all ledger entries for the branch
        $entries = LedgerEntry::with(['account', 'transaction'])
            ->where('branch_id', $staff->branch_id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return \Inertia\Inertia::render('Staff/Accounting/Ledger', [
            'entries' => $entries
        ]);
    }

    public function trialBalance()
    {
        $staff = Auth::guard('staff')->user();

        // Calculate trial balance for the branch
        $trialBalance = DB::table('ledger_entries')
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->select(
                'accounts.account_number',
                'accounts.account_name',
                DB::raw("SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE 0 END) as total_debit"),
                DB::raw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE 0 END) as total_credit")
            )
            ->where('ledger_entries.branch_id', $staff->branch_id)
            ->groupBy('accounts.id', 'accounts.account_number', 'accounts.account_name')
            ->get();

        $grandTotalDebit = $trialBalance->sum('total_debit');
        $grandTotalCredit = $trialBalance->sum('total_credit');

        return \Inertia\Inertia::render('Staff/Accounting/TrialBalance', [
            'trialBalance' => $trialBalance,
            'totals' => [
                'debit' => $grandTotalDebit,
                'credit' => $grandTotalCredit,
                'is_balanced' => abs($grandTotalDebit - $grandTotalCredit) < 0.01
            ]
        ]);
    }
}
