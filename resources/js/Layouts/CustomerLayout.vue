<template>
  <div class="min-h-screen bg-slate-100 flex">

    <!-- ═══════════ Sidebar ═══════════ -->
    <aside
      class="fixed inset-y-0 left-0 z-40 flex flex-col bg-[#0B1929] transition-all duration-300 shadow-2xl"
      :class="sidebarOpen ? 'w-64' : 'w-16'"
    >
      <!-- Logo / Brand -->
      <div class="flex items-center h-16 px-4 border-b border-white/10 shrink-0 gap-3">
        <img src="/images/MAin Logo.png" alt="Gobaad Bank"
             class="h-9 w-auto object-contain shrink-0" />
        <Transition name="fade-text">
          <div v-if="sidebarOpen" class="overflow-hidden min-w-0">
            <p class="text-[#C9A84C] font-bold text-sm leading-none whitespace-nowrap font-serif tracking-wide">Gobaad Bank</p>
            <p class="text-[#7a9ab5] text-[10px] uppercase tracking-widest mt-0.5 whitespace-nowrap">Customer Portal</p>
          </div>
        </Transition>
      </div>

      <!-- Customer info pill -->
      <div class="px-3 py-4 border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-[#C9A84C]/20 flex items-center justify-center text-[#C9A84C] text-xs font-bold shrink-0">
            {{ initials }}
          </div>
          <Transition name="fade-text">
            <div v-if="sidebarOpen" class="overflow-hidden min-w-0">
              <p class="text-white text-xs font-semibold truncate leading-tight">{{ auth?.customer?.full_name }}</p>
              <p class="text-[#7a9ab5] text-[10px] truncate mt-0.5">{{ auth?.customer?.phone }}</p>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Nav links -->
      <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        <Link
          v-for="item in navLinks"
          :key="item.route"
          :href="route(item.route)"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group relative"
          :class="route().current(item.route)
            ? 'bg-[#C9A84C]/15 text-[#C9A84C]'
            : 'text-slate-400 hover:bg-white/5 hover:text-white'"
        >
          <i :class="item.icon + ' text-lg shrink-0'"></i>
          <Transition name="fade-text">
            <span v-if="sidebarOpen" class="whitespace-nowrap">{{ item.label }}</span>
          </Transition>
          <!-- Notification badge -->
          <Transition name="fade-text">
            <span
              v-if="sidebarOpen && item.route === 'customer.notifications' && auth?.customer?.unread_count"
              class="ml-auto inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold bg-red-500 text-white rounded-full"
            >
              {{ auth.customer.unread_count > 9 ? '9+' : auth.customer.unread_count }}
            </span>
          </Transition>
          <!-- Collapsed badge dot -->
          <span
            v-if="!sidebarOpen && item.route === 'customer.notifications' && auth?.customer?.unread_count"
            class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"
          ></span>
          <!-- Tooltip when collapsed -->
          <div
            v-if="!sidebarOpen"
            class="absolute left-full ml-3 px-2.5 py-1.5 bg-slate-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity z-50 shadow-lg"
          >
            {{ item.label }}
          </div>
        </Link>
      </nav>

      <!-- Bottom: toggle + sign out -->
      <div class="px-2 py-3 border-t border-white/10 space-y-1 shrink-0">
        <!-- Toggle collapse -->
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white transition-all text-sm group relative"
        >
          <i class="text-lg shrink-0 transition-transform duration-300"
             :class="sidebarOpen ? 'ti ti-layout-sidebar-left-collapse' : 'ti ti-layout-sidebar-left-expand'"></i>
          <Transition name="fade-text">
            <span v-if="sidebarOpen" class="whitespace-nowrap">Collapse</span>
          </Transition>
          <div v-if="!sidebarOpen"
               class="absolute left-full ml-3 px-2.5 py-1.5 bg-slate-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity z-50 shadow-lg">
            Expand sidebar
          </div>
        </button>

        <!-- Sign out -->
        <button
          @click="logout"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-red-400/70 hover:bg-red-900/20 hover:text-red-400 transition-all text-sm group relative"
        >
          <i class="ti ti-logout text-lg shrink-0"></i>
          <Transition name="fade-text">
            <span v-if="sidebarOpen" class="whitespace-nowrap">Sign Out</span>
          </Transition>
          <div v-if="!sidebarOpen"
               class="absolute left-full ml-3 px-2.5 py-1.5 bg-slate-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity z-50 shadow-lg">
            Sign Out
          </div>
        </button>
      </div>
    </aside>

    <!-- ═══════════ Main content area ═══════════ -->
    <div
      class="flex-1 flex flex-col min-h-screen transition-all duration-300"
      :class="sidebarOpen ? 'ml-64' : 'ml-16'"
    >

      <!-- Top bar -->
      <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-3 sm:px-6 sticky top-0 z-30 shadow-sm">
        <!-- Page title -->
        <div class="flex items-center gap-2 min-w-0">
          <h1 class="text-sm sm:text-base font-semibold text-slate-800 truncate">{{ pageTitle }}</h1>
        </div>

        <!-- Right side: theme toggle + notification bell + user badge -->
        <div class="flex items-center gap-1 sm:gap-3 flex-shrink-0">
          <!-- Theme toggle -->
          <button @click="toggleTheme"
                  class="theme-toggle-btn p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                  :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
            <i :class="isDark ? 'ti ti-sun text-xl' : 'ti ti-moon text-xl'"></i>
          </button>
          <!-- Notification bell -->
          <Link :href="route('customer.notifications')" class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
            <i class="ti ti-bell text-xl"></i>
            <span v-if="auth?.customer?.unread_count"
                  class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
          </Link>

          <!-- User badge -->
          <div class="flex items-center gap-2 pl-2 sm:pl-3 border-l border-slate-200">
            <div class="w-8 h-8 rounded-full bg-[#0B1929] flex items-center justify-center text-[#C9A84C] text-xs font-bold flex-shrink-0">
              {{ initials }}
            </div>
            <span class="text-sm font-medium text-slate-700 hidden sm:block max-w-[140px] truncate">
              {{ auth?.customer?.full_name }}
            </span>
          </div>
        </div>
      </header>

      <!-- Flash messages -->
      <div v-if="flash.success || flash.error || flash.info" class="px-3 sm:px-6 pt-4 space-y-2">
        <div v-if="flash.success"
             class="flex items-start gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
          <i class="ti ti-circle-check text-lg text-emerald-600 shrink-0 mt-0.5"></i>
          <span>{{ flash.success }}</span>
        </div>
        <div v-if="flash.error"
             class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
          <i class="ti ti-alert-circle text-lg text-red-600 shrink-0 mt-0.5"></i>
          <span>{{ flash.error }}</span>
        </div>
        <div v-if="flash.info"
             class="flex items-start gap-3 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl text-sm">
          <i class="ti ti-info-circle text-lg text-blue-600 shrink-0 mt-0.5"></i>
          <span>{{ flash.info }}</span>
        </div>
      </div>

      <!-- Page content -->
      <main class="flex-1 p-3 sm:p-6">
        <slot />
      </main>

      <!-- Footer -->
      <footer class="bg-white border-t border-slate-200 px-4 sm:px-6 py-3 flex flex-col sm:flex-row items-center justify-between gap-1">
        <p class="text-[11px] text-slate-400 uppercase tracking-widest">Gobaad Bank — Secure Banking</p>
        <p class="text-[11px] text-slate-400">Do not share your password with anyone.</p>
      </footer>
    </div>

    <!-- Mobile overlay when sidebar open on small screens -->
    <Transition name="overlay">
      <div
        v-if="sidebarOpen && isMobile"
        class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm md:hidden"
        @click="sidebarOpen = false"
      ></div>
    </Transition>

    <!-- Hidden logout form -->
    <form ref="logoutForm" :action="route('customer.logout')" method="POST" class="hidden">
      <input type="hidden" name="_token" :value="csrf" />
    </form>

    <!-- ── Session Timeout Modal ── -->
    <Transition name="overlay">
      <div v-if="showTimeoutWarning" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
          <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <i class="ti ti-clock-exclamation text-amber-600 text-2xl"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-2">Session Expiring</h3>
          <p class="text-sm text-slate-500 mb-1">
            You've been inactive. Your session will expire in
          </p>
          <p class="text-3xl font-bold text-red-600 mb-5">{{ countdownDisplay }}</p>
          <div class="flex gap-3">
            <button @click="extendSession"
              class="flex-1 bg-[#0B1929] hover:bg-[#1a3a5c] text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
              Stay Logged In
            </button>
            <button @click="logoutNow"
              class="flex-1 border border-slate-300 text-slate-600 hover:bg-slate-50 font-semibold py-2.5 rounded-xl text-sm transition-colors">
              Log Out
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const { isDark, toggleTheme } = useTheme()

