<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    branch_name:       { type: String,  default: '—' },
    vault_balance:     { type: Number,  default: 0 },
    today_count:       { type: Number,  default: 0 },
    today_amount:      { type: Number,  default: 0 },
    month_count:       { type: Number,  default: 0 },
    month_amount:      { type: Number,  default: 0 },
    pending_approvals: { type: Number,  default: 0 },
    flagged_count:     { type: Number,  default: 0 },
    staff_total:       { type: Number,  default: 0 },
    staff_active:      { type: Number,  default: 0 },
    trend:             { type: Array,   default: () => [] },
    breakdown:         { type: Object,  default: () => ({}) },
    top_tellers:       { type: Array,   default: () => [] },
    recent_flagged:    { type: Array,   default: () => [] },
    recent_pending:    { type: Array,   default: () => [] },
})

const fmt   = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtN  = (n) => Number(n ?? 0).toLocaleString('en-US')

const typeIcon = (t) => ({
    deposit:      'ti-arrow-down-circle',
    withdrawal:   'ti-arrow-up-circle',
    transfer:     'ti-arrows-right-left',
    vault_deposit:'ti-building-bank',
}[t] ?? 'ti-activity')

const typeColor = (t) => ({
    deposit:       'text-emerald-400',
    withdrawal:    'text-red-400',
    transfer:      'text-blue-400',
    vault_deposit: 'text-amber-400',
}[t] ?? 'text-slate-400')

const maxTrend = Math.max(...props.trend.map(d => d.amount), 1)
</script>

