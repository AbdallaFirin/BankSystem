<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Models\Staff;
use App\Models\Customer;

class BranchScopeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $staff = Auth::guard('sanctum')->user();
        if ($staff) {
            $role = DB::table('roles')->where('id', $staff->role_id)->first();
            
            // If the role is explicitly branch restricted, inject the global scopes automatically
            if ($role && $role->branch_restricted) {
                // Determine scope logic based on the table's specific column structure
                $branchScope = function ($builder) use ($staff) {
                    $builder->where('branch_id', $staff->branch_id);
                };
                
                $homeBranchScope = function ($builder) use ($staff) {
                    $builder->where('home_branch_id', $staff->branch_id);
                };

                Account::addGlobalScope('branch', $homeBranchScope);
                Customer::addGlobalScope('branch', $homeBranchScope);
                Transaction::addGlobalScope('branch', $branchScope);
                LedgerEntry::addGlobalScope('branch', $branchScope);
                Staff::addGlobalScope('branch', $branchScope);
            }
        }
        
        return $next($request);
    }
}
