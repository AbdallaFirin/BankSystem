<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashAllocation;
use App\Models\CashCount;
use App\Models\LedgerEntry;
use App\Models\StaffAuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StaffProfileController extends Controller
{
    /* ─────────────────────────────────────────────
     *  GET /staff/my-profile
     * ───────────────────────────────────────────── */
    public function show()
    {
        $staff = Auth::guard('staff')->user();
        $staff->load(['role', 'branch']);

        // Recent activity: last 5 audit log entries
        $recentActivity = StaffAuditLog::where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['action', 'description', 'result', 'created_at']);

        // Stats
        $txCount = Transaction::where('initiated_by', $staff->id)->count();

        $cashCountCount = CashCount::where('staff_id', $staff->id)->count();

        $activeTill = CashAllocation::where('teller_id', $staff->id)
            ->whereIn('status', ['pending', 'acknowledged'])
            ->latest('allocated_at')
            ->first();

        $tillBalance = 0.0;
        if ($activeTill) {
            $tillBalance = (float) LedgerEntry::where('account_id', $activeTill->till_account_id)
                ->selectRaw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as bal")
                ->value('bal') ?? 0.0;
        }

        return Inertia::render('Staff/MyProfile', [
            'staff'           => [
                'id'                    => $staff->id,
                'full_name'             => $staff->full_name,
                'email'                 => $staff->email,
                'phone'                 => $staff->phone,
                'ident_number'          => $staff->ident_number,
                'status'                => $staff->status,
                'avatar'                => $staff->avatar,
                'gender'                => $staff->gender,
                'date_of_birth'         => $staff->date_of_birth?->format('Y-m-d'),
                'address'               => $staff->address,
                'emergency_contact'     => $staff->emergency_contact,
                'hired_at'              => $staff->hired_at?->format('Y-m-d'),
                'role_name'             => $staff->role?->role_name,
                'branch_name'           => $staff->branch?->branch_name,
                'created_at'            => $staff->created_at?->format('d M Y'),
                // Only expose the temp password on this page — wiped after first self-change
                'temp_password'         => $staff->force_password_change ? $staff->temp_password : null,
                'force_password_change' => (bool) $staff->force_password_change,
            ],
            'stats' => [
                'transactions'  => $txCount,
                'cash_counts'   => $cashCountCount,
                'till_balance'  => $tillBalance,
                'till_open'     => $activeTill?->status === 'acknowledged',
            ],
            'recent_activity' => $recentActivity,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/my-profile/info
     *  Update personal info
     * ───────────────────────────────────────────── */
    public function updateInfo(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $request->validate([
            'full_name'         => 'required|string|max:255',
            'email'             => 'required|email|unique:staff,email,' . $staff->id,
            'phone'             => 'required|string|unique:staff,phone,' . $staff->id,
            'gender'            => 'nullable|in:Male,Female,Other',
            'date_of_birth'     => 'nullable|date|before:today',
            'address'           => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
        ]);

        $staff->update($request->only([
            'full_name', 'email', 'phone',
            'gender', 'date_of_birth', 'address', 'emergency_contact',
        ]));

        return back()->with('success', 'Profile updated successfully.');
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/my-profile/password
     *  Change password
     * ───────────────────────────────────────────── */
    public function updatePassword(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $staff->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $staff->update([
            'password'              => Hash::make($request->new_password),
            'force_password_change' => false,
            'temp_password'         => null,
            'password_changed_at'   => now(),   // reset 90-day expiry clock
            'failed_attempts'       => 0,       // clear any failed attempts on password change
            'locked_until'          => null,
        ]);

        return back()->with('success', 'Password changed successfully. Keep it safe!');
    }

    /* ─────────────────────────────────────────────
     *  POST /staff/my-profile/avatar
     *  Upload profile photo
     * ───────────────────────────────────────────── */
    public function updateAvatar(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Delete old avatar
        if ($staff->avatar && Storage::disk('public')->exists($staff->avatar)) {
            Storage::disk('public')->delete($staff->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $staff->update(['avatar' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }
}