<template>
  <Head :title="`${branch_name} — Branch Dashboard`" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-7xl mx-auto px-5 py-8 space-y-8">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[rgba(201,168,76,0.12)] border border-[rgba(201,168,76,0.2)] flex items-center justify-center">
              <i class="ti ti-building-bank text-[#C9A84C] text-2xl"></i>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-white font-serif">{{ branch_name }}</h1>
              <p class="text-xs text-slate-400 tracking-widest uppercase mt-0.5">Branch Manager Dashboard</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Link :href="route('staff.branch.reports')"
                  class="px-4 py-2 bg-[rgba(52,211,153,0.1)] border border-[rgba(52,211,153,0.2)] text-emerald-400 rounded-xl text-sm font-medium hover:bg-[rgba(52,211,153,0.15)] transition flex items-center gap-2">
              <i class="ti ti-chart-bar"></i> Reports
            </Link>
            <Link :href="route('staff.branch.clearing.index')"
                  class="px-4 py-2 bg-[rgba(56,189,248,0.1)] border border-[rgba(56,189,248,0.2)] text-sky-400 rounded-xl text-sm font-medium hover:bg-[rgba(56,189,248,0.15)] transition flex items-center gap-2">
              <i class="ti ti-arrows-exchange"></i> Clearing
            </Link>
          </div>
        </div>

        <!-- KPI Cards Row 1 -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Vault Balance -->
          <div class="bg-[#0d1f35] border border-[rgba(201,168,76,0.2)] rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Vault Balance</span>
              <div class="w-8 h-8 rounded-lg bg-[rgba(201,168,76,0.1)] flex items-center justify-center">
                <i class="ti ti-vault text-[#C9A84C] text-base"></i>
              </div>
            </div>
            <p class="text-2xl font-bold text-[#C9A84C]">${{ fmt(vault_balance) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Total cash on hand</p>
          </div>

          <!-- Today Transactions -->
          <div class="bg-[#0d1f35] border border-[rgba(76,175,125,0.2)] rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Today</span>
              <div class="w-8 h-8 rounded-lg bg-[rgba(76,175,125,0.1)] flex items-center justify-center">
                <i class="ti ti-activity text-emerald-400 text-base"></i>
              </div>
            </div>
            <p class="text-2xl font-bold text-emerald-400">{{ fmtN(today_count) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">${{ fmt(today_amount) }} volume</p>
          </div>

          <!-- Pending Approvals -->
          <div class="bg-[#0d1f35] border rounded-2xl p-5"
               :class="pending_approvals > 0 ? 'border-amber-500/30' : 'border-white/5'">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Pending</span>
              <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                <i class="ti ti-clock text-amber-400 text-base"></i>
              </div>
            </div>
            <p class="text-2xl font-bold" :class="pending_approvals > 0 ? 'text-amber-400' : 'text-slate-400'">
              {{ fmtN(pending_approvals) }}
            </p>
            <p class="text-[11px] text-slate-500 mt-1">Awaiting approval</p>
          </div>

          <!-- Flagged Transactions -->
          <div class="bg-[#0d1f35] border rounded-2xl p-5"
               :class="flagged_count > 0 ? 'border-red-500/30' : 'border-white/5'">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Flagged</span>
              <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center">
                <i class="ti ti-flag text-red-400 text-base"></i>
              </div>
            </div>
            <p class="text-2xl font-bold" :class="flagged_count > 0 ? 'text-red-400' : 'text-slate-400'">
              {{ fmtN(flagged_count) }}
            </p>
            <p class="text-[11px] text-slate-500 mt-1">Suspicious transactions</p>
          </div>
        </div>

        <!-- KPI Cards Row 2 -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5">
            <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-3">Month Transactions</p>
            <p class="text-2xl font-bold text-white">{{ fmtN(month_count) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">${{ fmt(month_amount) }} total</p>
          </div>
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5">
            <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-3">Active Staff</p>
            <p class="text-2xl font-bold text-white">{{ staff_active }}<span class="text-slate-500 text-lg"> / {{ staff_total }}</span></p>
            <p class="text-[11px] text-slate-500 mt-1">Staff online today</p>
          </div>
          <!-- Type breakdown mini cards -->
          <div v-for="(val, type) in breakdown" :key="type"
               class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-3">
              <i :class="['ti', typeIcon(type), typeColor(type), 'text-base']"></i>
              <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold capitalize">{{ type.replace('_', ' ') }}</p>
            </div>
            <p class="text-xl font-bold text-white">{{ fmtN(val.count) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">${{ fmt(val.amount) }}</p>
          </div>
        </div>

        <!-- 7-Day Trend + Top Tellers -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- 7-Day Bar Chart -->
          <div class="lg:col-span-2 bg-[#0d1f35] border border-white/5 rounded-2xl p-6">
            <h2 class="text-sm font-bold text-white mb-6">7-Day Transaction Volume</h2>
            <div class="flex items-end gap-3 h-36">
              <div v-for="day in trend" :key="day.date"
                   class="flex-1 flex flex-col items-center gap-1.5">
                <span class="text-[10px] text-slate-500">{{ day.count }}</span>
                <div class="w-full rounded-t-md bg-[rgba(201,168,76,0.15)] border-t border-[rgba(201,168,76,0.4)] transition-all"
                     :style="{ height: `${Math.max((day.amount / maxTrend) * 120, day.amount > 0 ? 6 : 2)}px` }">
                </div>
                <span class="text-[10px] text-slate-500 font-medium">{{ day.date }}</span>
              </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/5 flex justify-between text-[11px] text-slate-500">
              <span>Last 7 days</span>
              <span>${{ fmt(trend.reduce((s, d) => s + d.amount, 0)) }} total</span>
            </div>
          </div>

          <!-- Top Tellers -->
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-6">
            <h2 class="text-sm font-bold text-white mb-4">Top Tellers Today</h2>
            <div v-if="top_tellers.length" class="space-y-3">
              <div v-for="(t, i) in top_tellers" :key="i"
                   class="flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-7 h-7 rounded-full bg-[rgba(201,168,76,0.1)] flex items-center justify-center shrink-0">
                    <span class="text-[#C9A84C] text-xs font-bold">{{ i + 1 }}</span>
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ t.full_name }}</p>
                    <p class="text-[10px] text-slate-500 font-mono">{{ t.ident_number }}</p>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-xs font-bold text-emerald-400">${{ fmt(t.total) }}</p>
                  <p class="text-[10px] text-slate-500">{{ t.cnt }} txns</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-slate-600 py-8">
              <i class="ti ti-users text-3xl block mb-2"></i>
              <p class="text-sm">No transactions today</p>
            </div>
          </div>
        </div>

        <!-- Recent Flagged + Pending Approvals -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

          <!-- Recent Flagged -->
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="ti ti-flag text-orange-400"></i> Recent Flagged Transactions
              </h2>
              <Link :href="route('staff.compliance.transactions')"
                    class="text-xs text-orange-400 hover:underline">View all</Link>
            </div>
            <div v-if="recent_flagged.length" class="space-y-3">
              <div v-for="txn in recent_flagged" :key="txn.id"
                   class="flex items-center justify-between p-3 bg-orange-950/20 border border-orange-900/30 rounded-xl">
                <div>
                  <p class="text-xs font-mono text-white">{{ txn.reference }}</p>
                  <p class="text-[10px] text-slate-500 mt-0.5">{{ txn.primary_account?.customer?.full_name ?? '—' }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold text-orange-400">${{ fmt(txn.amount) }}</p>
                  <p class="text-[10px] text-slate-500 capitalize">{{ txn.type }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-slate-600 py-8">
              <i class="ti ti-shield-check text-3xl block mb-2 text-emerald-600"></i>
              <p class="text-sm">No flagged transactions</p>
            </div>
          </div>

          <!-- Recent Pending Approvals -->
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="ti ti-clock text-amber-400"></i> Pending Approvals
              </h2>
              <Link :href="route('staff.approvals')"
                    class="text-xs text-amber-400 hover:underline">View all</Link>
            </div>
            <div v-if="recent_pending.length" class="space-y-3">
              <div v-for="p in recent_pending" :key="p.id"
                   class="flex items-center justify-between p-3 bg-amber-950/20 border border-amber-900/30 rounded-xl">
                <div>
                  <p class="text-xs font-mono text-white">{{ p.transaction?.reference ?? '—' }}</p>
                  <p class="text-[10px] text-slate-500 mt-0.5">{{ p.requested_by?.full_name ?? '—' }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold text-amber-400">${{ fmt(p.transaction?.amount) }}</p>
                  <p class="text-[10px] text-amber-600 font-semibold uppercase">Pending</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-slate-600 py-8">
              <i class="ti ti-check text-3xl block mb-2 text-emerald-600"></i>
              <p class="text-sm">No pending approvals</p>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Link :href="route('staff.branch.staff.index')"
                class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5 hover:border-indigo-500/30 transition group text-center">
            <i class="ti ti-user-cog text-indigo-400 text-2xl block mb-2 group-hover:scale-110 transition-transform"></i>
            <p class="text-sm font-semibold text-white">Staff</p>
            <p class="text-[11px] text-slate-500">Management</p>
          </Link>
          <Link :href="route('staff.branch.settings')"
                class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5 hover:border-amber-500/30 transition group text-center">
            <i class="ti ti-settings-2 text-amber-400 text-2xl block mb-2 group-hover:scale-110 transition-transform"></i>
            <p class="text-sm font-semibold text-white">Settings</p>
            <p class="text-[11px] text-slate-500">Branch Config</p>
          </Link>
          <Link :href="route('staff.branch.clearing.index')"
                class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5 hover:border-sky-500/30 transition group text-center">
            <i class="ti ti-arrows-exchange text-sky-400 text-2xl block mb-2 group-hover:scale-110 transition-transform"></i>
            <p class="text-sm font-semibold text-white">Clearing</p>
            <p class="text-[11px] text-slate-500">Inter-Branch</p>
          </Link>
          <Link :href="route('staff.branch.audit')"
                class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5 hover:border-orange-500/30 transition group text-center">
            <i class="ti ti-history text-orange-400 text-2xl block mb-2 group-hover:scale-110 transition-transform"></i>
            <p class="text-sm font-semibold text-white">Audit Trail</p>
            <p class="text-[11px] text-slate-500">Activity Log</p>
          </Link>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
