<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CustomerAuthController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /customer/login
     * ───────────────────────────────────────────── */
    public function showLogin()
    {
        if (Auth::guard('customers')->check()) {
            return redirect()->route('customer.dashboard');
        }
        return Inertia::render('Customer/Login');
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/login  (step 1 — verify credentials)
     * ───────────────────────────────────────────── */
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        // Verify credentials without fully logging in
        if (!Auth::guard('customers')->validate(['phone' => $request->phone, 'password' => $request->password])) {
            return back()->withErrors(['phone' => 'The phone number or password is incorrect.'])->onlyInput('phone');
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer || !$customer->email) {
            // No email on file — skip 2FA and log in directly
            Auth::guard('customers')->attempt(['phone' => $request->phone, 'password' => $request->password], $request->boolean('remember'));
            $request->session()->regenerate();
            $customer?->update(['last_login_at' => now()]);
            return redirect()->intended(route('customer.dashboard'));
        }

        // Generate 2FA OTP
        $otp = OtpCode::generate($customer->phone, $customer->email, 'customer', '2fa');
        $this->sendOtpMail($customer->email, $customer->full_name, $otp->code, '2fa');

        // Store pending login in session (never log in yet)
        $request->session()->put('2fa_customer_phone', $customer->phone);
        $request->session()->put('2fa_remember', $request->boolean('remember'));

        return redirect()->route('customer.verify-2fa');
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/verify-2fa
     * ───────────────────────────────────────────── */
    public function showVerify2fa(Request $request)
    {
        if (!$request->session()->has('2fa_customer_phone')) {
            return redirect()->route('customer.login');
        }
        return Inertia::render('Customer/Verify2fa', [
            'email_hint' => $this->maskEmail(
                Customer::where('phone', $request->session()->get('2fa_customer_phone'))?->first()?->email ?? ''
            ),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/verify-2fa
     * ───────────────────────────────────────────── */
    public function verify2fa(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $phone = $request->session()->get('2fa_customer_phone');
        if (!$phone) {
            return redirect()->route('customer.login')->withErrors(['code' => 'Session expired. Please log in again.']);
        }

        $otp = OtpCode::findValid($phone, 'customer', $request->code, '2fa');
        if (!$otp) {
            return back()->withErrors(['code' => 'Invalid or expired code. Please try again.']);
        }

        $otp->markUsed();
        $request->session()->forget(['2fa_customer_phone', '2fa_remember']);

        $customer = Customer::where('phone', $phone)->firstOrFail();
        Auth::guard('customers')->login($customer, $request->session()->pull('2fa_remember', false));
        $request->session()->regenerate();
        $customer->update(['last_login_at' => now()]);

        return redirect()->intended(route('customer.dashboard'));
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/logout
     * ───────────────────────────────────────────── */
    public function logout(Request $request)
    {
        Auth::guard('customers')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('customer.login')->with('success', 'You have been logged out successfully.');
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/change-password
     * ───────────────────────────────────────────── */
    public function showChangePassword()
    {
        $forced = Auth::guard('customers')->user()?->must_change_password ?? false;
        return Inertia::render('Customer/ChangePassword', ['forced' => $forced]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/change-password
     * ───────────────────────────────────────────── */
    public function changePassword(Request $request)
    {
        $customer = Auth::guard('customers')->user();
        $forced   = $customer->must_change_password;

        $rules = ['password' => 'required|string|min:8|confirmed'];

        // Only require current password for voluntary changes
        if (!$forced) {
            $rules['current_password'] = 'required|string';
        }
        $request->validate($rules);

        if (!$forced && !Hash::check($request->current_password, $customer->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $customer->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('customer.dashboard')
            ->with('success', 'Password updated successfully.');
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/forgot-password
     * ───────────────────────────────────────────── */
    public function showForgotPassword()
    {
        return Inertia::render('Customer/ForgotPassword');
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/forgot-password  (send OTP)
     * ───────────────────────────────────────────── */
    public function sendResetOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $customer = Customer::where('phone', $request->phone)->first();

        // Always return success to prevent user enumeration
        if ($customer && $customer->email) {
            $otp = OtpCode::generate($customer->phone, $customer->email, 'customer', 'reset');
            $this->sendOtpMail($customer->email, $customer->full_name, $otp->code, 'reset');
        }

        return redirect()->route('customer.reset-password', ['phone' => $request->phone])
            ->with('info', 'If that phone number is registered, a reset code has been sent to the associated email.');
    }

    /* ─────────────────────────────────────────────
     *  GET /customer/reset-password
     * ───────────────────────────────────────────── */
    public function showResetPassword(Request $request)
    {
        return Inertia::render('Customer/ResetPassword', [
            'phone' => $request->query('phone', ''),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/reset-password  (verify OTP + set new password)
     * ───────────────────────────────────────────── */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'code'     => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $otp = OtpCode::findValid($request->phone, 'customer', $request->code, 'reset');
        if (!$otp) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $customer = Customer::where('phone', $request->phone)->first();
        if (!$customer) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $otp->markUsed();
        $customer->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('customer.login')
            ->with('success', 'Password reset successfully. You can now log in with your new password.');
    }

    /* ─────────────────────────────────────────────
     *  POST /customer/resend-otp
     * ───────────────────────────────────────────── */
    public function resendOtp(Request $request)
    {
        $phone   = $request->session()->get('2fa_customer_phone');
        $purpose = $request->input('purpose', '2fa');

        if ($purpose === '2fa' && $phone) {
            $customer = Customer::where('phone', $phone)->first();
            if ($customer && $customer->email) {
                $otp = OtpCode::generate($customer->phone, $customer->email, 'customer', '2fa');
                $this->sendOtpMail($customer->email, $customer->full_name, $otp->code, '2fa');
            }
        }

        return back()->with('info', 'A new code has been sent.');
    }

    /* ─────────────────────────────────────────────
     *  Helpers
     * ───────────────────────────────────────────── */
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
}
