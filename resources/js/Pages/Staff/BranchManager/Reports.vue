<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, watch, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    summary:       { type: Object,  default: () => ({}) },
    by_type:       { type: Array,   default: () => [] },
    daily_trend:   { type: Array,   default: () => [] },
    top_tellers:   { type: Array,   default: () => [] },
    largest_txns:  { type: Array,   default: () => [] },
    vault_balance: { type: Number,  default: null },
    branch_name:   { type: String,  default: '—' },
    swift_code:    { type: String,  default: null },
    branches:      { type: Array,   default: () => [] },
    date_from:     { type: String,  default: '' },
    date_to:       { type: String,  default: '' },
    filters:       { type: Object,  default: () => ({}) },
    is_restricted: { type: Boolean, default: true },
})

const filters = reactive({
    date_from: props.filters.date_from ?? props.date_from,
    date_to:   props.filters.date_to   ?? props.date_to,
    branch_id: props.filters.branch_id ?? '',
})

let debounce = null
watch(filters, () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        router.get(route('staff.branch.reports'), { ...filters }, { preserveState: true, replace: true })
    }, 400)
})

function printReport() { window.print() }

const fmt  = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtN = (n) => Number(n ?? 0).toLocaleString('en-US')
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
const fmtDateLong = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '—'
const nowStr = new Date().toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })

// Type config
const typeConfig = {
    deposit:       { label: 'Deposit',       color: '#4CAF7D', bg: 'rgba(76,175,125,0.12)', text: 'text-emerald-400', badge: 'bg-emerald-900/40 text-emerald-400', icon: 'ti-arrow-down-circle' },
    withdrawal:    { label: 'Withdrawal',    color: '#E2635A', bg: 'rgba(226,99,90,0.12)',   text: 'text-red-400',     badge: 'bg-red-900/40 text-red-400',         icon: 'ti-arrow-up-circle' },
    transfer:      { label: 'Transfer',      color: '#818CF8', bg: 'rgba(129,140,248,0.12)', text: 'text-indigo-400',  badge: 'bg-indigo-900/40 text-indigo-400',   icon: 'ti-arrows-right-left' },
    vault_deposit: { label: 'Vault Deposit', color: '#C9A84C', bg: 'rgba(201,168,76,0.12)',  text: 'text-amber-400',   badge: 'bg-amber-900/40 text-amber-400',     icon: 'ti-building-bank' },
}
const getType = (t) => typeConfig[t] ?? { label: t, color: '#6B7E8E', bg: 'rgba(107,126,142,0.1)', text: 'text-slate-400', badge: 'bg-slate-800 text-slate-400', icon: 'ti-activity' }

// Chart calculations
const maxBar  = computed(() => Math.max(...props.daily_trend.map(d => d.amount), 1))
const totalVol = computed(() => props.summary.total_volume ?? 0)
const typeWithPct = computed(() =>
    props.by_type.map(r => ({
        ...r,
        pct: totalVol.value > 0 ? Math.round((r.total / totalVol.value) * 100) : 0,
    }))
)

// Chart SVG dimensions
const chartW = 600
const chartH = 140
const barPad = 4
const barCount = computed(() => props.daily_trend.length || 1)
const barW     = computed(() => Math.max(4, (chartW - barPad * (barCount.value + 1)) / barCount.value))
</script>

