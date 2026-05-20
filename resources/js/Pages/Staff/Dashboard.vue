<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    vault_balance:        { type: Number,  default: 0 },
    staff_info:           { type: Object,  default: () => ({}) },
    stats:                { type: Object,  default: () => ({}) },
    recent_transactions:  { type: Array,   default: () => [] },
    // Manager analytics
    branch_breakdown:     { type: Object,  default: () => ({ deposit: {count:0,amount:0}, withdraw: {count:0,amount:0}, transfer: {count:0,amount:0} }) },
    weekly_trend:         { type: Array,   default: () => [] },
    active_tills:         { type: Array,   default: () => [] },
    branch_today_txns:    { type: Number,  default: 0 },
    branch_today_amount:  { type: Number,  default: 0 },
    yesterday_txns:       { type: Number,  default: 0 },
    yesterday_amount:     { type: Number,  default: 0 },
    branch_total_cash:    { type: Number,  default: 0 },
    flash:  { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
})

const role    = computed(() => props.staff_info?.role_name ?? '')
const isManager = computed(() => role.value.includes('Manager'))

const initials = computed(() => {
    const parts = (props.staff_info?.full_name ?? '?').split(' ')
    return parts.length >= 2 ? parts[0][0] + parts[1][0] : parts[0][0]
})

const fmt      = n => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtShort = n => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
const fmtK     = n => {
    const v = Number(n ?? 0)
    if (v >= 1_000_000) return `$${(v / 1_000_000).toFixed(1)}M`
    if (v >= 1_000)     return `$${(v / 1_000).toFixed(1)}K`
    return `$${fmt(v)}`
}

/* ── Role-aware greeting subtitle ── */
const roleSubtitle = computed(() => {
    const r = role.value
    if (r.includes('Manager'))    return 'Branch Manager Portal'
    if (r.includes('Teller'))     return 'Teller Operations Terminal'
    if (r.includes('Compliance')) return 'Compliance Review Station'
    if (r.includes('Customer'))   return 'Customer Service Desk'
    if (r.includes('Accounting')) return 'Accounting & Ledger'
    if (r.includes('Super'))      return 'System Administration'
    return 'Staff Portal'
})

/* ── Trend badge helper ── */
function trendLabel(today, yesterday) {
    if (yesterday === 0 && today === 0) return { text: '—', cls: 'text-slate-500' }
    if (yesterday === 0) return { text: 'new today', cls: 'text-emerald-400' }
    const pct = Math.round((today - yesterday) / yesterday * 100)
    if (pct > 0)  return { text: `+${pct}% vs yesterday`, cls: 'text-emerald-400' }
    if (pct < 0)  return { text: `${pct}% vs yesterday`,  cls: 'text-red-400' }
    return { text: 'same as yesterday', cls: 'text-slate-400' }
}

/* ── Manager: 4 stat cards ── */
const managerCards = computed(() => {
    const s = props.stats
    return [
        {
            label: 'Branch Total Cash',
            value: fmtK(props.branch_total_cash),
            sub:   `Vault + ${props.active_tills.length} active till${props.active_tills.length !== 1 ? 's' : ''}`,
            icon:  'ti-safe',
            color: 'gold',
        },
        {
            label: "Today's Activity",
            value: fmtShort(props.branch_today_txns),
            sub:   `${fmtK(props.branch_today_amount)} processed`,
            trend: trendLabel(props.branch_today_txns, props.yesterday_txns),
            icon:  'ti-arrows-exchange',
            color: 'indigo',
        },
        {
            label: 'Pending Approvals',
            value: fmtShort(s.pending_approvals),
            sub:   s.pending_approvals > 0 ? 'Awaiting your review' : 'Queue is clear',
            icon:  'ti-clipboard-check',
            color: s.pending_approvals > 0 ? 'amber' : 'emerald',
        },
        {
            label: 'Active Tills',
            value: fmtShort(props.active_tills.length),
            sub:   `of ${fmtShort(s.branch_staff)} branch staff`,
            icon:  'ti-cash-register',
            color: props.active_tills.length > 0 ? 'purple' : 'slate',
        },
    ]
})

