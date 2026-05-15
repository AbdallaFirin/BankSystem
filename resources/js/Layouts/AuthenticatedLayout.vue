<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';

const showingMobileNav = ref(false);
const authStore = useAuthStore();
const page = usePage();

// Sidebar collapse — persisted in localStorage
const sidebarCollapsed = ref(false);
onMounted(() => {
    const saved = localStorage.getItem('sidebar_collapsed');
    if (saved === 'true') sidebarCollapsed.value = true;
});
function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebar_collapsed', sidebarCollapsed.value);
}

// Current user info
const currentUser = computed(() => page.props.auth?.user ?? {});

// Force password change flag
const mustChangePassword = computed(() => !!page.props.auth?.user?.force_password_change);

// Sync Pinia with Inertia on every visit/mount
onMounted(() => {
    syncAuth();
});

const syncAuth = () => {
    const user = page.props.auth?.user;
    if (user) {
        authStore.setAuth({
            token: localStorage.getItem('token') ?? '',
            user: user,
            permissions: user.permissions ?? [],
        });
    }
};

// ── Notifications ─────────────────────────────────────────────────────────────
const showNotifications = ref(false);
const notifications = computed(() => page.props.notifications ?? { count: 0, items: [] });

function toggleNotifications() {
    showNotifications.value = !showNotifications.value;
}

// Per-color Tailwind classes (full strings so Tailwind includes them in the build)
const NOTIF_COLORS = {
    amber:   { icon: 'bg-amber-500/15 text-amber-400',   dot: 'bg-amber-400' },
    emerald: { icon: 'bg-emerald-500/15 text-emerald-400', dot: 'bg-emerald-400' },
    red:     { icon: 'bg-red-500/15 text-red-400',       dot: 'bg-red-400' },
    sky:     { icon: 'bg-sky-500/15 text-sky-400',       dot: 'bg-sky-400' },
};
function notifColor(color) {
    return NOTIF_COLORS[color] ?? NOTIF_COLORS['amber'];
}

// Poll for new notifications every 30 s (partial reload — only updates notifications prop)
let pollTimer = null;
onMounted(() => {
    pollTimer = setInterval(() => {
        router.reload({ only: ['notifications'], preserveScroll: true, preserveState: true });
    }, 30_000);
});
onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

// ── Permission helper ──────────────────────────────────────────────────────────
// Permission helper — reads directly from Inertia shared props (always available,
// even before onMounted) so the sidebar renders correctly on first paint.
const can = (permission) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    // Super Admin bypass
    if (user.role_id === 1 || user.role?.role_name === 'Super Admin') return true;
    // Check permissions array shared from the server
    const perms = user.permissions ?? authStore.permissions ?? [];
    return perms.includes(permission);
};
</script>

