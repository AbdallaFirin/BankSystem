<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PendingApproval;
use App\Models\KycDocument;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Only load staff users here — never the Customer model
        $user = $request->user('staff');

        // Load permissions once – reused for both auth.user.permissions and notification gating
        $permissions = [];
        if ($user) {
            $permissions = DB::table('role_permissions')
                ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where('role_permissions.role_id', $user->role_id)
                ->pluck('permissions.permission_key')
                ->values()
                ->toArray();
        }

        $isSuperAdmin = $user && $user->role_id === 1;
        $can = fn(string $p) => $isSuperAdmin || in_array($p, $permissions);

        // Customer portal guard — resolved separately, never mixed with staff $user
        $customer = Auth::guard('customers')->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->load('role')->toArray(), [
                    'permissions' => $permissions,
                ]) : null,
                'customer' => $customer ? [
                    'id'                   => $customer->id,
                    'full_name'            => $customer->full_name,
                    'phone'                => $customer->phone,
                    'email'                => $customer->email,
                    'must_change_password' => $customer->must_change_password,
                    'unread_count'         => $customer->unreadNotificationsCount(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'info'    => $request->session()->get('info'),
            ],
            // Wrapped in a closure so Inertia evaluates it lazily (once per response)
            'notifications' => fn() => $this->buildNotifications($user, $can),
        ];
    }

    /**
     * Build role-aware notification items for the bell icon.
     */
    private function buildNotifications(?object $user, callable $can): array
    {
        if (!$user) {
            return ['count' => 0, 'items' => []];
        }

        $items = [];

        // ── Supervisor / Manager: pending approvals in their branch ──────────
        if ($can('approvals.read')) {
            $pending = PendingApproval::where('status', 'pending')
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $user->branch_id))
                ->with(['transaction', 'requestedBy'])
                ->orderByDesc('created_at')
                ->limit(15)
                ->get();

            foreach ($pending as $pa) {
                $txn = $pa->transaction;
                $items[] = [
                    'id'    => 'approval-' . $pa->id,
                    'type'  => 'approval_pending',
                    'title' => 'Pending Approval',
                    'body'  => ucfirst($txn->type ?? 'Transaction')
                             . ' · $' . number_format($txn->amount ?? 0, 2)
                             . ' — ' . ($pa->requestedBy->full_name ?? 'Staff'),
                    'link'  => route('staff.approvals'),
                    'time'  => $pa->created_at->diffForHumans(),
                    'icon'  => 'ti-checks',
                    'color' => 'amber',
                    'ts'    => $pa->created_at->timestamp,
                ];
            }
        }

        // ── Teller: their own transactions decided (approved/rejected) in last 24 h ──
        if ($can('transaction.deposit') || $can('transaction.withdraw')) {
            $decisions = PendingApproval::where('requested_by', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->where('decided_at', '>=', now()->subDay())
                ->with('transaction')
                ->orderByDesc('decided_at')
                ->limit(15)
                ->get();

            foreach ($decisions as $pa) {
                $approved = $pa->status === 'approved';
                $decidedAt = $pa->decided_at ?? $pa->updated_at;
                $items[] = [
                    'id'    => 'decision-' . $pa->id,
                    'type'  => 'teller_decision',
                    'title' => $approved ? 'Transaction Approved' : 'Transaction Rejected',
                    'body'  => ucfirst($pa->transaction->type ?? 'Transaction')
                             . ' · $' . number_format($pa->transaction->amount ?? 0, 2),
                    'link'  => route('staff.teller.deposit'),
                    'time'  => $decidedAt->diffForHumans(),
                    'icon'  => $approved ? 'ti-circle-check' : 'ti-circle-x',
                    'color' => $approved ? 'emerald' : 'red',
                    'ts'    => $decidedAt->timestamp,
                ];
            }
        }

        // ── Compliance officer: all pending KYC documents ────────────────────
        if ($can('compliance.check')) {
            $kycCount = KycDocument::where('status', 'pending')->count();
            if ($kycCount > 0) {
                $items[] = [
                    'id'    => 'kyc-compliance',
                    'type'  => 'kyc_pending',
                    'title' => 'KYC Reviews Pending',
                    'body'  => $kycCount . ' document' . ($kycCount > 1 ? 's' : '') . ' awaiting compliance review',
                    'link'  => route('staff.compliance.index'),
                    'time'  => 'now',
                    'icon'  => 'ti-file-search',
                    'color' => 'sky',
                    'ts'    => now()->timestamp,
                ];
            }
        }

        // ── Customer Care: pending KYC uploads (only when not also compliance) ─
        if ($can('kyc.upload') && !$can('compliance.check')) {
            $kycCount = KycDocument::where('status', 'pending')->count();
            if ($kycCount > 0) {
                $items[] = [
                    'id'    => 'kyc-care',
                    'type'  => 'kyc_pending',
                    'title' => 'KYC Documents Pending',
                    'body'  => $kycCount . ' document' . ($kycCount > 1 ? 's' : '') . ' awaiting review',
                    'link'  => route('staff.customer-care.kyc.list'),
                    'time'  => 'now',
                    'icon'  => 'ti-file-upload',
                    'color' => 'sky',
                    'ts'    => now()->timestamp,
                ];
            }
        }

        // Sort newest first, then strip the internal sort key
        usort($items, fn($a, $b) => $b['ts'] <=> $a['ts']);
        foreach ($items as &$item) {
            unset($item['ts']);
        }

        return [
            'count' => count($items),
            'items' => array_values($items),
        ];
    }
}