/* ── Other roles: 4 stat cards ── */
const statCards = computed(() => {
    const s   = props.stats
    const r   = role.value
    const cards = []

    if (s.till_open) {
        cards.push({ label: 'My Till Balance', value: `$${fmt(s.till_balance)}`, sub: 'Active float allocated', icon: 'ti-wallet', color: 'amber' })
    } else {
        cards.push({ label: 'Branch Vault', value: `$${fmt(props.vault_balance)}`, sub: 'Live vault balance', icon: 'ti-building-bank', color: 'gold' })
    }

    cards.push({ label: "Today's Transactions", value: fmtShort(s.today_txns), sub: `$${fmt(s.today_amount)} processed`, icon: 'ti-arrows-exchange', color: 'indigo' })

    if (r.includes('Compliance') || r.includes('Customer')) {
        cards.push({ label: 'Pending KYC', value: fmtShort(s.pending_kyc), sub: 'Documents awaiting review', icon: 'ti-id-badge', color: s.pending_kyc > 0 ? 'amber' : 'emerald' })
    } else {
        cards.push({ label: 'Month Transactions', value: fmtShort(s.month_txns), sub: 'Processed this month', icon: 'ti-chart-bar', color: 'emerald' })
    }

    if (r.includes('Teller') || r.includes('Supervisor')) {
        cards.push({ label: 'Till Status', value: s.till_open ? 'Open' : 'No Till', sub: s.till_open ? 'Float allocated & active' : 'Request float from cashier', icon: s.till_open ? 'ti-lock-open' : 'ti-lock', color: s.till_open ? 'emerald' : 'slate' })
    } else {
        cards.push({ label: 'Branch Staff', value: fmtShort(s.branch_staff), sub: 'Active team members', icon: 'ti-users', color: 'purple' })
    }

    return cards
})

/* ── Transaction breakdown: proportional widths ── */
const breakdownTotal = computed(() => {
    const b = props.branch_breakdown
    return b.deposit.count + b.withdraw.count + b.transfer.count
})
function breakdownPct(type) {
    const total = breakdownTotal.value
    if (total === 0) return 0
    return Math.round(props.branch_breakdown[type].count / total * 100)
}

/* ── Weekly trend chart ── */
const maxTrendCount = computed(() => {
    const counts = props.weekly_trend.map(d => d.count)
    return Math.max(...counts, 1)
})

/* ── ApexCharts: 7-day bar + line chart ── */
const trendChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 200,
        background: 'transparent',
        toolbar: { show: false },
        sparkline: { enabled: false },
    },
    theme: { mode: 'dark' },
    colors: ['#C9A84C', '#3b82f6'],
    plotOptions: {
        bar: { borderRadius: 4, columnWidth: '55%' }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: [0, 2] },
    xaxis: {
        categories: props.weekly_trend.map(d => d.label),
        labels: { style: { colors: '#64748b', fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: [
        {
            labels: { style: { colors: '#64748b', fontSize: '11px' },
                      formatter: v => Math.round(v) },
        },
        {
            opposite: true,
            labels: {
                style: { colors: '#64748b', fontSize: '11px' },
                formatter: v => v >= 1000 ? `$${(v/1000).toFixed(1)}k` : `$${v}`
            }
        }
    ],
    grid: { borderColor: '#1e293b', strokeDashArray: 3 },
    tooltip: {
        theme: 'dark',
        y: [
            { formatter: v => `${v} txns` },
            { formatter: v => `$${Number(v).toLocaleString('en-US', { minimumFractionDigits: 0 })}` },
        ]
    },
    legend: { labels: { colors: '#94a3b8' } },
}))

const trendChartSeries = computed(() => [
    { name: 'Transactions', type: 'bar', data: props.weekly_trend.map(d => d.count) },
    { name: 'Volume ($)',   type: 'line', data: props.weekly_trend.map(d => Math.round(d.amount)) },
])

/* ── ApexCharts: transaction type donut ── */
const donutOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent', toolbar: { show: false } },
    theme: { mode: 'dark' },
    colors: ['#10b981', '#ef4444', '#6366f1'],
    labels: ['Deposits', 'Withdrawals', 'Transfers'],
    dataLabels: { enabled: false },
    legend: {
        position: 'bottom',
        labels: { colors: '#94a3b8' },
        fontSize: '11px',
    },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        color: '#94a3b8',
                        fontSize: '12px',
                        formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                    }
                }
            }
        }
    },
    tooltip: { theme: 'dark' },
}))

