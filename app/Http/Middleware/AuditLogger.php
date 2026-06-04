<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuditLogger
{
    // Only log mutating methods — skip GET/HEAD (reads)
    private const LOGGED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    // Map URL patterns to human-readable action names
    private const ACTION_MAP = [
        'staff/transactions/deposit'        => 'DEPOSIT',
        'staff/transactions/withdraw'       => 'WITHDRAWAL',
        'staff/transactions/transfer'       => 'TRANSFER',
        'staff/approvals'                   => 'APPROVAL',
        'staff/teller/reverse'              => 'REVERSAL',
        'staff/teller/cancel'               => 'TXN_CANCEL',
        'staff/teller/cash-count'           => 'CASH_COUNT',
        'staff/teller/cash-allocation'      => 'CASH_ALLOCATION',
        'staff/customer-care/register'      => 'CUSTOMER_REGISTER',
        'staff/customer-care/kyc'           => 'KYC_ACTION',
        'staff/customer-care/accounts'      => 'ACCOUNT_ACTION',
        'staff/customer-care/customers'     => 'CUSTOMER_UPDATE',
        'staff/admin/staff'                 => 'STAFF_MANAGE',
        'staff/admin/branches'              => 'BRANCH_MANAGE',
        'staff/admin/roles'                 => 'ROLE_MANAGE',
        'staff/admin/customers'             => 'CUSTOMER_STATUS',
        'staff/branch/staff'               => 'BRANCH_STAFF_MANAGE',
        'staff/branch/settings'             => 'BRANCH_SETTINGS',
        'staff/branch/vault'                => 'VAULT_ACTION',
        'staff/compliance'                  => 'COMPLIANCE_ACTION',
        'staff/my-profile'                  => 'PROFILE_UPDATE',
        'staff/accounting'                  => 'ACCOUNTING_ACTION',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log mutating requests from authenticated staff
        if (!in_array($request->method(), self::LOGGED_METHODS)) {
            return $response;
        }

        $staff = Auth::guard('staff')->user();
        if (!$staff) {
            return $response;
        }

        $action      = $this->resolveAction($request->path());
        $result      = $response->isSuccessful() || $response->isRedirect() ? 'success' : 'failed';
        $description = $this->buildDescription($request, $action);

        try {
            DB::table('staff_audit_logs')->insert([
                'staff_id'        => $staff->id,
                'branch_id'       => $staff->branch_id,
                'action'          => $action,
                'description'     => $description,
                'permission_used' => null,
                'ip_address'      => $request->ip(),
                'result'          => $result,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } catch (\Exception) {
            // Never break the request over a logging failure
        }

        return $response;
    }

    private function resolveAction(string $path): string
    {
        foreach (self::ACTION_MAP as $pattern => $action) {
            if (str_contains($path, $pattern)) {
                return $action;
            }
        }
        return strtoupper(str_replace(['staff/', '/'], ['', '_'], $path));
    }

    private function buildDescription(Request $request, string $action): string
    {
        $method = $request->method();
        $path   = $request->path();

        // Enrich common actions with readable context
        $extra = match(true) {
            str_contains($path, 'deposit')    => '$' . number_format((float)$request->input('amount', 0), 2) . ' deposit',
            str_contains($path, 'withdraw')   => '$' . number_format((float)$request->input('amount', 0), 2) . ' withdrawal',
            str_contains($path, 'transfer')   => '$' . number_format((float)$request->input('amount', 0), 2) . ' transfer',
            str_contains($path, 'approve')    => 'Approved transaction',
            str_contains($path, 'reject')     => 'Rejected transaction',
            str_contains($path, 'reverse')    => 'Reversed transaction',
            str_contains($path, 'freeze')     => 'Account freeze',
            str_contains($path, 'unfreeze')   => 'Account unfreeze',
            str_contains($path, 'kyc')        => 'KYC document action',
            str_contains($path, 'register')   => 'Customer registration',
            str_contains($path, 'password')   => 'Password change',
            str_contains($path, 'vault')      => '$' . number_format((float)$request->input('amount', 0), 2) . ' vault operation',
            default                           => "{$method} /{$path}",
        };

        return "{$action}: {$extra}";
    }
}