<template>
  <div class="min-h-screen bg-[#0B1929] text-[#F0EBE1] font-sans flex flex-col md:flex-row">
    
    <!-- Sidebar (Desktop) -->
    <aside :class="sidebarCollapsed ? 'w-0 overflow-hidden' : 'w-64'"
           class="bg-[#112236] border-r border-[#ffffff14] hidden md:flex flex-col h-screen sticky top-0 transition-all duration-300 shrink-0">

      <!-- Logo + Bank Name -->
      <div class="h-16 flex items-center px-5 border-b border-[#ffffff14] shrink-0 gap-3">
        <Link :href="route('staff.dashboard')" class="flex items-center gap-3 min-w-0 flex-1">
            <img src="/storage/images/MAin Logo.png" alt="Gobaad Bank" class="h-9 w-auto object-contain shrink-0" />
            <span class="font-serif text-[#C9A84C] tracking-wide text-sm whitespace-nowrap overflow-hidden text-ellipsis">Gobaad Bank</span>
        </Link>
        <!-- Bell (desktop sidebar) -->
        <button @click="toggleNotifications"
                class="relative shrink-0 text-[#A9B8C6] p-1.5 hover:bg-[rgba(255,255,255,0.06)] rounded-lg transition"
                title="Notifications">
          <i class="ti ti-bell text-base"></i>
          <span v-if="notifications.count > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-0.5 bg-red-500 text-white
                       text-[9px] font-bold rounded-full flex items-center justify-center leading-none">
            {{ notifications.count > 99 ? '99+' : notifications.count }}
          </span>
        </button>
      </div>

      <!-- Current User Info -->
      <div class="px-4 py-3 border-b border-[#ffffff0a] shrink-0 bg-[#0d1d30]">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-[#C9A84C]/20 flex items-center justify-center shrink-0">
            <span class="text-[#C9A84C] text-xs font-bold">
              {{ (currentUser.full_name || currentUser.name || '?').charAt(0).toUpperCase() }}
            </span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-[#F0EBE1] truncate leading-tight">
              {{ currentUser.full_name || currentUser.name || '—' }}
            </p>
            <p class="text-[10px] text-[#C9A84C]/80 truncate leading-tight mt-0.5">
              {{ currentUser.role?.role_name ?? '—' }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- Nav Links -->
      <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto w-full">

        <!-- ── Branch Operations ── -->
        <div v-if="can('ledger.read')" class="text-[10px] uppercase tracking-widest text-[#A9B8C6] opacity-50 mb-3 mt-2 px-2 font-semibold">Branch Operations</div>
        <Link v-if="can('ledger.read')" :href="route('staff.dashboard')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.dashboard') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
          <i class="ti ti-dashboard text-lg"></i>
          <span class="text-sm font-medium">Branch Dashboard</span>
        </Link>

        <!-- ── Teller Operations ── -->
        <template v-if="can('transaction.deposit') || can('transaction.withdraw') || can('transaction.transfer')">
          <div class="text-[10px] uppercase tracking-widest text-[#A9B8C6] opacity-50 mb-3 mt-6 px-2 font-semibold">Teller Operations</div>
          <Link v-if="can('transaction.deposit')" :href="route('staff.teller.deposit')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.teller.deposit') ? 'bg-[rgba(76,175,125,0.1)] text-[#4CAF7D]' : 'text-[#A9B8C6] hover:text-[#4CAF7D] hover:bg-[rgba(76,175,125,0.05)]'">
            <i class="ti ti-arrow-down-circle text-lg"></i>
            <span class="text-sm font-medium">Cash Deposit</span>
          </Link>
          <Link v-if="can('transaction.withdraw')" :href="route('staff.teller.withdraw')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.teller.withdraw') ? 'bg-[rgba(232,168,48,0.1)] text-[#E8A830]' : 'text-[#A9B8C6] hover:text-[#E8A830] hover:bg-[rgba(232,168,48,0.05)]'">
            <i class="ti ti-arrow-up-circle text-lg"></i>
            <span class="text-sm font-medium">Cash Withdrawal</span>
          </Link>
          <Link v-if="can('transaction.transfer')" :href="route('staff.teller.transfer')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.teller.transfer') ? 'bg-[rgba(138,99,210,0.1)] text-[#8A63D2]' : 'text-[#A9B8C6] hover:text-[#8A63D2] hover:bg-[rgba(138,99,210,0.05)]'">
            <i class="ti ti-arrows-right-left text-lg"></i>
            <span class="text-sm font-medium">Inter-Account Transfer</span>
          </Link>

          <!-- Cash Count -->
          <Link v-if="can('transaction.deposit') || can('transaction.withdraw')"
                :href="route('staff.teller.cash-count.index')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.teller.cash-count.*') ? 'bg-[rgba(16,185,129,0.1)] text-[#10B981]' : 'text-[#A9B8C6] hover:text-[#10B981] hover:bg-[rgba(16,185,129,0.05)]'">
            <i class="ti ti-calculator text-lg"></i>
            <span class="text-sm font-medium">Cash Count</span>
          </Link>

          <!-- My Till (teller's personal till view) -->
          <Link v-if="can('transaction.deposit') || can('transaction.withdraw')"
                :href="route('staff.teller.my-till')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.teller.my-till') ? 'bg-[rgba(251,191,36,0.1)] text-[#FBBF24]' : 'text-[#A9B8C6] hover:text-[#FBBF24] hover:bg-[rgba(251,191,36,0.05)]'">
            <i class="ti ti-cash-register text-lg"></i>
            <span class="text-sm font-medium">My Till</span>
          </Link>

          <!-- Cash Allocation (supervisor / vault cashier only) -->
          <Link v-if="can('approvals.read')"
                :href="route('staff.teller.cash-allocation.index')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.teller.cash-allocation.*') ? 'bg-[rgba(99,102,241,0.1)] text-[#6366F1]' : 'text-[#A9B8C6] hover:text-[#6366F1] hover:bg-[rgba(99,102,241,0.05)]'">
            <i class="ti ti-wallet text-lg"></i>
            <span class="text-sm font-medium">Cash Allocation</span>
          </Link>
        </template>

        <!-- ── Customer Care ── -->
        <template v-if="can('customer.read') || can('customer.write') || can('kyc.upload') || can('account.read') || can('account.write')">
          <div class="text-[10px] uppercase tracking-widest text-[#A9B8C6] opacity-50 mb-3 mt-6 px-2 font-semibold">Customer Care</div>

          <!-- Customer Registration -->
          <Link v-if="can('customer.write')" :href="route('staff.customer-care.register')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.customer-care.register') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-user-plus text-lg"></i>
            <span class="text-sm font-medium">New Registration</span>
          </Link>

          <!-- Customer Directory -->
          <Link v-if="can('customer.read')" :href="route('staff.customer-care.customers')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.customer-care.customers') || route().current('staff.customer-care.profile') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-users text-lg"></i>
            <span class="text-sm font-medium">Customer Directory</span>
          </Link>

          <!-- KYC Collection Queue -->
          <Link v-if="can('kyc.upload')" :href="route('staff.customer-care.kyc.list')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.customer-care.kyc.list') || route().current('staff.customer-care.kyc') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-file-upload text-lg"></i>
            <span class="text-sm font-medium">KYC Collection Queue</span>
          </Link>

          <!-- Accounts List -->
          <Link v-if="can('account.read')" :href="route('staff.customer-care.accounts.index')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.customer-care.accounts.index') || route().current('staff.account.transactions') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-building-bank text-lg"></i>
            <span class="text-sm font-medium">Accounts List</span>
          </Link>

          <!-- Account Opening -->
          <Link v-if="can('account.write')" :href="route('staff.customer-care.accounts.create')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.customer-care.accounts.create') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-circle-plus text-lg"></i>
            <span class="text-sm font-medium">Account Opening</span>
          </Link>
        </template>

        <!-- ── Branch Manager Section ── -->
        <template v-if="can('branch.settings')">
          <div class="text-[10px] uppercase tracking-widest text-[#A9B8C6] opacity-50 mt-6 mb-3 px-2 font-semibold">Branch Management</div>

          <Link :href="route('staff.branch.settings')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.branch.settings') ? 'bg-[rgba(201,168,76,0.12)] text-[#C9A84C]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(201,168,76,0.05)]'">
            <i class="ti ti-settings-2 text-lg"></i>
            <span class="text-sm font-medium">Branch Settings</span>
          </Link>

          <Link :href="route('staff.branch.staff.index')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.branch.staff.*') ? 'bg-[rgba(99,102,241,0.12)] text-[#818CF8]' : 'text-[#A9B8C6] hover:text-[#818CF8] hover:bg-[rgba(99,102,241,0.05)]'">
            <i class="ti ti-user-cog text-lg"></i>
            <span class="text-sm font-medium">Staff Management</span>
          </Link>

          <Link :href="route('staff.branch.audit')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.branch.audit') ? 'bg-[rgba(245,158,11,0.12)] text-[#F59E0B]' : 'text-[#A9B8C6] hover:text-[#F59E0B] hover:bg-[rgba(245,158,11,0.05)]'">
            <i class="ti ti-history text-lg"></i>
            <span class="text-sm font-medium">Branch Audit Trail</span>
          </Link>
        </template>

        <!-- ── Operations ── -->
        <div v-if="can('reconciliation.read') || can('approvals.read') || can('compliance.check') || can('system.admin')"
             class="text-[10px] uppercase tracking-widest text-[#A9B8C6] opacity-50 mt-6 mb-3 px-2 font-semibold">Operations</div>

        <Link v-if="can('approvals.read')" :href="route('staff.approvals')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.approvals') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
          <i class="ti ti-check text-lg"></i>
          <span class="text-sm font-medium">Supervisor Queue</span>
        </Link>

        <Link v-if="can('compliance.check')" :href="route('staff.compliance.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.compliance.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
          <i class="ti ti-shield-check text-lg"></i>
          <span class="text-sm font-medium">Compliance & AML</span>
        </Link>

        <Link v-if="can('reconciliation.read')" :href="route('staff.accounting.ledger')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.accounting.ledger') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
          <i class="ti ti-report-money text-lg"></i>
          <span class="text-sm font-medium">General Ledger</span>
        </Link>

        <div v-if="can('system.admin')" class="text-[10px] uppercase tracking-widest text-[#A9B8C6] opacity-50 mt-8 mb-4 px-2 font-semibold">HQ Administration</div>
        
        <template v-if="can('system.admin')">
          <Link :href="route('staff.admin.dashboard')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.dashboard') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-chart-bar text-lg"></i> 
            <span class="text-sm font-medium">Global Analytics</span>
          </Link>
          <Link :href="route('staff.admin.branches.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.branches.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-building-bank text-lg"></i> 
            <span class="text-sm font-medium">Branch Management</span>
          </Link>
          <Link :href="route('staff.admin.roles.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.roles.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-shield-lock text-lg"></i> 
            <span class="text-sm font-medium">Roles & Permissions</span>
          </Link>
          <Link :href="route('staff.admin.staff.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.staff.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-users text-lg"></i> 
            <span class="text-sm font-medium">Staff & RBAC</span>
          </Link>
          <Link :href="route('staff.admin.audit.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.audit.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-history text-lg"></i> 
            <span class="text-sm font-medium">System Audit Trail</span>
          </Link>
          <Link :href="route('staff.admin.customers.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.customers.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-users text-lg"></i>
            <span class="text-sm font-medium">Customer Controls</span>
          </Link>
          <Link :href="route('staff.admin.settings.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.settings.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-settings-2 text-lg"></i>
            <span class="text-sm font-medium">Global Settings</span>
          </Link>
        </template>
      </nav>
      
      <!-- My Profile — visible to all staff -->
      <div class="px-4 pb-1 shrink-0">
        <Link :href="route('staff.profile')"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition w-full"
              :class="route().current('staff.profile') ? 'bg-[rgba(201,168,76,0.12)] text-[#C9A84C]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(201,168,76,0.05)]'">
          <i class="ti ti-user-circle text-lg"></i>
          <span class="text-sm font-medium">My Profile</span>
        </Link>
      </div>

      <!-- Sidebar Collapse Toggle -->
      <div class="px-4 py-2 border-t border-[#ffffff0a] shrink-0">
        <button @click="toggleSidebar"
                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#A9B8C6] hover:text-white hover:bg-[rgba(255,255,255,0.04)] transition text-sm">
          <i class="ti ti-layout-sidebar-left-collapse text-lg"></i>
          <span class="text-xs font-medium">Collapse Sidebar</span>
        </button>
      </div>

      <!-- User Profile Bottom -->
      <div class="p-4 border-t border-[#ffffff14] shrink-0 bg-[#0c1827]">
        <div class="flex items-center justify-between">
            <div class="flex flex-col max-w-[150px]">
              <span class="text-sm font-medium text-[#F0EBE1] truncate">{{ $page.props.auth.user.full_name || $page.props.auth.user.name }}</span>
              <span class="text-[11px] text-[#A9B8C6] truncate">{{ $page.props.auth.user.email }}</span>
            </div>
            <Link :href="route('logout')" method="post" as="button" class="text-[#E2635A] hover:bg-[rgba(226,99,90,0.1)] p-2 rounded-lg transition" title="Logout">
              <i class="ti ti-logout text-lg"></i>
            </Link>
        </div>
      </div>
    </aside>

    <!-- Floating expand button (visible only when sidebar is collapsed) -->
    <Transition name="fade-btn">
      <button v-if="sidebarCollapsed"
              @click="toggleSidebar"
              class="hidden md:flex fixed top-4 left-3 z-40 w-9 h-9 bg-[#112236] border border-[#ffffff18]
                     rounded-xl items-center justify-center text-[#A9B8C6] hover:text-[#C9A84C]
                     hover:border-[#C9A84C]/40 transition-all shadow-lg">
        <i class="ti ti-layout-sidebar-left-expand text-base"></i>
      </button>
    </Transition>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
      <!-- Mobile Header -->
      <div class="md:hidden h-16 flex items-center justify-between px-4 bg-[#112236] border-b border-[#ffffff14] shrink-0">
         <div class="flex items-center gap-3">
            <img src="/storage/images/MAin Logo.png" alt="Gobaad Bank" class="h-8 w-auto object-contain shrink-0" />
            <span class="font-serif text-[#C9A84C] tracking-wide text-sm whitespace-nowrap">Gobaad Bank</span>
         </div>
         <div class="flex items-center gap-1">
           <!-- Bell (mobile) -->
           <button @click="toggleNotifications"
                   class="relative text-[#A9B8C6] p-2 hover:bg-[rgba(255,255,255,0.05)] rounded-lg transition"
                   title="Notifications">
             <i class="ti ti-bell text-xl"></i>
             <span v-if="notifications.count > 0"
                   class="absolute top-1 right-1 min-w-[16px] h-4 px-0.5 bg-red-500 text-white
                          text-[9px] font-bold rounded-full flex items-center justify-center leading-none">
               {{ notifications.count > 99 ? '99+' : notifications.count }}
             </span>
           </button>
           <!-- Hamburger -->
           <button @click="showingMobileNav = !showingMobileNav" class="text-[#A9B8C6] p-2 hover:bg-[rgba(255,255,255,0.05)] rounded">
             <i class="ti ti-menu-2 text-xl" v-if="!showingMobileNav"></i>
             <i class="ti ti-x text-xl" v-else></i>
           </button>
         </div>
      </div>
    
      <!-- Mobile Nav Overlay -->
      <div v-if="showingMobileNav" class="md:hidden bg-[#162d47] border-b border-[#ffffff14] w-full z-50 shrink-0">
          <div class="px-4 py-4 space-y-1">
            <!-- Branch Operations -->
            <div v-if="authStore.can('ledger.read')" class="text-[9px] uppercase tracking-widest text-[#6B7E8E] px-3 pt-2 pb-1 font-semibold">Branch Operations</div>
            <Link v-if="authStore.can('ledger.read')" :href="route('staff.dashboard')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.dashboard')}">
              <i class="ti ti-dashboard mr-2"></i>Branch Dashboard
            </Link>
            <!-- Teller Operations (mobile) -->
            <template v-if="authStore.can('transaction.deposit') || authStore.can('transaction.withdraw') || authStore.can('transaction.transfer')">
              <div class="text-[9px] uppercase tracking-widest text-[#6B7E8E] px-3 pt-4 pb-1 font-semibold">Teller Operations</div>
              <Link v-if="authStore.can('transaction.deposit')" :href="route('staff.teller.deposit')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#4CAF7D]" :class="{'text-[#4CAF7D] bg-[rgba(76,175,125,0.08)]': route().current('staff.teller.deposit')}">
                <i class="ti ti-arrow-down-circle mr-2"></i>Cash Deposit
              </Link>
              <Link v-if="authStore.can('transaction.withdraw')" :href="route('staff.teller.withdraw')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#E8A830]" :class="{'text-[#E8A830] bg-[rgba(232,168,48,0.08)]': route().current('staff.teller.withdraw')}">
                <i class="ti ti-arrow-up-circle mr-2"></i>Cash Withdrawal
              </Link>
              <Link v-if="authStore.can('transaction.transfer')" :href="route('staff.teller.transfer')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#8A63D2]" :class="{'text-[#8A63D2] bg-[rgba(138,99,210,0.08)]': route().current('staff.teller.transfer')}">
                <i class="ti ti-arrows-right-left mr-2"></i>Inter-Account Transfer
              </Link>
              <Link v-if="authStore.can('transaction.deposit') || authStore.can('transaction.withdraw')" :href="route('staff.teller.cash-count.index')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#10B981]" :class="{'text-[#10B981] bg-[rgba(16,185,129,0.08)]': route().current('staff.teller.cash-count.*')}">
                <i class="ti ti-calculator mr-2"></i>Cash Count
              </Link>
              <Link v-if="authStore.can('transaction.deposit') || authStore.can('transaction.withdraw')" :href="route('staff.teller.my-till')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#FBBF24]" :class="{'text-[#FBBF24] bg-[rgba(251,191,36,0.08)]': route().current('staff.teller.my-till')}">
                <i class="ti ti-cash-register mr-2"></i>My Till
              </Link>
              <Link v-if="authStore.can('approvals.read')" :href="route('staff.teller.cash-allocation.index')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#6366F1]" :class="{'text-[#6366F1] bg-[rgba(99,102,241,0.08)]': route().current('staff.teller.cash-allocation.*')}">
                <i class="ti ti-wallet mr-2"></i>Cash Allocation
              </Link>
            </template>

            <!-- Customer Care -->
            <template v-if="authStore.can('customer.read') || authStore.can('customer.write') || authStore.can('kyc.upload') || authStore.can('account.read') || authStore.can('account.write')">
              <div class="text-[9px] uppercase tracking-widest text-[#6B7E8E] px-3 pt-4 pb-1 font-semibold">Customer Care</div>
              <Link v-if="authStore.can('customer.write')" :href="route('staff.customer-care.register')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.customer-care.register')}">
                <i class="ti ti-user-plus mr-2"></i>New Registration
              </Link>
              <Link v-if="authStore.can('customer.read')" :href="route('staff.customer-care.customers')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.customer-care.customers')}">
                <i class="ti ti-users mr-2"></i>Customer Directory
              </Link>
              <Link v-if="authStore.can('kyc.upload')" :href="route('staff.customer-care.kyc.list')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.customer-care.kyc.list')}">
                <i class="ti ti-file-upload mr-2"></i>KYC Collection Queue
              </Link>
              <Link v-if="authStore.can('account.read')" :href="route('staff.customer-care.accounts.index')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.customer-care.accounts.index')}">
                <i class="ti ti-building-bank mr-2"></i>Accounts List
              </Link>
              <Link v-if="authStore.can('account.write')" :href="route('staff.customer-care.accounts.create')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.customer-care.accounts.create')}">
                <i class="ti ti-circle-plus mr-2"></i>Account Opening
              </Link>
            </template>
            <!-- Branch Manager (mobile) -->
            <template v-if="authStore.can('branch.settings')">
              <div class="text-[9px] uppercase tracking-widest text-[#6B7E8E] px-3 pt-4 pb-1 font-semibold">Branch Management</div>
              <Link :href="route('staff.branch.settings')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#C9A84C] bg-[rgba(201,168,76,0.08)]': route().current('staff.branch.settings')}">
                <i class="ti ti-settings-2 mr-2"></i>Branch Settings
              </Link>
              <Link :href="route('staff.branch.staff.index')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#818CF8]" :class="{'text-[#818CF8] bg-[rgba(99,102,241,0.08)]': route().current('staff.branch.staff.*')}">
                <i class="ti ti-user-cog mr-2"></i>Staff Management
              </Link>
              <Link :href="route('staff.branch.audit')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#F59E0B]" :class="{'text-[#F59E0B] bg-[rgba(245,158,11,0.08)]': route().current('staff.branch.audit')}">
                <i class="ti ti-history mr-2"></i>Branch Audit Trail
              </Link>
            </template>
            <!-- Operations -->
            <div v-if="authStore.can('approvals.read') || authStore.can('compliance.check') || authStore.can('reconciliation.read') || authStore.can('system.admin')" class="text-[9px] uppercase tracking-widest text-[#6B7E8E] px-3 pt-4 pb-1 font-semibold">Operations</div>
            <Link v-if="authStore.can('approvals.read')" :href="route('staff.approvals')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.approvals')}">
              <i class="ti ti-check mr-2"></i>Supervisor Queue
            </Link>
            <Link v-if="authStore.can('compliance.check')" :href="route('staff.compliance.index')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.compliance.index')}">
              <i class="ti ti-shield-check mr-2"></i>Compliance & AML
            </Link>
            <Link v-if="authStore.can('reconciliation.read')" :href="route('staff.accounting.ledger')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.accounting.ledger')}">
              <i class="ti ti-report-money mr-2"></i>General Ledger
            </Link>
            <Link v-if="authStore.can('system.admin')" :href="route('staff.admin.dashboard')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#C9A84C]" :class="{'text-[#F0EBE1] bg-[rgba(255,255,255,0.05)]': route().current('staff.admin.dashboard')}">
              <i class="ti ti-chart-bar mr-2"></i>Global Analytics
            </Link>
          </div>
          <div class="px-4 py-4 border-t border-[#ffffff14]">
             <div class="mb-3">
              <div class="text-sm font-medium text-[#F0EBE1]">{{ $page.props.auth.user.full_name || $page.props.auth.user.name }}</div>
              <div class="text-[11px] text-[#A9B8C6]">{{ $page.props.auth.user.email }}</div>
             </div>
             <Link :href="route('logout')" method="post" as="button" class="flex items-center gap-2 text-[#E2635A] py-2 font-medium">
                <i class="ti ti-logout"></i> Log Out
             </Link>
          </div>
      </div>

      <!-- Force Password Change Banner -->
      <div v-if="mustChangePassword"
           class="shrink-0 bg-amber-500/10 border-b border-amber-500/30 px-4 py-2.5 flex items-center gap-3">
        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-amber-500/20 shrink-0">
          <i class="ti ti-lock-exclamation text-amber-400 text-base"></i>
        </span>
        <p class="flex-1 text-sm text-amber-200 leading-snug">
          <span class="font-semibold text-amber-300">Security alert:</span>
          Your password was set by an administrator. Please change it now to secure your account.
        </p>
        <Link :href="route('staff.profile') + '?tab=security'"
              class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-[#0B1929] text-xs font-bold transition whitespace-nowrap">
          Change Password
        </Link>
      </div>

      <!-- Optional Page Header Slot -->
      <header class="bg-[#112236] border-b border-[#ffffff08] shrink-0" v-if="$slots.header">
          <div class="px-8 py-5">
              <slot name="header" />
          </div>
      </header>

      <!-- Scrollable Inner Content -->
      <main class="flex-1 overflow-y-auto">
          <slot />
      </main>
    </div>
    
  </div>

  <!-- ── Notifications dropdown (portal — appears above everything) ── -->
  <Teleport to="body">
    <!-- Backdrop: clicking outside closes the panel -->
    <div v-if="showNotifications"
         class="fixed inset-0 z-[9990]"
         @click="showNotifications = false">
    </div>

    <Transition name="notif-drop">
      <div v-if="showNotifications"
           class="fixed z-[9995] flex flex-col rounded-xl shadow-2xl overflow-hidden
                  bg-[#112236] border border-[#ffffff14]
                  top-[64px] right-2 w-80 max-h-[calc(100vh-80px)]
                  md:top-[64px] md:left-2 md:right-auto md:w-[288px]">

        <!-- Panel header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-[#ffffff10] shrink-0">
          <div class="flex items-center gap-2">
            <i class="ti ti-bell text-[#C9A84C] text-base"></i>
            <span class="text-sm font-semibold text-[#F0EBE1]">Notifications</span>
          </div>
          <span v-if="notifications.count > 0"
                class="text-[10px] font-bold px-2 py-0.5 bg-red-500/20 text-red-400
                       rounded-full border border-red-500/30">
            {{ notifications.count }}
          </span>
        </div>

        <!-- Items list -->
        <div class="overflow-y-auto flex-1">
          <!-- Empty state -->
          <div v-if="notifications.items.length === 0"
               class="flex flex-col items-center justify-center py-10 px-4 text-center">
            <div class="w-12 h-12 rounded-full bg-[#ffffff08] flex items-center justify-center mb-3">
              <i class="ti ti-bell-off text-[#A9B8C6] text-xl"></i>
            </div>
            <p class="text-sm text-[#A9B8C6]">All clear — no new notifications</p>
          </div>

          <!-- Notification items -->
          <template v-else>
            <Link v-for="item in notifications.items"
                  :key="item.id"
                  :href="item.link"
                  @click="showNotifications = false"
                  class="flex items-start gap-3 px-4 py-3
                         hover:bg-[rgba(255,255,255,0.04)] transition
                         border-b border-[#ffffff08] last:border-0">
              <!-- Coloured icon -->
              <div class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center mt-0.5"
                   :class="notifColor(item.color).icon">
                <i :class="['ti', item.icon, 'text-sm']"></i>
              </div>

              <!-- Text -->
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-[#F0EBE1] leading-tight">{{ item.title }}</p>
                <p class="text-[11px] text-[#A9B8C6] mt-0.5 leading-snug line-clamp-2">{{ item.body }}</p>
                <p class="text-[10px] text-[#6B7E8E] mt-1">{{ item.time }}</p>
              </div>

              <!-- Accent dot -->
              <div class="shrink-0 w-2 h-2 rounded-full mt-2"
                   :class="notifColor(item.color).dot">
              </div>
            </Link>
          </template>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css');

.fade-btn-enter-active, .fade-btn-leave-active { transition: opacity .2s, transform .2s; }
.fade-btn-enter-from, .fade-btn-leave-to { opacity: 0; transform: translateX(-6px); }

.notif-drop-enter-active, .notif-drop-leave-active { transition: opacity .18s ease, transform .18s ease; }
.notif-drop-enter-from, .notif-drop-leave-to { opacity: 0; transform: translateY(-8px) scale(.97); }

/* Custom scrollbar for webkit */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
::-webkit-scrollbar-track {
  background: rgba(11, 25, 41, 0.5); 
}
::-webkit-scrollbar-thumb {
  background: rgba(169, 184, 198, 0.2); 
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: rgba(169, 184, 198, 0.4); 
}
</style>