<template>
  <Head :title="`Branch Reports — ${branch_name}`" />
  <AuthenticatedLayout>

    <!-- ═══════════════════════════════════════════════════════════
         SCREEN VIEW  (dark theme)
    ═══════════════════════════════════════════════════════════ -->
    <div class="min-h-screen bg-[#070F1A] text-white print:hidden">
      <div class="max-w-7xl mx-auto px-5 py-8 space-y-7">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <Link :href="route('staff.branch.dashboard')"
                  class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-all">
              <i class="ti ti-arrow-left text-base"></i>
            </Link>
            <div class="w-11 h-11 rounded-2xl bg-[rgba(76,175,125,0.1)] border border-[rgba(76,175,125,0.2)] flex items-center justify-center">
              <i class="ti ti-chart-bar text-[#4CAF7D] text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white font-serif">Branch Performance Report</h1>
              <p class="text-xs text-slate-400 mt-0.5">{{ branch_name }} · {{ fmtDateLong(date_from) }} — {{ fmtDateLong(date_to) }}</p>
            </div>
          </div>
          <div class="flex gap-2">
            <button @click="printReport"
                    class="px-4 py-2 bg-[rgba(76,175,125,0.1)] border border-[rgba(76,175,125,0.25)] text-[#4CAF7D] rounded-xl text-sm font-semibold hover:bg-[rgba(76,175,125,0.18)] transition flex items-center gap-2">
              <i class="ti ti-printer"></i> Export / Print
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 bg-[#0d1f35] border border-white/5 rounded-2xl px-5 py-4">
          <i class="ti ti-adjustments-horizontal text-slate-400"></i>
          <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider mr-1">Period</span>
          <input v-model="filters.date_from" type="date"
                 class="bg-[#0B1929] border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[#4CAF7D]/50 transition-colors" />
          <span class="text-slate-600 text-sm">→</span>
          <input v-model="filters.date_to" type="date"
                 class="bg-[#0B1929] border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[#4CAF7D]/50 transition-colors" />
          <select v-if="!is_restricted" v-model="filters.branch_id"
                  class="bg-[#0B1929] border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none transition-colors ml-2">
            <option value="">All Branches</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
          </select>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5 flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Total Txns</span>
              <i class="ti ti-list-numbers text-slate-600 text-base"></i>
            </div>
            <p class="text-3xl font-bold text-white">{{ fmtN(summary.total_transactions) }}</p>
          </div>
          <div class="bg-[#0d1f35] border border-[rgba(76,175,125,0.2)] rounded-2xl p-5 flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Volume</span>
              <i class="ti ti-trending-up text-[#4CAF7D] text-base"></i>
            </div>
            <p class="text-3xl font-bold text-[#4CAF7D]">${{ fmt(summary.total_volume) }}</p>
          </div>
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5 flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Completed</span>
              <i class="ti ti-circle-check text-slate-600 text-base"></i>
            </div>
            <p class="text-3xl font-bold text-white">{{ fmtN(summary.completed) }}</p>
          </div>
          <div class="bg-[#0d1f35] border rounded-2xl p-5 flex flex-col gap-2"
               :class="summary.pending > 0 ? 'border-amber-500/25' : 'border-white/5'">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Pending</span>
              <i class="ti ti-clock text-amber-500/70 text-base"></i>
            </div>
            <p class="text-3xl font-bold" :class="summary.pending > 0 ? 'text-amber-400' : 'text-slate-500'">{{ fmtN(summary.pending) }}</p>
          </div>
          <div class="bg-[#0d1f35] border rounded-2xl p-5 flex flex-col gap-2"
               :class="summary.flagged > 0 ? 'border-red-500/25' : 'border-white/5'">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Flagged</span>
              <i class="ti ti-flag text-red-500/70 text-base"></i>
            </div>
            <p class="text-3xl font-bold" :class="summary.flagged > 0 ? 'text-red-400' : 'text-slate-500'">{{ fmtN(summary.flagged) }}</p>
          </div>
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5 flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Avg Txn</span>
              <i class="ti ti-math-avg text-slate-600 text-base"></i>
            </div>
            <p class="text-3xl font-bold text-white">${{ fmt(summary.avg_amount) }}</p>
          </div>
        </div>

        <!-- Vault + Type Breakdown row -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

          <!-- Vault -->
          <div v-if="vault_balance !== null"
               class="lg:col-span-2 bg-[rgba(201,168,76,0.04)] border border-[rgba(201,168,76,0.18)] rounded-2xl p-6 flex flex-col justify-between">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-xl bg-[rgba(201,168,76,0.12)] flex items-center justify-center">
                <i class="ti ti-vault text-[#C9A84C] text-xl"></i>
              </div>
              <div>
                <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Vault Balance</p>
                <p class="text-[10px] text-slate-600 mt-0.5">Current cash on hand</p>
              </div>
            </div>
            <p class="text-4xl font-bold text-[#C9A84C] font-serif">${{ fmt(vault_balance) }}</p>
            <p class="text-xs text-slate-500 mt-3">As of {{ nowStr }}</p>
          </div>

          <!-- Type Breakdown -->
          <div class="lg:col-span-3 bg-[#0d1f35] border border-white/5 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-5">Transaction Breakdown by Type</h3>
            <div v-if="typeWithPct.length" class="space-y-4">
              <div v-for="row in typeWithPct" :key="row.type">
                <div class="flex items-center justify-between mb-1.5">
                  <div class="flex items-center gap-2">
                    <i :class="['ti', getType(row.type).icon, getType(row.type).text, 'text-sm']"></i>
                    <span class="text-sm font-semibold text-white capitalize">{{ row.type?.replace('_', ' ') }}</span>
                    <span class="text-xs text-slate-500">{{ row.count }} txns</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400">{{ row.pct }}%</span>
                    <span class="text-sm font-bold text-white">${{ fmt(row.total) }}</span>
                  </div>
                </div>
                <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-700"
                       :style="{ width: row.pct + '%', backgroundColor: getType(row.type).color }"></div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-slate-600 py-8">
              <i class="ti ti-chart-pie text-3xl block mb-2"></i>
              <p class="text-sm">No transactions in this period.</p>
            </div>
          </div>
        </div>

        <!-- Daily Trend Chart (SVG) -->
        <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-sm font-bold text-white">Daily Transaction Volume</h3>
            <span class="text-xs text-slate-500">{{ daily_trend.length }} days</span>
          </div>
          <div v-if="daily_trend.length" class="overflow-x-auto">
            <svg :viewBox="`0 0 ${chartW} ${chartH + 30}`" class="w-full" :style="{ minWidth: daily_trend.length * 20 + 'px' }">
              <!-- Grid lines -->
              <line v-for="i in 4" :key="i"
                    x1="0" :x2="chartW"
                    :y1="chartH - (chartH / 4) * i" :y2="chartH - (chartH / 4) * i"
                    stroke="rgba(255,255,255,0.04)" stroke-width="1" />
              <!-- Bars -->
              <g v-for="(day, idx) in daily_trend" :key="day.date">
                <rect
                  :x="barPad + idx * (barW + barPad)"
                  :y="chartH - Math.max((day.amount / maxBar) * chartH, day.amount > 0 ? 4 : 0)"
                  :width="barW"
                  :height="Math.max((day.amount / maxBar) * chartH, day.amount > 0 ? 4 : 0)"
                  rx="3" ry="3"
                  fill="url(#barGrad)"
                  opacity="0.85"
                />
                <!-- Amount label on top if bar is tall enough -->
                <text v-if="(day.amount / maxBar) > 0.15"
                      :x="barPad + idx * (barW + barPad) + barW / 2"
                      :y="chartH - Math.max((day.amount / maxBar) * chartH, 4) - 4"
                      text-anchor="middle" fill="#6B7E8E" font-size="7">
                  ${{ Math.round(day.amount / 1000) }}k
                </text>
                <!-- Date label -->
                <text
                  :x="barPad + idx * (barW + barPad) + barW / 2"
                  :y="chartH + 16"
                  text-anchor="middle" fill="#4a5568" font-size="8">
                  {{ day.date }}
                </text>
              </g>
              <defs>
                <linearGradient id="barGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#4CAF7D" />
                  <stop offset="100%" stop-color="#2d7a56" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div v-else class="text-center text-slate-600 py-8">
            <i class="ti ti-chart-bar text-3xl block mb-2"></i>
            <p class="text-sm">No data for this period.</p>
          </div>
        </div>

        <!-- Top Tellers -->
        <div class="bg-[#0d1f35] border border-white/5 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-white/5 flex items-center gap-2">
            <i class="ti ti-award text-[#C9A84C]"></i>
            <h3 class="text-sm font-bold text-white">Top Tellers by Volume</h3>
          </div>
          <table class="w-full text-sm">
            <thead class="bg-white/[0.02]">
              <tr class="text-[11px] text-slate-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left w-10">#</th>
                <th class="px-4 py-3 text-left">Name</th>
                <th class="px-4 py-3 text-left">Staff ID</th>
                <th class="px-4 py-3 text-center">Transactions</th>
                <th class="px-4 py-3 text-right">Volume</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.03]">
              <tr v-for="(t, i) in top_tellers" :key="i" class="hover:bg-white/[0.02] transition">
                <td class="px-6 py-3.5">
                  <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                        :class="i === 0 ? 'bg-[rgba(201,168,76,0.2)] text-[#C9A84C]' : i === 1 ? 'bg-slate-700 text-slate-300' : 'bg-slate-800 text-slate-500'">
                    {{ i + 1 }}
                  </span>
                </td>
                <td class="px-4 py-3.5 font-semibold text-white">{{ t.full_name }}</td>
                <td class="px-4 py-3.5 font-mono text-xs text-slate-400">{{ t.ident_number }}</td>
                <td class="px-4 py-3.5 text-center text-slate-300">{{ fmtN(t.cnt) }}</td>
                <td class="px-4 py-3.5 text-right font-bold text-[#4CAF7D]">${{ fmt(t.total) }}</td>
              </tr>
              <tr v-if="!top_tellers.length">
                <td colspan="5" class="px-6 py-10 text-center text-slate-600">No teller activity in this period.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Largest Transactions -->
        <div class="bg-[#0d1f35] border border-white/5 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-white/5 flex items-center gap-2">
            <i class="ti ti-arrows-sort text-slate-400"></i>
            <h3 class="text-sm font-bold text-white">Largest Transactions</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-white/[0.02]">
                <tr class="text-[11px] text-slate-500 uppercase tracking-wider">
                  <th class="px-6 py-3 text-left">Reference</th>
                  <th class="px-4 py-3 text-left">Customer</th>
                  <th class="px-4 py-3 text-left">Type</th>
                  <th class="px-4 py-3 text-right">Amount</th>
                  <th class="px-4 py-3 text-center">Status</th>
                  <th class="px-4 py-3 text-left">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/[0.03]">
                <tr v-for="txn in largest_txns" :key="txn.id" class="hover:bg-white/[0.02] transition">
                  <td class="px-6 py-3.5 font-mono text-xs text-slate-300">{{ txn.reference }}</td>
                  <td class="px-4 py-3.5 text-xs text-slate-300">{{ txn.primary_account?.customer?.full_name ?? '—' }}</td>
                  <td class="px-4 py-3.5">
                    <span class="text-[10px] px-2 py-1 rounded-lg font-bold uppercase"
                          :class="getType(txn.type).badge">
                      {{ txn.type?.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 text-right font-bold text-white">${{ fmt(txn.amount) }}</td>
                  <td class="px-4 py-3.5 text-center">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase"
                          :class="txn.status === 'completed'
                            ? 'bg-emerald-900/40 text-emerald-400'
                            : 'bg-amber-900/40 text-amber-400'">
                      {{ txn.status?.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 text-xs text-slate-500">{{ fmtDate(txn.created_at) }}</td>
                </tr>
                <tr v-if="!largest_txns.length">
                  <td colspan="6" class="px-6 py-10 text-center text-slate-600">No transactions in this period.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

  </AuthenticatedLayout>

  <!-- ═══════════════════════════════════════════════════════════
       PRINT VIEW — teleported to <body> so it sits outside #app
       completely avoiding any layout overlap
  ═══════════════════════════════════════════════════════════ -->
  <Teleport to="body">
    <div id="print-area" style="display:none; font-family:Arial,Helvetica,sans-serif; font-size:11px; line-height:1.5; color:#1e293b; background:white; padding:0; margin:0;">

      <!-- Letterhead -->
      <div style="display:flex; align-items:flex-start; justify-content:space-between; padding-bottom:14px; border-bottom:3px solid #1a3a5c; margin-bottom:20px; gap:16px;">
        <!-- Left: logo + bank name -->
        <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
          <img src="/images/MAin Logo.png" alt="Gobaad Bank" style="height:44px; width:auto; display:block;" />
          <div>
            <div style="font-size:19px; font-weight:800; color:#1a3a5c; letter-spacing:-0.3px; white-space:nowrap;">Gobaad Bank</div>
            <div style="font-size:9px; color:#64748b; letter-spacing:0.2em; text-transform:uppercase; margin-top:2px; white-space:nowrap;">Branch Performance Report</div>
          </div>
        </div>
        <!-- Right: branch + period -->
        <div style="text-align:right; flex-shrink:0;">
          <div style="font-size:14px; font-weight:800; color:#1a3a5c; white-space:nowrap;">{{ branch_name }}</div>
          <div v-if="swift_code" style="font-size:9px; color:#1a3a5c; font-weight:700; margin-top:2px; white-space:nowrap; font-family:monospace;">SWIFT: {{ swift_code }}</div>
          <div style="font-size:10px; color:#475569; margin-top:3px; white-space:nowrap;">{{ fmtDateLong(date_from) }} — {{ fmtDateLong(date_to) }}</div>
          <div style="font-size:9px; color:#94a3b8; margin-top:2px; white-space:nowrap;">Generated: {{ nowStr }}</div>
        </div>
      </div>

      <!-- KPI Summary Grid -->
      <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px;">
        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:14px; background:#f8fafc;">
          <div style="font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700; margin-bottom:6px;">Total Transactions</div>
          <div style="font-size:26px; font-weight:800; color:#1a3a5c;">{{ fmtN(summary.total_transactions) }}</div>
        </div>
        <div style="border:1px solid #bbf7d0; border-radius:8px; padding:14px; background:#f0fdf4;">
          <div style="font-size:9px; color:#16a34a; text-transform:uppercase; letter-spacing:0.1em; font-weight:700; margin-bottom:6px;">Total Volume</div>
          <div style="font-size:26px; font-weight:800; color:#15803d;">${{ fmt(summary.total_volume) }}</div>
        </div>
        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:14px; background:#f8fafc;">
          <div style="font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700; margin-bottom:6px;">Average Transaction</div>
          <div style="font-size:26px; font-weight:800; color:#1a3a5c;">${{ fmt(summary.avg_amount) }}</div>
        </div>
        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:14px; background:#f8fafc;">
          <div style="font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700; margin-bottom:6px;">Completed</div>
          <div style="font-size:26px; font-weight:800; color:#1a3a5c;">{{ fmtN(summary.completed) }}</div>
        </div>
        <div style="border:1px solid #fef3c7; border-radius:8px; padding:14px; background:#fffbeb;">
          <div style="font-size:9px; color:#92400e; text-transform:uppercase; letter-spacing:0.1em; font-weight:700; margin-bottom:6px;">Pending Approval</div>
          <div style="font-size:26px; font-weight:800; color:#b45309;">{{ fmtN(summary.pending) }}</div>
        </div>
        <div style="border:1px solid #fee2e2; border-radius:8px; padding:14px; background:#fff5f5;">
          <div style="font-size:9px; color:#991b1b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700; margin-bottom:6px;">Flagged</div>
          <div style="font-size:26px; font-weight:800; color:#dc2626;">{{ fmtN(summary.flagged) }}</div>
        </div>
      </div>

      <!-- Vault Balance -->
      <div v-if="vault_balance !== null"
           style="border:2px solid #1a3a5c; border-radius:8px; padding:14px 18px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; background:#f0f6ff;">
        <div>
          <div style="font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.15em; font-weight:700;">Current Vault Balance</div>
          <div style="font-size:28px; font-weight:800; color:#1a3a5c; margin-top:4px;">${{ fmt(vault_balance) }}</div>
        </div>
        <div style="text-align:right; font-size:10px; color:#94a3b8;">As of {{ nowStr }}</div>
      </div>

      <!-- Type Breakdown -->
      <div style="margin-bottom:18px;">
        <div style="font-size:12px; font-weight:700; color:#1a3a5c; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid #e2e8f0;">
          Transaction Breakdown by Type
        </div>
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="background:#f1f5f9;">
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Type</th>
              <th style="padding:7px 10px; text-align:center; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Count</th>
              <th style="padding:7px 10px; text-align:right; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Volume</th>
              <th style="padding:7px 10px; text-align:right; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Share</th>
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; font-weight:700;" width="120">Distribution</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in typeWithPct" :key="row.type"
                :style="i % 2 === 0 ? 'background:white;' : 'background:#f8fafc;'">
              <td style="padding:8px 10px; font-weight:600; text-transform:capitalize;">{{ row.type?.replace('_', ' ') }}</td>
              <td style="padding:8px 10px; text-align:center; color:#475569;">{{ fmtN(row.count) }}</td>
              <td style="padding:8px 10px; text-align:right; font-weight:700; color:#15803d;">${{ fmt(row.total) }}</td>
              <td style="padding:8px 10px; text-align:right; color:#475569;">{{ row.pct }}%</td>
              <td style="padding:8px 10px;">
                <div style="height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
                  <div :style="{ width: row.pct + '%', height: '100%', borderRadius:'4px', backgroundColor: getType(row.type).color }"></div>
                </div>
              </td>
            </tr>
            <tr v-if="!typeWithPct.length">
              <td colspan="5" style="padding:14px; text-align:center; color:#94a3b8;">No data.</td>
            </tr>
          </tbody>
          <tfoot>
            <tr style="background:#f1f5f9; border-top:2px solid #e2e8f0;">
              <td style="padding:8px 10px; font-weight:700;">Total</td>
              <td style="padding:8px 10px; text-align:center; font-weight:700;">{{ fmtN(summary.total_transactions) }}</td>
              <td style="padding:8px 10px; text-align:right; font-weight:800; color:#15803d;">${{ fmt(summary.total_volume) }}</td>
              <td style="padding:8px 10px; text-align:right; font-weight:700;">100%</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Top Tellers -->
      <div style="margin-bottom:18px;">
        <div style="font-size:12px; font-weight:700; color:#1a3a5c; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid #e2e8f0;">
          Top Tellers by Volume
        </div>
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="background:#f1f5f9;">
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">#</th>
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Teller Name</th>
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Staff ID</th>
              <th style="padding:7px 10px; text-align:center; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Transactions</th>
              <th style="padding:7px 10px; text-align:right; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Volume</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(t, i) in top_tellers" :key="i"
                :style="i % 2 === 0 ? 'background:white;' : 'background:#f8fafc;'">
              <td style="padding:8px 10px; font-weight:700; color:#94a3b8;">{{ i + 1 }}</td>
              <td style="padding:8px 10px; font-weight:600;">{{ t.full_name }}</td>
              <td style="padding:8px 10px; font-family:monospace; color:#64748b; font-size:10px;">{{ t.ident_number }}</td>
              <td style="padding:8px 10px; text-align:center; color:#475569;">{{ fmtN(t.cnt) }}</td>
              <td style="padding:8px 10px; text-align:right; font-weight:700; color:#15803d;">${{ fmt(t.total) }}</td>
            </tr>
            <tr v-if="!top_tellers.length">
              <td colspan="5" style="padding:14px; text-align:center; color:#94a3b8;">No teller activity in this period.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Largest Transactions -->
      <div style="margin-bottom:24px;">
        <div style="font-size:12px; font-weight:700; color:#1a3a5c; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid #e2e8f0;">
          Largest Transactions
        </div>
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="background:#f1f5f9;">
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Reference</th>
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Customer</th>
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Type</th>
              <th style="padding:7px 10px; text-align:right; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Amount</th>
              <th style="padding:7px 10px; text-align:center; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Status</th>
              <th style="padding:7px 10px; text-align:left; font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; font-weight:700;">Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(txn, i) in largest_txns" :key="txn.id"
                :style="i % 2 === 0 ? 'background:white;' : 'background:#f8fafc;'">
              <td style="padding:7px 10px; font-family:monospace; font-size:10px; color:#475569;">{{ txn.reference }}</td>
              <td style="padding:7px 10px; color:#334155;">{{ txn.primary_account?.customer?.full_name ?? '—' }}</td>
              <td style="padding:7px 10px; text-transform:capitalize; color:#475569;">{{ txn.type?.replace('_', ' ') }}</td>
              <td style="padding:7px 10px; text-align:right; font-weight:700; color:#15803d;">${{ fmt(txn.amount) }}</td>
              <td style="padding:7px 10px; text-align:center;">
                <span :style="txn.status === 'completed'
                  ? 'background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:20px; font-size:9px; font-weight:700; text-transform:uppercase;'
                  : 'background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:20px; font-size:9px; font-weight:700; text-transform:uppercase;'">
                  {{ txn.status?.replace('_', ' ') }}
                </span>
              </td>
              <td style="padding:7px 10px; color:#64748b;">{{ fmtDate(txn.created_at) }}</td>
            </tr>
            <tr v-if="!largest_txns.length">
              <td colspan="6" style="padding:14px; text-align:center; color:#94a3b8;">No transactions in this period.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div style="border-top:2px solid #1a3a5c; padding-top:12px; display:flex; align-items:center; justify-content:space-between; color:#94a3b8; font-size:9px;">
        <div>
          <span style="font-weight:700; color:#1a3a5c;">Gobaad Bank</span> · {{ branch_name }}<template v-if="swift_code"> · SWIFT: {{ swift_code }}</template> · CONFIDENTIAL
        </div>
        <div>Report Period: {{ fmtDateLong(date_from) }} — {{ fmtDateLong(date_to) }}</div>
        <div>Generated: {{ nowStr }}</div>
      </div>

    </div>
  </Teleport>

</template>

<style>
@media print {
  /* Hide the entire Vue app — #print-area is teleported outside it */
  #app {
    display: none !important;
  }

  /* Show only the print area and let it flow naturally */
  #print-area {
    display: block !important;
    width: 100% !important;
    background: white !important;
  }

  @page {
    size: A4 landscape;
    margin: 10mm 14mm;
  }

  body {
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
  }
}
</style>
