<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showStaffLogin()
    {
        return Inertia::render('Auth/StaffLogin');
    }

    public function staffLogin(Request $request)
    {
        $request->validate([
            'staff_id' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $credentials = [
            'ident_number' => strtoupper(trim($request->staff_id)),
            'password'     => $request->password,
        ];

        if (Auth::guard('staff')->attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::guard('staff')->user();

            // Role-based redirect
            if ($user->role) {
                if ($user->role->role_name === 'Super Admin') {
                    return redirect()->route('staff.admin.dashboard');
                }
                if ($user->role->role_name === 'Customer Care Officer') {
                    return redirect()->route('staff.customer-care.register');
                }
            }

            return redirect()->route('staff.dashboard');
        }

        return back()->withErrors([
            'staff_id' => 'Invalid Staff ID or password.',
        ])->onlyInput('staff_id');
    }

    public function staffLogout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function apiLogin(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $staff = \App\Models\Staff::where('email', $request->email)->first();

        if (!$staff || !\Illuminate\Support\Facades\Hash::check($request->password, $staff->password_hash)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $staff->createToken('staff-access')->plainTextToken;
        $permissions = \Illuminate\Support\Facades\DB::table('role_permissions')
                        ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                        ->where('role_permissions.role_id', $staff->role_id)
                        ->pluck('permissions.permission_key');

        return response()->json([
            'token' => $token,
            'user' => $staff,
            'permissions' => $permissions
        ]);
    }

    public function apiLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