// ── Session Timeout (3 min idle → warning, 5 min → auto logout) ────────────
const IDLE_WARN_MS   = 3 * 60 * 1000
const IDLE_LOGOUT_MS = 5 * 60 * 1000
const showTimeoutWarning = ref(false)
const countdownSec       = ref(120)
let idleTimer = null, countdownTimer = null

const countdownDisplay = computed(() => {
  const m = Math.floor(countdownSec.value / 60)
  const s = String(countdownSec.value % 60).padStart(2, '0')
  return m > 0 ? `${m}:${s}` : `${countdownSec.value}s`
})

function resetIdle() {
  if (showTimeoutWarning.value) return // don't reset during warning
  clearTimeout(idleTimer)
  idleTimer = setTimeout(() => {
    showTimeoutWarning.value = true
    countdownSec.value = 120
    countdownTimer = setInterval(() => {
      countdownSec.value--
      if (countdownSec.value <= 0) { clearInterval(countdownTimer); logoutNow() }
    }, 1000)
  }, IDLE_WARN_MS)
}

function extendSession() {
  showTimeoutWarning.value = false
  clearInterval(countdownTimer)
  resetIdle()
}

function logoutNow() {
  clearInterval(countdownTimer)
  clearTimeout(idleTimer)
  logoutForm.value?.submit()
}