const donutSeries = computed(() => {
    const b = props.branch_breakdown
    return [b.deposit.count, b.withdraw.count, b.transfer.count]
})

/* ── Quick action links ── */
const quickActions = computed(() => {
    const r = role.value
    const all = []
    if (r.includes('Manager')) {
        all.push(
            { label: 'Branch Settings',  icon: 'ti-settings',       href: route('staff.branch.settings'),             color: 'gold'    },
            { label: 'Staff Management', icon: 'ti-users',           href: route('staff.branch.staff.index'),          color: 'indigo'  },
            { label: 'Audit Trail',      icon: 'ti-history',         href: route('staff.branch.audit'),                color: 'purple'  },
            { label: 'Cash Allocation',  icon: 'ti-cash',            href: route('staff.teller.cash-allocation.index'),color: 'amber'   },
            { label: 'Approvals',        icon: 'ti-clipboard-check', href: route('staff.approvals'),                   color: 'emerald' },
        )
    } else if (r.includes('Teller') || r.includes('Supervisor')) {
        all.push(
            { label: 'Cash Deposit',  icon: 'ti-arrow-down-right',  href: route('staff.teller.deposit'),             color: 'emerald' },
            { label: 'Withdrawal',    icon: 'ti-arrow-up-right',    href: route('staff.teller.withdraw'),            color: 'amber'   },
            { label: 'Transfer',      icon: 'ti-arrows-right-left', href: route('staff.teller.transfer'),            color: 'purple'  },
            { label: 'Cash Count',    icon: 'ti-calculator',        href: route('staff.teller.cash-count.index'),    color: 'indigo'  },
            { label: 'My Till',       icon: 'ti-wallet',            href: route('staff.teller.my-till'),             color: 'gold'    },
        )
    } else if (r.includes('Customer')) {
        all.push(
            { label: 'Register Customer', icon: 'ti-user-plus',   href: route('staff.customer-care.register'),          color: 'emerald' },
            { label: 'Customers',         icon: 'ti-users',       href: route('staff.customer-care.customers'),         color: 'indigo'  },
            { label: 'KYC Pending',       icon: 'ti-id-badge',    href: route('staff.customer-care.kyc.list'),          color: 'amber'   },
            { label: 'Accounts',          icon: 'ti-credit-card', href: route('staff.customer-care.accounts.index'),    color: 'purple'  },
        )
    } else if (r.includes('Compliance')) {
        all.push({ label: 'Compliance Review', icon: 'ti-shield-check', href: route('staff.compliance.index'), color: 'indigo' })
    } else if (r.includes('Accounting')) {
        all.push(
            { label: 'Ledger',        icon: 'ti-book',      href: route('staff.accounting.ledger'),        color: 'indigo'  },
            { label: 'Trial Balance', icon: 'ti-chart-bar', href: route('staff.accounting.trial-balance'), color: 'emerald' },
        )
    }
    all.push({ label: 'My Profile', icon: 'ti-user-circle', href: route('staff.profile'), color: 'slate' })
    return all
})

/* ── Color maps ── */
const cardColorMap = {
    gold:    'bg-[#C9A84C]/10 border-[#C9A84C]/30 text-[#C9A84C]',
    amber:   'bg-amber-900/20 border-amber-700/30 text-amber-400',
    indigo:  'bg-indigo-900/20 border-indigo-700/30 text-indigo-400',
    emerald: 'bg-emerald-900/20 border-emerald-700/30 text-emerald-400',
    purple:  'bg-purple-900/20 border-purple-700/30 text-purple-400',
    slate:   'bg-slate-800/60 border-slate-600/30 text-slate-400',
}
const actionColorMap = {
    gold:    'bg-[#C9A84C]/10 text-[#C9A84C] border-[#C9A84C]/20 hover:bg-[#C9A84C]/20',
    amber:   'bg-amber-900/20 text-amber-400 border-amber-700/20 hover:bg-amber-900/40',
    indigo:  'bg-indigo-900/20 text-indigo-400 border-indigo-700/20 hover:bg-indigo-900/40',
    emerald: 'bg-emerald-900/20 text-emerald-400 border-emerald-700/20 hover:bg-emerald-900/40',
    purple:  'bg-purple-900/20 text-purple-400 border-purple-700/20 hover:bg-purple-900/40',
    slate:   'bg-slate-800/50 text-slate-300 border-slate-700/30 hover:bg-slate-700/60',
}

