<template>
  <CustomerLayout>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Transactions</h1>
        <p class="text-sm text-slate-500 mt-1">Your transaction history across all accounts.</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-5">
      <form @submit.prevent="applyFilters" class="flex flex-wrap gap-3">
        <!-- Account filter -->
        <select v-model="filters.account_id"
          class="flex-1 min-w-[140px] px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C9A84C]">
          <option value="">All Accounts</option>
          <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_number }}</option>
        </select>

        <!-- Type filter -->
        <select v-model="filters.type"
          class="flex-1 min-w-[130px] px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C9A84C]">
          <option value="">All Types</option>
          <option value="deposit">Deposit</option>
          <option value="withdrawal">Withdrawal</option>
          <option value="transfer">Transfer</option>
        </select>

        <!-- Date from -->
        <input v-model="filters.date_from" type="date"
          class="flex-1 min-w-[140px] px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C9A84C]" />

        <!-- Date to -->
        <input v-model="filters.date_to" type="date"
          class="flex-1 min-w-[140px] px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C9A84C]" />

        <button type="submit"
          class="px-5 py-2 bg-[#0B1929] text-white text-sm font-semibold rounded-lg hover:bg-[#1a3a5c] transition-colors">
          <i class="ti ti-filter mr-1"></i> Filter
        </button>

        <button v-if="hasFilters" type="button" @click="clearFilters"
          class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg">
          Clear
        </button>
      </form>
    </div>

    <!-- Transactions table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="transactions.data.length">
        <!-- Desktop table -->
        <table class="w-full hidden sm:table">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50">
              <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Reference</th>
              <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Type</th>
              <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Account</th>
              <th class="px-5 py-3 text-right text-[11px] font-bold text-slate-400 uppercase tracking-widest">Amount</th>
              <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
              <th class="px-5 py-3 text-center text-[11px] font-bold text-slate-400 uppercase tracking-widest">Receipt</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-5 py-3.5 font-mono text-xs text-slate-600 font-semibold">{{ tx.reference }}</td>
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-2">
                  <span class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="tx.sign === '+' ? 'bg-emerald-100' : 'bg-red-100'">
                    <i class="text-xs"
                      :class="tx.sign === '+' ? 'ti ti-arrow-down-left text-emerald-600' : 'ti ti-arrow-up-right text-red-600'"></i>
                  </span>
                  <span class="text-sm text-slate-700">{{ tx.type }}</span>
                </div>
              </td>
              <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ tx.account_number }}</td>
              <td class="px-5 py-3.5 text-right font-bold text-sm"
                :class="tx.sign === '+' ? 'text-emerald-600' : 'text-red-600'">
                {{ tx.sign }}${{ formatAmount(tx.amount) }}
              </td>
              <td class="px-5 py-3.5 text-xs text-slate-400">{{ tx.created_at }}</td>
              <td class="px-5 py-3.5 text-center">
                <a
                  :href="receiptUrl(tx.id)"
                  target="_blank"
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 hover:bg-[#0B1929] hover:text-white text-slate-600 rounded-lg text-[11px] font-semibold transition-colors"
                  title="Download PDF receipt"
                >
                  <i class="ti ti-download text-xs"></i>
                  PDF
                </a>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Mobile list -->
        <div class="sm:hidden divide-y divide-slate-100">
          <div v-for="tx in transactions.data" :key="tx.id" class="flex items-center gap-3 px-4 py-4">
            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
              :class="tx.sign === '+' ? 'bg-emerald-100' : 'bg-red-100'">
              <i :class="tx.sign === '+' ? 'ti ti-arrow-down-left text-emerald-600' : 'ti ti-arrow-up-right text-red-600'"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-800 truncate">{{ tx.type }}</p>
              <p class="text-xs text-slate-400 truncate font-mono">{{ tx.reference }}</p>
            </div>
            <div class="text-right flex flex-col items-end gap-1">
              <p class="text-sm font-bold" :class="tx.sign === '+' ? 'text-emerald-600' : 'text-red-600'">
                {{ tx.sign }}${{ formatAmount(tx.amount) }}
              </p>
              <p class="text-[11px] text-slate-400">{{ tx.created_at }}</p>
              <a :href="receiptUrl(tx.id)" target="_blank"
                 class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 hover:bg-[#0B1929] hover:text-white text-slate-600 rounded text-[10px] font-semibold transition-colors">
                <i class="ti ti-download text-[10px]"></i> PDF
              </a>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.last_page > 1"
          class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Page {{ transactions.current_page }} of {{ transactions.last_page }}
            &nbsp;·&nbsp; {{ transactions.total }} total
          </p>
          <div class="flex gap-2">
            <Link v-if="transactions.prev_page_url" :href="transactions.prev_page_url"
              class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">
              ← Prev
            </Link>
            <Link v-if="transactions.next_page_url" :href="transactions.next_page_url"
              class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">
              Next →
            </Link>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="px-6 py-14 text-center text-slate-400">
        <i class="ti ti-receipt-off text-4xl mb-3 block"></i>
        <p class="text-sm">No transactions found for the selected filters.</p>
      </div>
    </div>
  </CustomerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

const props = defineProps({
  transactions: { type: Object, required: true },
  accounts:     { type: Array, default: () => [] },
  filters:      { type: Object, default: () => ({}) },
})

const filters = ref({
  account_id: props.filters.account_id ?? '',
  type:       props.filters.type ?? '',
  date_from:  props.filters.date_from ?? '',
  date_to:    props.filters.date_to ?? '',
})

const hasFilters = computed(() =>
  Object.values(filters.value).some(v => v !== '')
)

function applyFilters() {
  router.get(route('customer.transactions'), filters.value, { preserveState: true })
}

function clearFilters() {
  filters.value = { account_id: '', type: '', date_from: '', date_to: '' }
  applyFilters()
}

function formatAmount(n) {
  return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function receiptUrl(id) {
  return route('customer.transactions.receipt', { id })
}
</script>
