<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';
import { useTheme } from '@/composables/useTheme';

const { isDark, toggleTheme } = useTheme();

// ── Notification dropdown ─────────────────────────────────────────────────────
const showNotifDropdown  = ref(false)
const notifBellDesktop   = ref(null)
const notifBellMobile    = ref(null)

function toggleNotifDropdown() {
    showNotifDropdown.value = !showNotifDropdown.value
}

function closeNotifDropdown(e) {
    const insideDesktop = notifBellDesktop.value?.contains(e.target)
    const insideMobile  = notifBellMobile.value?.contains(e.target)
    if (!insideDesktop && !insideMobile) {
        showNotifDropdown.value = false
    }
}

const notifColorMap = {
    amber:   { bg: 'bg-amber-500/10',   text: 'text-amber-400'   },
    red:     { bg: 'bg-red-500/10',     text: 'text-red-400'     },
    emerald: { bg: 'bg-emerald-500/10', text: 'text-emerald-400' },
    blue:    { bg: 'bg-blue-500/10',    text: 'text-blue-400'    },
    purple:  { bg: 'bg-purple-500/10',  text: 'text-purple-400'  },
    slate:   { bg: 'bg-slate-500/10',   text: 'text-slate-400'   },
}
function notifBg(color)   { return (notifColorMap[color] ?? notifColorMap.slate).bg }
function notifText(color) { return (notifColorMap[color] ?? notifColorMap.slate).text }

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

// Sync Pinia with Inertia on every visit/mount + start idle tracker
onMounted(() => {
    syncAuth()
    IDLE_EVENTS.forEach(e => window.addEventListener(e, resetStaffIdle, { passive: true }))
    resetStaffIdle()
    document.addEventListener('click', closeNotifDropdown, true)
})

onBeforeUnmount(() => {
    IDLE_EVENTS.forEach(e => window.removeEventListener(e, resetStaffIdle))
    clearTimeout(staffIdleTimer)
    clearInterval(staffCountdownTimer)
    document.removeEventListener('click', closeNotifDropdown, true)
})

// ── Staff Session Timeout (8 min idle → warning, 10 min → logout) ───────────
const csrf               = computed(() => document.querySelector('meta[name="csrf-token"]')?.content ?? '')
const showStaffTimeout   = ref(false)
const staffCountdown     = ref(120)
let staffIdleTimer = null, staffCountdownTimer = null

const staffCountdownDisplay = computed(() => {
  const m = Math.floor(staffCountdown.value / 60)
  const s = String(staffCountdown.value % 60).padStart(2, '0')
  return m > 0 ? `${m}:${s}` : `${staffCountdown.value}s`
})

function resetStaffIdle() {
  if (showStaffTimeout.value) return
  clearTimeout(staffIdleTimer)
  staffIdleTimer = setTimeout(() => {
    showStaffTimeout.value = true
    staffCountdown.value   = 120
    staffCountdownTimer = setInterval(() => {
      staffCountdown.value--
      if (staffCountdown.value <= 0) {
        clearInterval(staffCountdownTimer)
        document.querySelector('form[action*="logout"]')?.submit()
      }
    }, 1000)
  }, 8 * 60 * 1000)
}

function extendStaffSession() {
  showStaffTimeout.value = false
  clearInterval(staffCountdownTimer)
  resetStaffIdle()
}