const txTypeColor = t => {
    if (!t) return 'text-slate-400'
    const v = t.toLowerCase()
    if (v === 'deposit')  return 'text-emerald-400'
    if (v === 'withdraw') return 'text-red-400'
    if (v === 'transfer') return 'text-indigo-400'
    return 'text-slate-400'
}
const txTypeIcon = t => {
    if (!t) return 'ti-arrows-exchange'
    const v = t.toLowerCase()
    if (v === 'deposit')  return 'ti-arrow-down-right'
    if (v === 'withdraw') return 'ti-arrow-up-right'
    if (v === 'transfer') return 'ti-arrows-right-left'
    return 'ti-arrows-exchange'
}
const txTypeBg = t => {
    if (!t) return 'bg-slate-800/50'
    const v = t.toLowerCase()
    if (v === 'deposit')  return 'bg-emerald-900/30'
    if (v === 'withdraw') return 'bg-red-900/30'
    if (v === 'transfer') return 'bg-indigo-900/30'
    return 'bg-slate-800/50'
}
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-6xl mx-auto px-5 py-7 space-y-6">

        <!-- ── Greeting Header ── -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl overflow-hidden bg-indigo-900/40 border border-indigo-700/30 flex items-center justify-center shrink-0">
              <img v-if="staff_info.avatar" :src="'/storage/' + staff_info.avatar" class="w-full h-full object-cover" />
              <span v-else class="text-lg font-bold text-indigo-300">{{ initials }}</span>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">
                Good day, <span class="text-[#C9A84C]">{{ staff_info.full_name?.split(' ')[0] }}</span>
              </h1>
              <p class="text-xs text-slate-400 mt-0.5">
                <span class="font-mono text-slate-500">{{ staff_info.ident_number }}</span>
                <span class="mx-1.5 text-slate-700">·</span>
                {{ roleSubtitle }}
                <span class="mx-1.5 text-slate-700">·</span>
                {{ staff_info.branch_name }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs text-slate-500">{{ new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) }}</span>
            <span class="text-xs px-3 py-1.5 rounded-full bg-[#C9A84C]/10 text-[#C9A84C] border border-[#C9A84C]/20 font-medium">
              {{ role }}
            </span>
          </div>
        </div>

        <!-- ── Flash ── -->
        <Transition name="fade">
          <div v-if="$page.props.flash?.success"
               class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
            <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
          </div>
        </Transition>
        <Transition name="fade">
          <div v-if="$page.props.errors?.error"
               class="bg-red-900/40 border border-red-600/40 text-red-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
            <i class="ti ti-alert-circle text-lg"></i>{{ $page.props.errors.error }}
          </div>
        </Transition>

        <!-- ── Stats Grid ── -->
        <!-- Manager cards -->
        <div v-if="isManager" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div v-for="(card, i) in managerCards" :key="i"
               class="bg-slate-900 border border-slate-700/60 rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <p class="text-xs text-slate-400 font-medium">{{ card.label }}</p>
              <div :class="cardColorMap[card.color]"
                   class="w-8 h-8 rounded-xl border flex items-center justify-center shrink-0">
                <i :class="'ti ' + card.icon + ' text-sm'"></i>
              </div>
            </div>
            <div>
              <p class="text-2xl font-bold text-white leading-none">{{ card.value }}</p>
              <p v-if="card.trend" :class="card.trend.cls" class="text-[11px] mt-1.5 font-medium">
                <i class="ti ti-trending-up mr-0.5" v-if="card.trend.cls.includes('emerald')"></i>
                <i class="ti ti-trending-down mr-0.5" v-else-if="card.trend.cls.includes('red')"></i>
                {{ card.trend.text }}
              </p>
              <p v-else class="text-xs text-slate-500 mt-1.5">{{ card.sub }}</p>
            </div>
          </div>
        </div>

        <!-- Other role cards -->
        <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div v-for="(card, i) in statCards" :key="i"
               class="bg-slate-900 border border-slate-700/60 rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <p class="text-xs text-slate-400 font-medium">{{ card.label }}</p>
              <div :class="cardColorMap[card.color]"
                   class="w-8 h-8 rounded-xl border flex items-center justify-center">
                <i :class="'ti ' + card.icon + ' text-sm'"></i>
              </div>
            </div>
            <div>
              <p class="text-2xl font-bold text-white leading-none">{{ card.value }}</p>
              <p class="text-xs text-slate-500 mt-1.5">{{ card.sub }}</p>
            </div>
          </div>
        </div>

        <!-- ════════════════════════════════════════════
             MANAGER-ONLY SECTIONS
        ════════════════════════════════════════════ -->
        <template v-if="isManager">

          <!-- ── Charts Row: 7-Day Trend + Type Donut ── -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- 7-Day Trend (ApexCharts) -->
            <div class="lg:col-span-2 bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
              <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                  <h2 class="font-semibold text-white text-sm">7-Day Transaction Trend</h2>
                  <p class="text-xs text-slate-500 mt-0.5">Count (bars) · Volume in $ (line)</p>
                </div>
                <i class="ti ti-chart-bar text-[#C9A84C] text-lg"></i>
              </div>
              <div class="px-2 py-3">
                <VueApexCharts
                  v-if="weekly_trend.length"
                  type="bar"
                  height="200"
                  :options="trendChartOptions"
                  :series="trendChartSeries"
                />
                <div v-else class="h-48 flex items-center justify-center text-slate-600 text-sm">
                  No transaction data yet
                </div>
              </div>
            </div>

            <!-- Type Donut (ApexCharts) -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
              <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                  <h2 class="font-semibold text-white text-sm">Today's Breakdown</h2>
                  <p class="text-xs text-slate-500 mt-0.5">By transaction type</p>
                </div>
                <i class="ti ti-chart-donut text-[#C9A84C] text-lg"></i>
              </div>
              <div class="px-2 py-1">
                <VueApexCharts
                  v-if="branch_today_txns > 0"
                  type="donut"
                  height="220"
                  :options="donutOptions"
                  :series="donutSeries"
                />
                <div v-else class="h-52 flex flex-col items-center justify-center text-slate-600">
                  <i class="ti ti-chart-donut text-3xl mb-2"></i>
                  <p class="text-sm">No transactions today</p>
                </div>
              </div>
              <!-- Amount summary below donut -->
              <div v-if="branch_today_txns > 0" class="px-5 pb-4 space-y-2">
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1.5 text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>Deposits</span>
                  <span class="text-slate-300 font-medium">${{ fmt(branch_breakdown.deposit.amount) }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1.5 text-red-400"><span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>Withdrawals</span>
                  <span class="text-slate-300 font-medium">${{ fmt(branch_breakdown.withdraw.amount) }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1.5 text-indigo-400"><span class="w-2 h-2 rounded-full bg-indigo-500 inline-block"></span>Transfers</span>
                  <span class="text-slate-300 font-medium">${{ fmt(branch_breakdown.transfer.amount) }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- ── Active Tills ── -->
          <div>
            <!-- Active Tills -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
              <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                  <h2 class="font-semibold text-white text-sm">Active Tills</h2>
                  <p class="text-xs text-slate-500 mt-0.5">Tellers with acknowledged float</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-purple-900/40 text-purple-400 border border-purple-700/30">
                  {{ active_tills.length }} open
                </span>
              </div>
              <div class="overflow-y-auto max-h-[220px]">
                <div v-if="active_tills.length === 0" class="px-5 py-10 text-center">
                  <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center mx-auto mb-2">
                    <i class="ti ti-cash-register text-slate-500 text-lg"></i>
                  </div>
                  <p class="text-sm text-slate-500">No tills open yet today</p>
                </div>
                <div v-else>
                  <!-- Header row -->
                  <div class="grid grid-cols-3 px-5 py-2 border-b border-slate-800 bg-slate-900/80">
                    <span class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold">Teller</span>
                    <span class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold text-right">Opening Float</span>
                    <span class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold text-right">Current Balance</span>
                  </div>
                  <div v-for="(till, i) in active_tills" :key="i"
                       class="grid grid-cols-3 px-5 py-3 border-b border-slate-800/60 last:border-0 hover:bg-slate-800/30 transition">
                    <div class="flex items-center gap-2">
                      <div class="w-6 h-6 rounded-lg bg-purple-900/40 flex items-center justify-center shrink-0">
                        <span class="text-[9px] font-bold text-purple-400">{{ till.teller_name.charAt(0) }}</span>
                      </div>
                      <div>
                        <p class="text-xs font-medium text-white truncate max-w-[90px]">{{ till.teller_name }}</p>
                        <p class="text-[9px] text-slate-500">Since {{ till.since }}</p>
                      </div>
                    </div>
                    <p class="text-xs text-slate-400 text-right self-center">${{ fmt(till.opening_float) }}</p>
                    <p class="text-xs font-semibold text-right self-center"
                       :class="till.till_balance >= till.opening_float * 0.9 ? 'text-emerald-400' : 'text-amber-400'">
                      ${{ fmt(till.till_balance) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </template>
        <!-- ════════════════════════════════════════════ -->

        <!-- ── Quick Actions + Recent Transactions ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <!-- Quick Actions -->
          <div class="lg:col-span-1 bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800">
              <h2 class="font-semibold text-white text-sm">Quick Actions</h2>
              <p class="text-xs text-slate-500 mt-0.5">Jump to your most-used pages</p>
            </div>
            <div class="p-3 space-y-1.5">
              <Link v-for="action in quickActions" :key="action.label"
                    :href="action.href"
                    :class="actionColorMap[action.color]"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl border text-sm font-medium transition-colors">
                <i :class="'ti ' + action.icon + ' text-base'"></i>
                {{ action.label }}
              </Link>
            </div>
          </div>

          <!-- Recent Transactions -->
          <div class="lg:col-span-2 bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
              <div>
                <h2 class="font-semibold text-white text-sm">
                  {{ isManager ? 'Branch Transactions' : 'Recent Transactions' }}
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                  {{ isManager ? 'Latest activity across all tellers' : 'Your last processed transactions' }}
                </p>
              </div>
              <Link v-if="isManager" :href="route('staff.approvals')"
                    class="text-xs text-[#C9A84C] hover:underline">
                View approvals →
              </Link>
            </div>

            <div v-if="recent_transactions.length" class="divide-y divide-slate-800">
              <div v-for="tx in recent_transactions" :key="tx.id"
                   class="px-5 py-3 flex items-center gap-3">
                <div :class="[txTypeBg(tx.type), txTypeColor(tx.type)]"
                     class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0">
                  <i :class="'ti ' + txTypeIcon(tx.type) + ' text-sm'"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-white capitalize">{{ tx.type }}</p>
                  <p class="text-xs text-slate-500 font-mono truncate">
                    {{ tx.reference }}
                    <span v-if="isManager && tx.teller" class="ml-1.5 not-italic font-sans text-slate-600">
                      · {{ tx.teller }}
                    </span>
                  </p>
                </div>
                <div class="text-right shrink-0">
                  <p :class="txTypeColor(tx.type)" class="text-sm font-semibold">
                    {{ tx.type === 'deposit' ? '+' : tx.type === 'withdraw' ? '-' : '' }}${{ fmt(tx.amount) }}
                  </p>
                  <p class="text-[10px] text-slate-500 mt-0.5">{{ tx.created_at }}</p>
                </div>
                <!-- Status badge -->
                <span v-if="tx.status && tx.status !== 'completed'"
                      class="text-[9px] px-1.5 py-0.5 rounded-full font-bold shrink-0"
                      :class="tx.status === 'pending' ? 'bg-amber-900/40 text-amber-400 border border-amber-700/30'
                            : tx.status === 'cancelled' ? 'bg-red-900/40 text-red-400 border border-red-700/30'
                            : 'bg-slate-800 text-slate-400'">
                  {{ tx.status }}
                </span>
              </div>
            </div>

            <div v-else class="px-5 py-12 text-center">
              <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center mx-auto mb-3">
                <i class="ti ti-arrows-exchange text-xl text-slate-500"></i>
              </div>
              <p class="text-sm text-slate-500">No transactions yet today</p>
              <p class="text-xs text-slate-600 mt-1">Processed transactions will appear here</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
