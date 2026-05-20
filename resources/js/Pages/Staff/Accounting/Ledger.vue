<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { reactive, watch } from 'vue'

const props = defineProps({
    transactions:  { type: Object, default: () => ({ data: [] }) },
    filters:       { type: Object, default: () => ({}) },
    is_restricted: { type: Boolean, default: true },
    branches:      { type: Array,   default: () => [] },
})

/* ── Filters ── */
const f = reactive({
    date_from: props.filters.date_from ?? '',
    date_to:   props.filters.date_to   ?? '',
    type:      props.filters.type      ?? '',
    status:    props.filters.status    ?? '',
})

let debounce = null
watch(f, () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        router.get(route('staff.accounting.ledger'), { ...f }, { preserveState: true, replace: true })
    }, 300)
})

function clearFilters() {
    Object.assign(f, { date_from: '', date_to: '', type: '', status: '' })
}

/* ── Helpers ── */
const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const TYPE_CFG = {
    deposit:    { label: 'Deposit',    icon: 'ti-arrow-down-circle', cls: 'bg-emerald-900/30 text-emerald-400 border-emerald-700/30' },
    withdrawal: { label: 'Withdrawal', icon: 'ti-arrow-up-circle',   cls: 'bg-amber-900/30 text-amber-400 border-amber-700/30' },
    transfer:   { label: 'Transfer',   icon: 'ti-arrows-right-left', cls: 'bg-indigo-900/30 text-indigo-400 border-indigo-700/30' },
}

const STATUS_CFG = {
    completed:        { cls: 'bg-emerald-900/30 text-emerald-400', label: 'Completed' },
    pending_approval: { cls: 'bg-amber-900/30 text-amber-400',     label: 'Pending' },
    rejected:         { cls: 'bg-red-900/30 text-red-400',         label: 'Rejected' },
    cancelled:        { cls: 'bg-slate-700/50 text-slate-400',     label: 'Cancelled' },
}

const ACCT_CFG = {
    vault:    { icon: 'ti-building-bank',  cls: 'text-amber-400',   bg: 'bg-amber-900/20 border-amber-700/30' },
    customer: { icon: 'ti-user-circle',    cls: 'text-sky-400',     bg: 'bg-sky-900/20 border-sky-700/30'   },
    internal: { icon: 'ti-database',       cls: 'text-slate-400',   bg: 'bg-slate-800/40 border-slate-700/30' },
}

function typeCfg(t)   { return TYPE_CFG[t]   ?? TYPE_CFG.deposit }
function statusCfg(s) { return STATUS_CFG[s] ?? { cls: 'bg-slate-700/50 text-slate-400', label: s } }
function acctCfg(t)   { return ACCT_CFG[t]   ?? ACCT_CFG.internal }

const hasFilters = () => f.date_from || f.date_to || f.type || f.status
</script>

