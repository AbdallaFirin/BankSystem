<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, reactive, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    transactions: { type: Object, default: () => ({}) },
    filters:      { type: Object, default: () => ({}) },
})

const filters = reactive({
    search:     props.filters.search     ?? '',
    is_flagged: props.filters.is_flagged ?? '',
    type:       props.filters.type       ?? '',
    date_from:  props.filters.date_from  ?? '',
    date_to:    props.filters.date_to    ?? '',
})

let debounceTimer = null
watch(filters, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 350)
})

function applyFilters() {
    router.get(route('staff.compliance.transactions'), { ...filters }, { preserveState: true, replace: true })
}

function clearFilters() {
    Object.assign(filters, { search: '', is_flagged: '', type: '', date_from: '', date_to: '' })
    router.get(route('staff.compliance.transactions'), {}, { preserveState: true, replace: true })
}

/* ── Flag / Unflag ── */
const flagTarget  = ref(null)
const showFlagModal = ref(false)
const flagForm    = useForm({ flag_reason: '' })
const unflagForm  = useForm({})

function openFlag(txn) {
    flagTarget.value      = txn
    flagForm.flag_reason  = ''
    showFlagModal.value   = true
}

function submitFlag() {
    flagForm.post(route('staff.compliance.flag', flagTarget.value.id), {
        onSuccess: () => { showFlagModal.value = false },
    })
}

function unflag(txn) {
    if (confirm(`Remove flag from ${txn.reference}?`)) {
        unflagForm.post(route('staff.compliance.unflag', txn.id))
    }
}

const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const txnTypeColor = (type) => ({
    deposit:    'text-emerald-400',
    withdrawal: 'text-red-400',
    transfer:   'text-blue-400',
}[type] ?? 'text-slate-400')

const statusBadge = (s) => ({
    completed:        'bg-emerald-900/40 text-emerald-400',
    pending_approval: 'bg-amber-900/40   text-amber-400',
    cancelled:        'bg-red-900/40     text-red-400',
}[s] ?? 'bg-slate-800 text-slate-400')
</script>