const IDLE_EVENTS = ['mousemove', 'keydown', 'click', 'touchstart', 'scroll']

const page  = usePage()
const auth  = computed(() => page.props.auth ?? {})
const flash = computed(() => page.props.flash ?? {})

const sidebarOpen = ref(true)
const isMobile    = ref(false)
const logoutForm  = ref(null)
const csrf        = computed(() => document.querySelector('meta[name="csrf-token"]')?.content ?? '')

// Collapse on mobile by default
function checkMobile() {
  isMobile.value = window.innerWidth < 768
  if (isMobile.value) sidebarOpen.value = false
  else sidebarOpen.value = true
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  // Start idle tracking
  IDLE_EVENTS.forEach(e => window.addEventListener(e, resetIdle, { passive: true }))
  resetIdle()
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
  IDLE_EVENTS.forEach(e => window.removeEventListener(e, resetIdle))
  clearTimeout(idleTimer)
  clearInterval(countdownTimer)
})

const initials = computed(() => {
  const name = auth.value.customer?.full_name ?? ''
  return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'
})

const navLinks = [
  { route: 'customer.dashboard',    icon: 'ti ti-layout-dashboard',  label: 'Dashboard' },
  { route: 'customer.accounts',     icon: 'ti ti-credit-card',       label: 'My Accounts' },
  { route: 'customer.transactions', icon: 'ti ti-list',              label: 'Transactions' },
  { route: 'customer.transfer',             icon: 'ti ti-arrows-right-left', label: 'Transfer' },
  { route: 'customer.beneficiaries.index', icon: 'ti ti-users',             label: 'Beneficiaries' },
  { route: 'customer.profile',             icon: 'ti ti-user-circle',       label: 'My Profile' },
  { route: 'customer.reports',             icon: 'ti ti-file-analytics',    label: 'Reports' },
]

const pageTitleMap = {
  'customer.dashboard':    'Dashboard',
  'customer.accounts':     'My Accounts',
  'customer.transactions': 'Transactions',
  'customer.transfer':     'Transfer Funds',
  'customer.deposit':      'Deposit Request',
  'customer.withdraw':     'Withdrawal Request',
  'customer.notifications':'Notifications',
  'customer.profile':      'My Profile',
  'customer.reports':      'Reports',
}

const pageTitle = computed(() => {
  for (const [routeName, title] of Object.entries(pageTitleMap)) {
    if (route().current(routeName)) return title
  }
  return 'Customer Portal'
})

function logout() {
  logoutForm.value?.submit()
}
</script>

<style scoped>
.fade-text-enter-active,
.fade-text-leave-active { transition: opacity 0.15s ease, width 0.25s ease; overflow: hidden; }
.fade-text-enter-from,
.fade-text-leave-to     { opacity: 0; }

.overlay-enter-active,
.overlay-leave-active { transition: opacity 0.2s ease; }
.overlay-enter-from,
.overlay-leave-to     { opacity: 0; }
</style>
