<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { reactive, computed, watch, ref } from 'vue'

const props = defineProps({
    account:       Object,
    entries:       Object,  // Paginated
    filters:       Object,
    total_credits: { type: Number, default: 0 },
    total_debits:  { type: Number, default: 0 },
})

const f = reactive({
    type:       props.filters.type       || '',
    date_from:  props.filters.date_from  || '',
    date_to:    props.filters.date_to    || '',
    min_amount: props.filters.min_amount || '',
    max_amount: props.filters.max_amount || '',
    sort_by:    props.filters.sort_by    || 'created_at',
    sort_dir:   props.filters.sort_dir   || 'desc',
})

/* ── Debounced reactive filter → server ── */
let timer = null
watch(f, () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get(route('staff.account.transactions', props.account.id), { ...f }, {
            preserveState: true,
            replace: true,
        })
    }, 350)
})

/* ── Sort helpers ── */
function setSort(col) {
    if (f.sort_by === col) {
        f.sort_dir = f.sort_dir === 'desc' ? 'asc' : 'desc'
    } else {
        f.sort_by  = col
        f.sort_dir = 'desc'
    }
}

function sortIcon(col) {
    if (f.sort_by !== col) return 'ti-arrows-sort'
    return f.sort_dir === 'desc' ? 'ti-sort-descending' : 'ti-sort-ascending'
}

function sortActive(col) {
    return f.sort_by === col
}