<template>
  <Head title="Transaction Monitor — Compliance" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-7xl mx-auto px-5 py-7 space-y-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <Link :href="route('staff.compliance.dashboard')"
                  class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-all">
              <i class="ti ti-arrow-left text-base"></i>
            </Link>
            <div class="w-10 h-10 rounded-xl bg-orange-900/30 flex items-center justify-center">
              <i class="ti ti-activity text-orange-400 text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">Transaction Monitor</h1>
              <p class="text-xs text-slate-400">Flag suspicious transactions for AML review</p>
            </div>
          </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
          <div class="relative flex-1 min-w-48">
            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input v-model="filters.search" type="text" placeholder="Reference, account number…"
                   class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2.5 text-sm text-white
                          placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-orange-500/40 focus:border-orange-500/60 transition-colors" />
          </div>
          <select v-model="filters.is_flagged"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors">
            <option value="">All Transactions</option>
            <option value="true">Flagged Only</option>
            <option value="false">Not Flagged</option>
          </select>
          <select v-model="filters.type"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors">
            <option value="">All Types</option>
            <option value="deposit">Deposit</option>
            <option value="withdrawal">Withdrawal</option>
            <option value="transfer">Transfer</option>
          </select>
          <input v-model="filters.date_from" type="date"
                 class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors" />
          <input v-model="filters.date_to" type="date"
                 class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors" />
          <button v-if="filters.search || filters.is_flagged || filters.type || filters.date_from || filters.date_to"
                  @click="clearFilters"
                  class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-sm rounded-xl transition-colors">
            Clear
          </button>
        </div>

        <!-- Table -->
        <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-800/60">
                <tr class="text-xs text-slate-400 uppercase tracking-wide">
                  <th class="px-5 py-3 text-left">Reference</th>
                  <th class="px-4 py-3 text-left">Account / Customer</th>
                  <th class="px-4 py-3 text-left">Type</th>
                  <th class="px-4 py-3 text-right">Amount</th>
                  <th class="px-4 py-3 text-center">Status</th>
                  <th class="px-4 py-3 text-center">Flag</th>
                  <th class="px-4 py-3 text-right">Date</th>
                  <th class="px-4 py-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="txn in transactions.data" :key="txn.id"
                    class="hover:bg-slate-800/30 transition-colors"
                    :class="txn.is_flagged ? 'bg-orange-950/20' : ''">
                  <td class="px-5 py-3 font-mono text-xs text-white">{{ txn.reference }}</td>
                  <td class="px-4 py-3">
                    <p class="text-white text-xs font-mono">{{ txn.primary_account?.account_number ?? '—' }}</p>
                    <p class="text-slate-400 text-[11px]">{{ txn.primary_account?.customer?.full_name ?? '—' }}</p>
                  </td>
                  <td class="px-4 py-3 capitalize" :class="txnTypeColor(txn.type)">{{ txn.type?.replace('_',' ') }}</td>
                  <td class="px-4 py-3 text-right font-semibold" :class="txnTypeColor(txn.type)">${{ fmt(txn.amount) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="text-[10px] px-2 py-0.5 rounded-full" :class="statusBadge(txn.status)">
                      {{ txn.status?.replace('_',' ') }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <div v-if="txn.is_flagged" class="flex flex-col items-center gap-0.5">
                      <i class="ti ti-flag-3 text-orange-400"></i>
                      <span class="text-[9px] text-orange-400/70 max-w-[80px] truncate">{{ txn.flag_reason }}</span>
                    </div>
                    <span v-else class="text-slate-600 text-xs">—</span>
                  </td>
                  <td class="px-4 py-3 text-right text-slate-400 text-xs">
                    {{ new Date(txn.created_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <button v-if="!txn.is_flagged" @click="openFlag(txn)"
                            class="px-3 py-1.5 bg-orange-900/30 hover:bg-orange-800/50 border border-orange-700/40 text-orange-400 text-xs rounded-lg transition-colors whitespace-nowrap">
                      <i class="ti ti-flag mr-1"></i> Flag
                    </button>
                    <button v-else @click="unflag(txn)"
                            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs rounded-lg transition-colors whitespace-nowrap">
                      <i class="ti ti-flag-off mr-1"></i> Clear
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="!transactions.data?.length" class="py-16 text-center text-slate-500">
            <i class="ti ti-activity text-4xl mb-3 block"></i>
            <p>No transactions match your filters.</p>
          </div>

          <!-- Pagination -->
          <div v-if="transactions.last_page > 1" class="px-5 py-4 border-t border-slate-800 flex items-center justify-between">
            <span class="text-xs text-slate-400">{{ transactions.from }}–{{ transactions.to }} of {{ transactions.total }}</span>
            <div class="flex gap-1">
              <Link v-if="transactions.prev_page_url" :href="transactions.prev_page_url"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">← Prev</Link>
              <Link v-if="transactions.next_page_url" :href="transactions.next_page_url"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">Next →</Link>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Flag Modal -->
    <Teleport to="body">
      <div v-if="showFlagModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-[#0B1929] border border-orange-700/30 w-full max-w-lg rounded-2xl p-8 shadow-2xl">
          <div class="flex justify-between items-center mb-5">
            <div>
              <h2 class="text-lg font-bold text-white">Flag Transaction</h2>
              <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ flagTarget?.reference }}</p>
            </div>
            <button @click="showFlagModal = false" class="text-slate-400 hover:text-white transition">
              <i class="ti ti-x text-xl"></i>
            </button>
          </div>
          <form @submit.prevent="submitFlag" class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-orange-400 uppercase tracking-wide mb-1.5 block">Reason for Flagging *</label>
              <textarea v-model="flagForm.flag_reason" rows="4" required
                        placeholder="Describe why this transaction is suspicious…"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-orange-600 transition resize-none"></textarea>
              <p v-if="flagForm.errors.flag_reason" class="text-xs text-red-400 mt-1">{{ flagForm.errors.flag_reason }}</p>
            </div>
            <button type="submit" :disabled="flagForm.processing"
                    class="w-full bg-orange-700 hover:bg-orange-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
              <i class="ti ti-flag-3"></i> Flag Transaction
            </button>
          </form>
        </div>
      </div>
    </Teleport>

  </AuthenticatedLayout>
</template>