<template>
  <Head title="General Ledger" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-7xl mx-auto px-5 py-8 space-y-6">

        <!-- ── Header ── -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[rgba(201,168,76,0.12)] flex items-center justify-center">
              <i class="ti ti-report-money text-[#C9A84C] text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">General <span class="text-[#C9A84C]">Ledger</span></h1>
              <p class="text-xs text-slate-400">Transaction journal — each entry shows its full GL double-posting</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Link :href="route('staff.accounting.trial-balance')"
                  class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[rgba(138,99,210,0.12)] border border-[rgba(138,99,210,0.25)] text-[#8A63D2] text-sm font-medium hover:bg-[rgba(138,99,210,0.22)] transition">
              <i class="ti ti-scale text-base"></i> Trial Balance
            </Link>
          </div>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap gap-3 items-center">
          <input v-model="f.date_from" type="date"
                 class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[#C9A84C] transition" />
          <input v-model="f.date_to" type="date"
                 class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[#C9A84C] transition" />
          <select v-model="f.type"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[#C9A84C] transition">
            <option value="">All Types</option>
            <option value="deposit">Deposit</option>
            <option value="withdrawal">Withdrawal</option>
            <option value="transfer">Transfer</option>
          </select>
          <select v-model="f.status"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[#C9A84C] transition">
            <option value="">All Statuses</option>
            <option value="completed">Completed</option>
            <option value="pending_approval">Pending</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <button v-if="hasFilters()" @click="clearFilters"
                  class="px-3 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-sm rounded-xl transition">
            Clear
          </button>
          <span class="ml-auto text-xs text-slate-500">{{ transactions.total ?? 0 }} transactions</span>
        </div>

        <!-- ── Transaction list ── -->
        <div class="space-y-3">

          <div v-if="!transactions.data?.length"
               class="py-20 text-center text-slate-500 bg-slate-900/50 border border-slate-800 rounded-2xl">
            <i class="ti ti-database-off text-4xl block mb-3 opacity-40"></i>
            <p>No transactions found for the selected filters.</p>
          </div>

          <!-- Each transaction = one GL group -->
          <div v-for="txn in transactions.data" :key="txn.id"
               class="bg-[#0d1f35] border border-white/5 rounded-2xl overflow-hidden transition hover:border-[#C9A84C]/20">

            <!-- Transaction header row -->
            <div class="flex flex-wrap items-center gap-3 px-5 py-3.5 border-b border-white/5 bg-slate-900/30">
              <!-- Type badge -->
              <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase px-2.5 py-1 rounded-full border"
                    :class="typeCfg(txn.type).cls">
                <i class="ti text-xs" :class="typeCfg(txn.type).icon"></i>
                {{ txn.type_label }}
              </span>

              <!-- Reference -->
              <span class="font-mono text-[#C9A84C] text-xs font-bold tracking-wider">{{ txn.reference }}</span>

              <!-- Status -->
              <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold uppercase"
                    :class="statusCfg(txn.status).cls">
                {{ statusCfg(txn.status).label }}
              </span>

              <!-- Reversed / Flagged flags -->
              <span v-if="txn.is_reversed"
                    class="text-[9px] px-2 py-0.5 rounded-full bg-red-900/40 text-red-400 border border-red-700/30 font-bold uppercase">
                REVERSED
              </span>
              <span v-if="txn.is_flagged"
                    class="text-[9px] px-2 py-0.5 rounded-full bg-orange-900/40 text-orange-400 border border-orange-700/30 font-bold uppercase">
                FLAGGED
              </span>

              <div class="ml-auto flex items-center gap-4">
                <!-- Amount -->
                <span class="text-white font-bold font-mono text-sm">${{ fmt(txn.amount) }}</span>
                <!-- Date -->
                <span class="text-slate-500 text-[11px]">{{ txn.created_at }}</span>
                <!-- GL detail link -->
                <Link :href="route('staff.accounting.gl-detail', txn.id)"
                      class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[rgba(201,168,76,0.08)] hover:bg-[rgba(201,168,76,0.18)] border border-[rgba(201,168,76,0.2)] text-[#C9A84C] text-[11px] font-semibold transition">
                  <i class="ti ti-book-2 text-xs"></i> GL Entry
                </Link>
              </div>
            </div>

            <!-- GL entries grid (Debit | Credit) -->
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-white/5">

              <!-- Entry columns -->
              <div v-for="entry in txn.entries" :key="entry.id"
                   class="px-5 py-4 flex items-start gap-3">

                <!-- Account type icon -->
                <div class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0 mt-0.5"
                     :class="acctCfg(entry.account_type).bg">
                  <i class="ti text-sm" :class="[acctCfg(entry.account_type).icon, acctCfg(entry.account_type).cls]"></i>
                </div>

                <div class="flex-1 min-w-0">
                  <!-- Entry type label -->
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded"
                          :class="entry.entry_type === 'debit'
                            ? 'bg-red-900/40 text-red-400'
                            : 'bg-emerald-900/40 text-emerald-400'">
                      {{ entry.entry_type === 'debit' ? 'DR' : 'CR' }}
                    </span>
                    <span class="text-[10px] text-slate-400">{{ entry.account_label }}</span>
                  </div>

                  <!-- Account number -->
                  <p class="font-mono text-[11px] text-slate-300 truncate">{{ entry.account_number }}</p>
                  <!-- Account holder -->
                  <p class="text-[11px] text-slate-400 mt-0.5">{{ entry.account_holder }}</p>
                </div>

                <!-- Amount + Balance -->
                <div class="text-right shrink-0">
                  <p class="text-sm font-bold font-mono"
                     :class="entry.entry_type === 'debit' ? 'text-red-400' : 'text-emerald-400'">
                    {{ entry.entry_type === 'debit' ? '−' : '+' }}${{ fmt(entry.amount) }}
                  </p>
                  <p class="text-[10px] text-slate-500 mt-0.5">Bal: ${{ fmt(entry.balance_after) }}</p>
                </div>
              </div>

              <!-- Placeholder if no entries posted yet (pending) -->
              <div v-if="!txn.entries?.length" class="col-span-2 px-5 py-4 text-center text-slate-600 text-xs">
                <i class="ti ti-clock-hour-4 mr-1"></i> GL entries not yet posted — awaiting approval
              </div>
            </div>

            <!-- Teller + description footer -->
            <div v-if="txn.teller || txn.description"
                 class="px-5 py-2.5 border-t border-white/5 bg-slate-900/20 flex flex-wrap items-center gap-4 text-[11px] text-slate-500">
              <span v-if="txn.teller">
                <i class="ti ti-user text-[10px] mr-1 text-slate-600"></i>
                Recorded by <span class="text-slate-400 font-medium">{{ txn.teller }}</span>
                <span v-if="txn.teller_role"> · {{ txn.teller_role }}</span>
              </span>
              <span v-if="txn.description && txn.description !== txn.type_label" class="italic text-slate-600">
                "{{ txn.description }}"
              </span>
            </div>
          </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="transactions.last_page > 1"
             class="flex items-center justify-between text-xs text-slate-400">
          <span>Showing {{ transactions.from }}–{{ transactions.to }} of {{ transactions.total }}</span>
          <div class="flex gap-1">
            <Link v-if="transactions.prev_page_url" :href="transactions.prev_page_url"
                  class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 transition">← Prev</Link>
            <Link v-if="transactions.next_page_url" :href="transactions.next_page_url"
                  class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 transition">Next →</Link>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