/* ── Quick presets ── */
function applyPreset(preset) {
    const now = new Date()
    const y   = now.getFullYear()
    const mo  = now.getMonth()
    const pad = n => String(n).padStart(2, '0')
    const lastDay = (yr2, mo2) => new Date(yr2, mo2 + 1, 0).getDate()
    const today   = now.toISOString().slice(0, 10)

    if (preset === 'this_month') {
        f.date_from = `${y}-${pad(mo + 1)}-01`;  f.date_to = today
    } else if (preset === 'last_month') {
        const lm = mo === 0 ? 11 : mo - 1
        const ly = mo === 0 ? y - 1 : y
        f.date_from = `${ly}-${pad(lm + 1)}-01`; f.date_to = `${ly}-${pad(lm + 1)}-${lastDay(ly, lm)}`
    } else if (preset === 'last_3') {
        const d = new Date(y, mo - 2, 1)
        f.date_from = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`; f.date_to = today
    } else if (preset === 'this_year') {
        f.date_from = `${y}-01-01`; f.date_to = today
    }
}

/* ── Filters active? ── */
const isActive = computed(() =>
    Object.entries(f).some(([k, v]) => v && !(k === 'sort_by' && v === 'created_at') && !(k === 'sort_dir' && v === 'desc'))
)

function clearFilters() {
    Object.assign(f, { type: '', date_from: '', date_to: '', min_amount: '', max_amount: '', sort_by: 'created_at', sort_dir: 'desc' })
}

/* ── Formatting ── */
const fmt  = n => '$' + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })
const fmtDate = d => new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })

/* ───────────────────────────────────────────────────────
 * Statement Export Modal
 * ─────────────────────────────────────────────────────── */
const showStatement = ref(false)

/* Statement-specific date range (separate from the filter state) */
const stmt = reactive({
    date_from: '',
    date_to:   '',
})

const stmtPresets = [
    { key: 'this_month',  label: 'This Month'    },
    { key: 'last_month',  label: 'Last Month'    },
    { key: 'last_3',      label: 'Last 3 Months' },
    { key: 'last_6',      label: 'Last 6 Months' },
    { key: 'this_year',   label: 'This Year'     },
    { key: 'all_time',    label: 'All Time'       },
]

const activePreset = ref('')

function applyStmtPreset(key) {
    const now  = new Date()
    const y    = now.getFullYear()
    const mo   = now.getMonth()
    const pad  = n => String(n).padStart(2, '0')
    const last = (yr, m) => new Date(yr, m + 1, 0).getDate()
    const today = now.toISOString().slice(0, 10)

    activePreset.value = key

    if (key === 'this_month') {
        stmt.date_from = `${y}-${pad(mo + 1)}-01`
        stmt.date_to   = today
    } else if (key === 'last_month') {
        const lm = mo === 0 ? 11 : mo - 1
        const ly = mo === 0 ? y - 1 : y
        stmt.date_from = `${ly}-${pad(lm + 1)}-01`
        stmt.date_to   = `${ly}-${pad(lm + 1)}-${last(ly, lm)}`
    } else if (key === 'last_3') {
        const d = new Date(y, mo - 2, 1)
        stmt.date_from = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`
        stmt.date_to   = today
    } else if (key === 'last_6') {
        const d = new Date(y, mo - 5, 1)
        stmt.date_from = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`
        stmt.date_to   = today
    } else if (key === 'this_year') {
        stmt.date_from = `${y}-01-01`
        stmt.date_to   = today
    } else if (key === 'all_time') {
        stmt.date_from = ''
        stmt.date_to   = ''
    }
}

function openStatement() {
    /* Pre-fill with the current filter range if set, else default to this month */
    if (f.date_from || f.date_to) {
        stmt.date_from = f.date_from
        stmt.date_to   = f.date_to
        activePreset.value = ''
    } else {
        applyStmtPreset('this_month')
    }
    showStatement.value = true
}

const stmtUrl = computed(() =>
    route('staff.account.statement', {
        id:        props.account.id,
        date_from: stmt.date_from || undefined,
        date_to:   stmt.date_to   || undefined,
    })
)

const stmtRangeLabel = computed(() => {
    if (!stmt.date_from && !stmt.date_to) return 'All transactions'
    if (stmt.date_from && stmt.date_to)   return `${stmt.date_from} → ${stmt.date_to}`
    if (stmt.date_from)                   return `From ${stmt.date_from}`
    return `Up to ${stmt.date_to}`
})

/* Reset activePreset if the user manually edits the dates */
watch(() => [stmt.date_from, stmt.date_to], () => { activePreset.value = '' })
</script>

<template>
    <Head :title="`Transactions — ${account.account_number}`" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-7 space-y-5">

            <!-- ── Header ── -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('staff.customer-care.profile', account.customer?.id)"
                          class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700
                                 flex items-center justify-center text-slate-400 hover:text-white transition-all">
                        <i class="ti ti-arrow-left text-base"></i>
                    </Link>
                    <div class="w-10 h-10 rounded-xl bg-[#C9A84C]/10 border border-[#C9A84C]/20 flex items-center justify-center">
                        <i class="ti ti-history text-[#C9A84C] text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Transaction History</h1>
                        <div class="flex flex-wrap gap-3 mt-0.5 text-xs text-slate-400">
                            <span class="font-mono text-[#C9A84C]">{{ account.account_number }}</span>
                            <span>{{ account.customer?.full_name }}</span>
                            <span>{{ account.account_type?.type_name }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-[10px] text-slate-500 uppercase">Balance</p>
                        <p class="text-xl font-bold text-[#C9A84C] font-mono">{{ fmt(account.balance) }}</p>
                    </div>
                    <button @click="openStatement"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#C9A84C]/10 text-[#C9A84C] border border-[#C9A84C]/20
                                   text-sm font-semibold hover:bg-[#C9A84C]/20 transition">
                        <i class="ti ti-file-download"></i> Statement
                    </button>
                </div>
            </div>

            <!-- ── Totals strip ── -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3 text-center">
                    <p class="text-[10px] text-slate-500 uppercase mb-1">Total Credits (page)</p>
                    <p class="text-base font-bold text-emerald-400 font-mono">+{{ fmt(total_credits) }}</p>
                </div>
                <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3 text-center">
                    <p class="text-[10px] text-slate-500 uppercase mb-1">Total Debits (page)</p>
                    <p class="text-base font-bold text-red-400 font-mono">-{{ fmt(total_debits) }}</p>
                </div>
                <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3 text-center">
                    <p class="text-[10px] text-slate-500 uppercase mb-1">Net (page)</p>
                    <p class="text-base font-bold font-mono"
                       :class="(total_credits - total_debits) >= 0 ? 'text-emerald-400' : 'text-red-400'">
                        {{ (total_credits - total_debits) >= 0 ? '+' : '' }}{{ fmt(total_credits - total_debits) }}
                    </p>
                </div>
            </div>

            <!-- ── Filters ── -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide flex items-center gap-2">
                        <i class="ti ti-filter text-[#C9A84C]"></i> Filters & Sort
                    </p>
                    <button v-if="isActive" @click="clearFilters"
                            class="flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition">
                        <i class="ti ti-x text-xs"></i> Clear all
                    </button>
                </div>

                <!-- Quick date presets -->
                <div class="flex flex-wrap gap-2">
                    <button v-for="p in [
                        {key:'this_month', label:'This Month'},
                        {key:'last_month', label:'Last Month'},
                        {key:'last_3',     label:'Last 3 Months'},
                        {key:'this_year',  label:'This Year'},
                    ]" :key="p.key" @click="applyPreset(p.key)"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 border border-slate-700
                                   text-slate-300 hover:bg-[#C9A84C]/10 hover:border-[#C9A84C]/30 hover:text-[#C9A84C] transition-colors">
                        {{ p.label }}
                    </button>
                </div>

                <!-- Filter row -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <!-- Type -->
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">Type</label>
                        <select v-model="f.type"
                                class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                       focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors">
                            <option value="">All</option>
                            <option value="credit">Credit (In)</option>
                            <option value="debit">Debit (Out)</option>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">From Date</label>
                        <input v-model="f.date_from" type="date"
                               class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                      focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors" />
                    </div>

                    <!-- To Date -->
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">To Date</label>
                        <input v-model="f.date_to" type="date"
                               class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                      focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors" />
                    </div>

                    <!-- Min Amount -->
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">Min Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs">$</span>
                            <input v-model="f.min_amount" type="number" min="0" step="0.01" placeholder="0.00"
                                   class="w-full bg-slate-800 border border-slate-600 rounded-xl pl-6 pr-3 py-2.5 text-sm text-white
                                          focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors" />
                        </div>
                    </div>

                    <!-- Max Amount -->
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">Max Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs">$</span>
                            <input v-model="f.max_amount" type="number" min="0" step="0.01" placeholder="∞"
                                   class="w-full bg-slate-800 border border-slate-600 rounded-xl pl-6 pr-3 py-2.5 text-sm text-white
                                          focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors" />
                        </div>
                    </div>

                    <!-- Sort Dir quick toggle -->
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">Sort Order</label>
                        <button @click="f.sort_dir = f.sort_dir === 'desc' ? 'asc' : 'desc'"
                                class="w-full flex items-center justify-between gap-2 bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5
                                       text-sm text-white hover:border-[#C9A84C]/60 transition-colors">
                            <span>{{ f.sort_dir === 'desc' ? 'Newest first' : 'Oldest first' }}</span>
                            <i :class="f.sort_dir === 'desc' ? 'ti ti-sort-descending' : 'ti ti-sort-ascending'"
                               class="text-[#C9A84C] text-base"></i>
                        </button>
                    </div>
                </div>

                <!-- Active filter chips -->
                <div v-if="isActive" class="flex flex-wrap gap-2 pt-1">
                    <span v-if="f.type"
                          class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-indigo-900/30 border border-indigo-700/30 text-indigo-300">
                        <i class="ti ti-tag text-xs"></i>{{ f.type === 'credit' ? 'Credit only' : 'Debit only' }}
                        <button @click="f.type = ''" class="hover:text-white ml-0.5"><i class="ti ti-x text-[10px]"></i></button>
                    </span>
                    <span v-if="f.date_from || f.date_to"
                          class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-[#C9A84C]/10 border border-[#C9A84C]/20 text-[#C9A84C]">
                        <i class="ti ti-calendar text-xs"></i>
                        {{ f.date_from || '…' }} → {{ f.date_to || 'today' }}
                        <button @click="f.date_from = ''; f.date_to = ''" class="hover:text-white ml-0.5"><i class="ti ti-x text-[10px]"></i></button>
                    </span>
                    <span v-if="f.min_amount || f.max_amount"
                          class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-emerald-900/20 border border-emerald-700/30 text-emerald-300">
                        <i class="ti ti-currency-dollar text-xs"></i>
                        {{ f.min_amount ? '$'+f.min_amount : '$0' }} – {{ f.max_amount ? '$'+f.max_amount : '∞' }}
                        <button @click="f.min_amount = ''; f.max_amount = ''" class="hover:text-white ml-0.5"><i class="ti ti-x text-[10px]"></i></button>
                    </span>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-400">
                        <span class="font-semibold text-white">{{ entries.total }}</span>
                        entr{{ entries.total === 1 ? 'y' : 'ies' }} found
                    </p>
                    <p class="text-xs text-slate-500">
                        Sorted by
                        <span class="text-slate-300 font-medium">{{
                            f.sort_by === 'created_at' ? 'Date' :
                            f.sort_by === 'amount'     ? 'Amount' : 'Balance After'
                        }}</span>
                        <span class="ml-1">{{ f.sort_dir === 'desc' ? '↓' : '↑' }}</span>
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-800/60">
                            <tr class="text-xs text-slate-400 uppercase tracking-wide">
                                <!-- Sortable: Date -->
                                <th class="px-5 py-3 text-left">
                                    <button @click="setSort('created_at')"
                                            :class="sortActive('created_at') ? 'text-[#C9A84C]' : 'text-slate-400 hover:text-white'"
                                            class="flex items-center gap-1.5 font-bold uppercase text-xs tracking-wide transition-colors">
                                        Date & Time
                                        <i :class="'ti ' + sortIcon('created_at') + ' text-sm'"></i>
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-left font-bold">Type</th>
                                <th class="px-4 py-3 text-left font-bold">Reference</th>
                                <!-- Sortable: Amount -->
                                <th class="px-4 py-3 text-right">
                                    <button @click="setSort('amount')"
                                            :class="sortActive('amount') ? 'text-[#C9A84C]' : 'text-slate-400 hover:text-white'"
                                            class="flex items-center gap-1.5 font-bold uppercase text-xs tracking-wide transition-colors ml-auto">
                                        Amount
                                        <i :class="'ti ' + sortIcon('amount') + ' text-sm'"></i>
                                    </button>
                                </th>
                                <!-- Sortable: Balance After -->
                                <th class="px-5 py-3 text-right">
                                    <button @click="setSort('balance_after')"
                                            :class="sortActive('balance_after') ? 'text-[#C9A84C]' : 'text-slate-400 hover:text-white'"
                                            class="flex items-center gap-1.5 font-bold uppercase text-xs tracking-wide transition-colors ml-auto">
                                        Balance After
                                        <i :class="'ti ' + sortIcon('balance_after') + ' text-sm'"></i>
                                    </button>
                                </th>
                                <th class="px-5 py-3 text-right font-bold text-xs uppercase tracking-wide text-slate-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr v-for="entry in entries.data" :key="entry.id"
                                class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3 text-xs text-slate-400 whitespace-nowrap">{{ fmtDate(entry.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="entry.entry_type === 'credit' ? 'text-emerald-400 bg-emerald-900/20 border-emerald-700/30'
                                                                                : 'text-red-400 bg-red-900/20 border-red-700/30'"
                                          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border">
                                        <i :class="entry.entry_type === 'credit' ? 'ti ti-arrow-down-left' : 'ti ti-arrow-up-right'"></i>
                                        {{ entry.entry_type === 'credit' ? 'Credit' : 'Debit' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-400">
                                    {{ entry.transaction?.reference ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold"
                                    :class="entry.entry_type === 'credit' ? 'text-emerald-400' : 'text-red-400'">
                                    {{ entry.entry_type === 'credit' ? '+' : '-' }}{{ fmt(entry.amount) }}
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-white text-sm">
                                    {{ fmt(entry.balance_after) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a v-if="entry.transaction?.id"
                                       :href="route('staff.transaction.receipt', entry.transaction.id)"
                                       target="_blank" rel="noopener"
                                       title="Print Receipt"
                                       class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white/5 border border-white/10 text-slate-400 hover:text-[#C9A84C] hover:border-[#C9A84C]/30 hover:bg-[#C9A84C]/8 transition-colors">
                                        <i class="ti ti-receipt text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="!entries.data.length">
                                <td colspan="6" class="px-5 py-14 text-center">
                                    <i class="ti ti-history text-3xl text-slate-600 block mb-3"></i>
                                    <p class="text-sm text-slate-500">No transactions match your filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="entries.last_page > 1"
                     class="px-5 py-4 border-t border-slate-800 flex items-center justify-between bg-slate-800/20">
                    <p class="text-xs text-slate-500">
                        Showing {{ entries.from ?? 0 }}–{{ entries.to ?? 0 }} of {{ entries.total }}
                    </p>
                    <div class="flex gap-1.5">
                        <Link v-for="link in entries.links" :key="link.label"
                              :href="link.url || '#'"
                              v-html="link.label"
                              class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs transition"
                              :class="link.active
                                  ? 'bg-[#C9A84C] border-[#C9A84C] text-[#070F1A] font-bold'
                                  : link.url
                                      ? 'border-slate-700 text-slate-400 hover:bg-slate-800'
                                      : 'border-transparent text-slate-600 cursor-default'" />
                    </div>
                </div>
            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════
             STATEMENT EXPORT MODAL
        ════════════════════════════════════════════════════ -->
        <Teleport to="body">
          <Transition name="stmt-modal">
            <div v-if="showStatement"
                 class="fixed inset-0 z-[9995] flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
                 @click.self="showStatement = false">

              <div class="bg-[#0B1929] border border-[#C9A84C]/20 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden stmt-card">

                <!-- Modal header -->
                <div class="flex items-center justify-between px-7 py-5 border-b border-white/8">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#C9A84C]/10 flex items-center justify-center text-[#C9A84C]">
                      <i class="ti ti-file-analytics text-xl"></i>
                    </div>
                    <div>
                      <h2 class="text-lg font-serif text-[#F0EBE1]">Generate Statement</h2>
                      <p class="text-[11px] text-[#A9B8C6]">{{ account.account_number }} · {{ account.customer?.full_name }}</p>
                    </div>
                  </div>
                  <button @click="showStatement = false"
                          class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 text-[#A9B8C6] hover:text-white transition flex items-center justify-center">
                    <i class="ti ti-x"></i>
                  </button>
                </div>

                <div class="px-7 py-6 space-y-6">

                  <!-- Quick-select presets -->
                  <div>
                    <p class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-3">Quick Range</p>
                    <div class="grid grid-cols-3 gap-2">
                      <button v-for="p in stmtPresets" :key="p.key"
                              @click="applyStmtPreset(p.key)"
                              class="px-3 py-2 rounded-xl text-xs font-semibold border transition-all"
                              :class="activePreset === p.key
                                  ? 'bg-[#C9A84C]/15 border-[#C9A84C]/50 text-[#C9A84C]'
                                  : 'bg-white/3 border-white/8 text-[#A9B8C6] hover:bg-white/6 hover:border-white/15'">
                        {{ p.label }}
                      </button>
                    </div>
                  </div>

                  <!-- Manual date range -->
                  <div>
                    <p class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-3">Custom Date Range</p>
                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <label class="block text-[10px] text-[#6B7E8E] uppercase font-bold tracking-wider mb-1.5">From</label>
                        <input v-model="stmt.date_from" type="date"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-[#F0EBE1]
                                      focus:outline-none focus:border-[#C9A84C]/60 focus:ring-1 focus:ring-[#C9A84C]/20 transition" />
                      </div>
                      <div>
                        <label class="block text-[10px] text-[#6B7E8E] uppercase font-bold tracking-wider mb-1.5">To</label>
                        <input v-model="stmt.date_to" type="date"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-[#F0EBE1]
                                      focus:outline-none focus:border-[#C9A84C]/60 focus:ring-1 focus:ring-[#C9A84C]/20 transition" />
                      </div>
                    </div>
                  </div>

                  <!-- Summary preview -->
                  <div class="bg-[rgba(201,168,76,0.04)] border border-[#C9A84C]/15 rounded-2xl p-4 space-y-3">
                    <p class="text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold">Statement Preview</p>
                    <div class="space-y-2">
                      <div class="flex justify-between text-xs">
                        <span class="text-[#6B7E8E]">Account</span>
                        <span class="text-[#F0EBE1] font-mono font-medium">{{ account.account_number }}</span>
                      </div>
                      <div class="flex justify-between text-xs">
                        <span class="text-[#6B7E8E]">Account Holder</span>
                        <span class="text-[#F0EBE1] font-medium">{{ account.customer?.full_name }}</span>
                      </div>
                      <div class="flex justify-between text-xs">
                        <span class="text-[#6B7E8E]">Account Type</span>
                        <span class="text-[#F0EBE1]">{{ account.account_type?.type_name }}</span>
                      </div>
                      <div class="flex justify-between text-xs">
                        <span class="text-[#6B7E8E]">Current Balance</span>
                        <span class="text-[#C9A84C] font-mono font-bold">{{ fmt(account.balance) }}</span>
                      </div>
                      <div class="border-t border-white/8 pt-2 flex justify-between text-xs">
                        <span class="text-[#6B7E8E]">Date Range</span>
                        <span class="text-[#F0EBE1] font-medium text-right max-w-[60%] truncate">{{ stmtRangeLabel }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Disclaimer -->
                  <p class="text-[10px] text-[#6B7E8E] leading-relaxed">
                    <i class="ti ti-info-circle mr-1"></i>
                    The PDF will open in a new tab. It includes all ledger entries for the selected period, formatted for official use.
                  </p>

                  <!-- Download button -->
                  <a :href="stmtUrl" target="_blank" rel="noopener"
                     @click="showStatement = false"
                     class="flex items-center justify-center gap-2.5 w-full py-4 rounded-2xl bg-[#C9A84C] text-[#070F1A]
                            font-bold text-sm hover:bg-[#D9B85C] active:scale-[0.98] transition-all shadow-lg shadow-[#C9A84C]/10">
                    <i class="ti ti-file-download text-lg"></i>
                    Download PDF Statement
                  </a>
                </div>
              </div>
            </div>
          </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.stmt-modal-enter-active,
.stmt-modal-leave-active {
  transition: opacity 0.2s ease;
}
.stmt-modal-enter-from,
.stmt-modal-leave-to {
  opacity: 0;
}
/* Card slide-up handled non-scoped via animation (scoped can't reach teleport children) */
</style>
<style>
/* Non-scoped so it reaches the teleported DOM */
.stmt-card {
  animation: stmt-card-in 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}
@keyframes stmt-card-in {
  from { opacity: 0; transform: translateY(16px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0)    scale(1);    }
}
</style>