const IDLE_EVENTS = ['mousemove', 'keydown', 'click', 'touchstart', 'scroll']

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
const notifications = computed(() => page.props.notifications ?? { count: 0, items: [] });

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
            <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-9 w-auto object-contain shrink-0" />
            <span class="font-serif text-[#C9A84C] tracking-wide text-sm whitespace-nowrap overflow-hidden text-ellipsis">Gobaad Bank</span>
        </Link>
        <!-- Theme toggle (desktop sidebar) -->
        <button @click="toggleTheme"
                class="theme-toggle-btn shrink-0 text-[#A9B8C6] hover:text-[#C9A84C] p-1.5 rounded-lg hover:bg-[rgba(201,168,76,0.08)] transition"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
          <i :class="isDark ? 'ti ti-sun text-base' : 'ti ti-moon text-base'"></i>
        </button>

        <!-- Bell (desktop sidebar) — dropdown -->
        <div ref="notifBellDesktop" class="relative shrink-0">
          <button @click.stop="toggleNotifDropdown"
                  class="relative text-[#A9B8C6] hover:text-[#C9A84C] p-1.5 rounded-lg hover:bg-[rgba(201,168,76,0.08)] transition"
                  title="Notifications">
            <i class="ti ti-bell text-base"></i>
            <span v-if="notifications.count > 0"
                  class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-0.5 bg-red-500 text-white
                         text-[9px] font-bold rounded-full flex items-center justify-center leading-none">
              {{ notifications.count > 99 ? '99+' : notifications.count }}
            </span>
          </button>

          <!-- Dropdown panel -->
          <Transition name="notif-drop">
            <div v-if="showNotifDropdown"
                 class="absolute top-full right-0 mt-2 w-80 bg-[#112236] border border-[#ffffff14]
                        rounded-2xl shadow-2xl z-[200] overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between px-4 py-3 border-b border-[#ffffff0a]">
                <span class="text-xs font-bold uppercase tracking-widest text-[#A9B8C6]">Notifications</span>
                <span v-if="notifications.count > 0"
                      class="text-[10px] bg-red-500/20 text-red-400 border border-red-500/20 px-2 py-0.5 rounded-full font-bold">
                  {{ notifications.count }} new
                </span>
              </div>

              <!-- Items -->
              <div class="max-h-80 overflow-y-auto divide-y divide-[#ffffff08]">
                <template v-if="notifications.items?.length">
                  <Link v-for="n in notifications.items" :key="n.id"
                        :href="n.link ?? '#'"
                        @click="showNotifDropdown = false"
                        class="flex items-start gap-3 px-4 py-3 hover:bg-[rgba(255,255,255,0.03)] transition cursor-pointer block">
                    <div :class="[notifBg(n.color), 'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5']">
                      <i :class="['ti', n.icon ?? 'ti-bell', 'text-sm', notifText(n.color)]"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-semibold text-[#F0EBE1] leading-tight">{{ n.title }}</p>
                      <p class="text-[11px] text-[#A9B8C6] mt-0.5 leading-snug line-clamp-2">{{ n.body }}</p>
                      <p class="text-[10px] text-[#6B7E8E] mt-1">{{ n.time }}</p>
                    </div>
                  </Link>
                </template>
                <div v-else class="px-4 py-8 text-center text-[#6B7E8E]">
                  <i class="ti ti-bell-off text-2xl block mb-2"></i>
                  <p class="text-xs">No new notifications</p>
                </div>
              </div>
            </div>
          </Transition>
        </div>
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

          <!-- Transaction History -->
          <Link v-if="can('transaction.deposit') || can('transaction.withdraw') || can('transaction.transfer')"
                :href="route('staff.teller.history')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.teller.history') ? 'bg-[rgba(99,179,237,0.1)] text-[#63B3ED]' : 'text-[#A9B8C6] hover:text-[#63B3ED] hover:bg-[rgba(99,179,237,0.05)]'">
            <i class="ti ti-history text-lg"></i>
            <span class="text-sm font-medium">Transaction History</span>
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

          <Link :href="route('staff.branch.dashboard')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.branch.dashboard') ? 'bg-[rgba(201,168,76,0.12)] text-[#C9A84C]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(201,168,76,0.05)]'">
            <i class="ti ti-layout-dashboard text-lg"></i>
            <span class="text-sm font-medium">Branch Dashboard</span>
          </Link>

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

          <Link :href="route('staff.branch.clearing.index')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.branch.clearing.*') ? 'bg-[rgba(56,189,248,0.12)] text-[#38BDF8]' : 'text-[#A9B8C6] hover:text-[#38BDF8] hover:bg-[rgba(56,189,248,0.05)]'">
            <i class="ti ti-arrows-exchange text-lg"></i>
            <span class="text-sm font-medium">Inter-Branch Clearing</span>
          </Link>

          <Link :href="route('staff.branch.reports')"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition"
                :class="route().current('staff.branch.reports') ? 'bg-[rgba(52,211,153,0.12)] text-[#34D399]' : 'text-[#A9B8C6] hover:text-[#34D399] hover:bg-[rgba(52,211,153,0.05)]'">
            <i class="ti ti-chart-bar text-lg"></i>
            <span class="text-sm font-medium">Branch Reports</span>
          </Link>
        </template>

        <!-- ── Operations ── -->
        <div v-if="can('reconciliation.read') || can('approvals.read') || can('compliance.check') || can('system.admin')"
             class="text-[10px] uppercase tracking-widest text-[#A9B8C6] opacity-50 mt-6 mb-3 px-2 font-semibold">Operations</div>

        <Link v-if="can('approvals.read')" :href="route('staff.approvals')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.approvals') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
          <i class="ti ti-check text-lg"></i>
          <span class="text-sm font-medium">Supervisor Queue</span>
        </Link>

        <!-- Compliance sub-section -->
        <template v-if="can('compliance.check')">
          <Link :href="route('staff.compliance.dashboard')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.compliance.dashboard') ? 'bg-[rgba(226,99,90,0.1)] text-[#E2635A]' : 'text-[#A9B8C6] hover:text-[#E2635A] hover:bg-[rgba(226,99,90,0.04)]'">
            <i class="ti ti-shield-check text-lg"></i>
            <span class="text-sm font-medium">Compliance Hub</span>
          </Link>
          <Link :href="route('staff.compliance.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition pl-8" :class="route().current('staff.compliance.index') ? 'bg-[rgba(226,99,90,0.08)] text-[#E2635A]' : 'text-[#A9B8C6] hover:text-[#E2635A] hover:bg-[rgba(226,99,90,0.04)]'">
            <i class="ti ti-file-search text-base"></i>
            <span class="text-sm">KYC Queue</span>
          </Link>
          <Link :href="route('staff.compliance.customers')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition pl-8" :class="route().current('staff.compliance.customers') || route().current('staff.compliance.customer-detail') ? 'bg-[rgba(226,99,90,0.08)] text-[#E2635A]' : 'text-[#A9B8C6] hover:text-[#E2635A] hover:bg-[rgba(226,99,90,0.04)]'">
            <i class="ti ti-users text-base"></i>
            <span class="text-sm">Customer Review</span>
          </Link>
          <Link v-if="can('aml.flag')" :href="route('staff.compliance.transactions')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition pl-8" :class="route().current('staff.compliance.transactions') ? 'bg-[rgba(226,99,90,0.08)] text-[#E2635A]' : 'text-[#A9B8C6] hover:text-[#E2635A] hover:bg-[rgba(226,99,90,0.04)]'">
            <i class="ti ti-activity text-base"></i>
            <span class="text-sm">Transaction Monitor</span>
          </Link>
          <Link v-if="can('compliance.report')" :href="route('staff.compliance.reports')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition pl-8" :class="route().current('staff.compliance.reports') ? 'bg-[rgba(226,99,90,0.08)] text-[#E2635A]' : 'text-[#A9B8C6] hover:text-[#E2635A] hover:bg-[rgba(226,99,90,0.04)]'">
            <i class="ti ti-report-analytics text-base"></i>
            <span class="text-sm">SAR Reports</span>
          </Link>
        </template>

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
          <Link :href="route('staff.admin.portal-access.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition" :class="route().current('staff.admin.portal-access.index') ? 'bg-[rgba(255,255,255,0.06)] text-[#F0EBE1]' : 'text-[#A9B8C6] hover:text-[#C9A84C] hover:bg-[rgba(255,255,255,0.02)]'">
            <i class="ti ti-key text-lg"></i>
            <span class="text-sm font-medium">Portal Access</span>
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
            <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-8 w-auto object-contain shrink-0" />
            <span class="font-serif text-[#C9A84C] tracking-wide text-sm whitespace-nowrap">Gobaad Bank</span>
         </div>
         <div class="flex items-center gap-1">
           <!-- Theme toggle (mobile) -->
           <button @click="toggleTheme"
                   class="theme-toggle-btn text-[#A9B8C6] hover:text-[#C9A84C] p-2 rounded-lg transition">
             <i :class="isDark ? 'ti ti-sun text-xl' : 'ti ti-moon text-xl'"></i>
           </button>
           <!-- Bell (mobile) — shared dropdown -->
           <div ref="notifBellMobile" class="relative">
             <button @click.stop="toggleNotifDropdown"
                     class="relative text-[#A9B8C6] hover:text-[#C9A84C] p-2 rounded-lg transition"
                     title="Notifications">
               <i class="ti ti-bell text-xl"></i>
               <span v-if="notifications.count > 0"
                     class="absolute top-1 right-1 min-w-[16px] h-4 px-0.5 bg-red-500 text-white
                            text-[9px] font-bold rounded-full flex items-center justify-center leading-none">
                 {{ notifications.count > 99 ? '99+' : notifications.count }}
               </span>
             </button>
             <!-- Mobile dropdown -->
             <Transition name="notif-drop">
               <div v-if="showNotifDropdown"
                    class="absolute top-full right-0 mt-2 w-72 bg-[#112236] border border-[#ffffff14]
                           rounded-2xl shadow-2xl z-[200] overflow-hidden">
                 <div class="flex items-center justify-between px-4 py-3 border-b border-[#ffffff0a]">
                   <span class="text-xs font-bold uppercase tracking-widest text-[#A9B8C6]">Notifications</span>
                   <span v-if="notifications.count > 0"
                         class="text-[10px] bg-red-500/20 text-red-400 border border-red-500/20 px-2 py-0.5 rounded-full font-bold">
                     {{ notifications.count }} new
                   </span>
                 </div>
                 <div class="max-h-72 overflow-y-auto divide-y divide-[#ffffff08]">
                   <template v-if="notifications.items?.length">
                     <Link v-for="n in notifications.items" :key="n.id"
                           :href="n.link ?? '#'"
                           @click="showNotifDropdown = false"
                           class="flex items-start gap-3 px-4 py-3 hover:bg-[rgba(255,255,255,0.03)] transition block">
                       <div :class="[notifBg(n.color), 'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5']">
                         <i :class="['ti', n.icon ?? 'ti-bell', 'text-sm', notifText(n.color)]"></i>
                       </div>
                       <div class="min-w-0 flex-1">
                         <p class="text-xs font-semibold text-[#F0EBE1] leading-tight">{{ n.title }}</p>
                         <p class="text-[11px] text-[#A9B8C6] mt-0.5 leading-snug line-clamp-2">{{ n.body }}</p>
                         <p class="text-[10px] text-[#6B7E8E] mt-1">{{ n.time }}</p>
                       </div>
                     </Link>
                   </template>
                   <div v-else class="px-4 py-8 text-center text-[#6B7E8E]">
                     <i class="ti ti-bell-off text-2xl block mb-2"></i>
                     <p class="text-xs">No new notifications</p>
                   </div>
                 </div>
               </div>
             </Transition>
           </div>
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
              <Link :href="route('staff.teller.history')" class="block text-[#A9B8C6] py-2 px-3 rounded hover:text-[#63B3ED]" :class="{'text-[#63B3ED] bg-[rgba(99,179,237,0.08)]': route().current('staff.teller.history')}">
                <i class="ti ti-history mr-2"></i>Transaction History
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

  <!-- ── Staff Session Timeout Modal ── -->
  <Transition name="fade-btn">
    <div v-if="showStaffTimeout" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm px-4">
      <div class="bg-[#112236] border border-[#ffffff14] rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="w-14 h-14 rounded-full bg-amber-500/10 flex items-center justify-center mx-auto mb-4">
          <i class="ti ti-clock-exclamation text-amber-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-[#F0EBE1] mb-2">Session Expiring</h3>
        <p class="text-sm text-[#A9B8C6] mb-1">Your session will expire in</p>
        <p class="text-3xl font-bold text-red-400 mb-5">{{ staffCountdownDisplay }}</p>
        <div class="flex gap-3">
          <button @click="extendStaffSession"
            class="flex-1 bg-[#C9A84C] hover:bg-[#b8973d] text-[#0B1929] font-bold py-2.5 rounded-xl text-sm transition-colors">
            Stay Logged In
          </button>
          <form :action="route('logout')" method="POST" class="flex-1">
            <input type="hidden" name="_token" :value="csrf" />
            <button type="submit" class="w-full border border-[#ffffff14] text-[#A9B8C6] hover:text-[#F0EBE1] hover:bg-[rgba(255,255,255,0.05)] font-semibold py-2.5 rounded-xl text-sm transition-colors">
              Log Out
            </button>
          </form>
        </div>
      </div>
    </div>
  </Transition>

</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css');

.fade-btn-enter-active, .fade-btn-leave-active { transition: opacity .2s, transform .2s; }
.fade-btn-enter-from, .fade-btn-leave-to { opacity: 0; transform: translateX(-6px); }

.notif-drop-enter-active, .notif-drop-leave-active { transition: opacity .15s, transform .15s; }
.notif-drop-enter-from, .notif-drop-leave-to { opacity: 0; transform: translateY(-6px) scale(0.97); }

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
