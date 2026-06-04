<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showStaffLogin()
    {
        return Inertia::render('Auth/StaffLogin');
    }

    /* POST /login — step 1: verify creds, send 2FA */
    public function staffLogin(Request $request)
    {
        $request->validate(['staff_id' => ['required', 'string'], 'password' => ['required']]);

        $identNumber = strtoupper(trim($request->staff_id));
        $credentials = ['ident_number' => $identNumber, 'password' => $request->password];

        if (!Auth::guard('staff')->validate($credentials)) {
            return back()->withErrors(['staff_id' => 'Invalid Staff ID or password.'])->onlyInput('staff_id');
        }

        $staff = Staff::where('ident_number', $identNumber)->first();

        // Block invited staff — they must accept their email invite first
        if ($staff->status === 'invited') {
            return back()->withErrors(['staff_id' => 'Your account is pending activation. Please check your email for the invite link.'])->onlyInput('staff_id');
        }

        // Block inactive/suspended accounts
        if ($staff->status === 'inactive') {
            return back()->withErrors(['staff_id' => 'Your account has been deactivated. Contact your administrator.'])->onlyInput('staff_id');
        }

        if (!$staff->email) {
            Auth::guard('staff')->attempt($credentials);
            $request->session()->regenerate();
            return $this->staffRedirect(Auth::guard('staff')->user());
        }

        $otp = OtpCode::generate($identNumber, $staff->email, 'staff', '2fa');
        $this->sendOtpMail($staff->email, $staff->full_name, $otp->code, '2fa');
        $request->session()->put('2fa_staff_id', $identNumber);

        return redirect()->route('staff.verify-2fa');
    }

    /* GET /verify-2fa */
    public function showVerify2fa(Request $request)
    {
        if (!$request->session()->has('2fa_staff_id')) {
            return redirect()->route('staff.login');
        }
        $email = Staff::where('ident_number', $request->session()->get('2fa_staff_id'))?->value('email') ?? '';
        return Inertia::render('Auth/Verify2fa', ['email_hint' => $this->maskEmail($email)]);
    }

    /* POST /verify-2fa */
    public function verify2fa(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $identNumber = $request->session()->get('2fa_staff_id');
        if (!$identNumber) {
            return redirect()->route('staff.login')->withErrors(['code' => 'Session expired. Please log in again.']);
        }

        $otp = OtpCode::findValid($identNumber, 'staff', $request->code, '2fa');
        if (!$otp) {
            return back()->withErrors(['code' => 'Invalid or expired code. Please try again.']);
        }

        $otp->markUsed();
        $request->session()->forget('2fa_staff_id');

        $staff = Staff::where('ident_number', $identNumber)->firstOrFail();
        Auth::guard('staff')->login($staff);
        $request->session()->regenerate();

        return $this->staffRedirect($staff);
    }

    /* POST /resend-2fa */
    public function resend2fa(Request $request)
    {
        $identNumber = $request->session()->get('2fa_staff_id');
        if ($identNumber) {
            $staff = Staff::where('ident_number', $identNumber)->first();
            if ($staff?->email) {
                $otp = OtpCode::generate($identNumber, $staff->email, 'staff', '2fa');
                $this->sendOtpMail($staff->email, $staff->full_name, $otp->code, '2fa');
            }
        }
        return back()->with('info', 'A new code has been sent.');
    }

    public function staffLogout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /* GET /forgot-password */
    public function showForgotPassword()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /* POST /forgot-password */
    public function sendResetOtp(Request $request)
    {
        $request->validate(['staff_id' => 'required|string']);
        $identNumber = strtoupper(trim($request->staff_id));
        $staff = Staff::where('ident_number', $identNumber)->first();

        if ($staff?->email) {
            $otp = OtpCode::generate($identNumber, $staff->email, 'staff', 'reset');
            $this->sendOtpMail($staff->email, $staff->full_name, $otp->code, 'reset');
        }

        return redirect()->route('staff.reset-password', ['staff_id' => $identNumber])
            ->with('info', 'If that Staff ID exists, a reset code was sent to the associated email.');
    }

    /* GET /reset-password */
    public function showResetPassword(Request $request)
    {
        return Inertia::render('Auth/ResetPassword', ['staff_id' => $request->query('staff_id', '')]);
    }

    /* POST /reset-password */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|string',
            'code'     => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $identNumber = strtoupper(trim($request->staff_id));
        $otp = OtpCode::findValid($identNumber, 'staff', $request->code, 'reset');
        if (!$otp) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $staff = Staff::where('ident_number', $identNumber)->first();
        if (!$staff) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $otp->markUsed();
        $staff->update(['password' => Hash::make($request->password)]);

        return redirect('/login')->with('success', 'Password reset successfully. You can now log in.');
    }

    /* ── Helpers ── */
    private function staffRedirect($user)
    {
        if ($user->role?->role_name === 'Super Admin') return redirect()->route('staff.admin.dashboard');
        if ($user->role?->role_name === 'Customer Care Officer') return redirect()->route('staff.customer-care.register');
        return redirect()->route('staff.dashboard');
    }

    private function sendOtpMail(string $to, string $name, string $code, string $purpose): void
    {
        try {
            Mail::send('mail.otp-code', compact('name', 'code', 'purpose'), function ($msg) use ($to, $purpose) {
                $subject = $purpose === 'reset' ? 'Reset Your Password — Gobaad Bank' : 'Your Login Code — Gobaad Bank';
                $msg->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('OTP email failed', ['to' => $to, 'error' => $e->getMessage()]);
        }
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) return '****';
        [$local, $domain] = explode('@', $email, 2);
        $visible = min(3, strlen($local));
        return substr($local, 0, $visible) . str_repeat('*', max(0, strlen($local) - $visible)) . '@' . $domain;
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

    /* ────────────────────────────────────────────────────────
     *  Staff Invite Accept Flow
     *  GET  /staff/accept-invite?token=xxx  → show form
     *  POST /staff/accept-invite             → set password & activate
     * ──────────────────────────────────────────────────────── */
    public function showAcceptInvite(Request $request)
    {
        $token = $request->query('token');
        $staff = Staff::where('temp_password', $token)->where('status', 'invited')->first();

        if (!$staff) {
            return redirect()->route('login')
                ->withErrors(['staff_id' => 'This invite link is invalid or has already been used.']);
        }

        return Inertia::render('Auth/AcceptInvite', [
            'token'      => $token,
            'staff_name' => $staff->full_name,
            'staff_id'   => $staff->ident_number,
            'role'       => $staff->role?->role_name,
            'branch'     => $staff->branch?->branch_name,
        ]);
    }

    public function acceptInvite(Request $request)
    {
        $request->validate([
            'token'                 => 'required|string',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $staff = Staff::where('temp_password', $request->token)->where('status', 'invited')->first();

        if (!$staff) {
            return back()->withErrors(['token' => 'This invite link is invalid or has already been used.']);
        }

        $staff->update([
            'password'              => Hash::make($request->password),
            'status'                => 'active',
            'force_password_change' => false,
            'temp_password'         => null,
        ]);

        return redirect()->route('login')
            ->with('success', 'Your account is now active. Please log in with your Staff ID: ' . $staff->ident_number);
    }
}
