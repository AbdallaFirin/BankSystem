<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $staff = Auth::guard('staff')->user();

        if (!$staff) {
            return redirect('/login');
        }

        // If Super Admin tier (tier check from docs), allow all
        $role = DB::table('roles')->where('id', $staff->role_id)->first();
        if ($role && $role->tier === 'System' && $role->role_name === 'Super Admin') {
            return $next($request);
        }

        // Check if role has the specific permission
        $hasPermission = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $staff->role_id)
            ->where('permissions.permission_key', $permissionKey)
            ->exists();

        if ($hasPermission) {
            return $next($request);
        }

        // Log audit failure
        DB::table('staff_audit_logs')->insert([
            'staff_id'        => $staff->id,
            'branch_id'       => $staff->branch_id,
            'action'          => 'PERMISSION_DENIED',
            'description'     => 'Denied access to: ' . $request->path(),
            'permission_used' => $permissionKey,
            'ip_address'      => $request->ip(),
            'result'          => 'denied',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Unauthorized Access - Missing permission: ' . $permissionKey], 403);
        }

        return redirect()->route('error.403');
    }
}
