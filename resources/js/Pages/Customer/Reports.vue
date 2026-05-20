<template>
  <CustomerLayout>
    <!-- Print-only header (hidden on screen) -->
    <div class="hidden print:block mb-6">
      <div class="flex items-center justify-between border-b-2 border-slate-800 pb-4 mb-4">
        <div class="flex items-center gap-3">
          <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-12 w-auto object-contain" />
          <div>
            <h1 class="text-xl font-bold text-slate-900 leading-tight">Gobaad Bank</h1>
            <p class="text-xs text-slate-500 uppercase tracking-widest">Customer Transaction Report</p>
          </div>
        </div>
        <div class="text-right text-sm text-slate-600">
          <p class="font-semibold">{{ customerName }}</p>
          <p class="text-xs mt-0.5">Generated: {{ today }}</p>
        </div>
      </div>
    </div>

    <div class="space-y-5 max-w-6xl mx-auto print:max-w-full">

      <!-- ── Toolbar (hidden on print) ── -->
      <div class="flex items-center justify-between print:hidden">
        <div class="flex items-center gap-3">
          <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain shrink-0" />
          <div>
            <h2 class="text-lg font-bold text-slate-800">Transaction Report</h2>
            <p class="text-sm text-slate-500">Filter and print your transaction history</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <!-- Export CSV -->
          <a
            :href="exportUrl"
            class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm"
          >
            <i class="ti ti-table-export text-base"></i>
            Export CSV
          </a>
          <!-- Print / PDF -->
          <button
            @click="printReport"
            class="flex items-center gap-2 bg-[#0B1929] hover:bg-[#1a3a5c] text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm"
          >
            <i class="ti ti-printer text-base"></i>
            Print / PDF
          </button>
        </div>
      </div>

      <!-- ── Filters (hidden on print) ── -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 print:hidden">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Account -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Account</label>
            <select v-model="filters.account_id" @change="applyFilters"
              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40">
              <option value="">All Accounts</option>
              <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_number }}</option>
            </select>
          </div>
          <!-- Type -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Type</label>
            <select v-model="filters.type" @change="applyFilters"
              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40">
              <option value="">All Types</option>
              <option value="deposit">Deposit</option>
              <option value="withdraw">Withdrawal</option>
              <option value="transfer">Transfer</option>
            </select>
          </div>
          <!-- Date From -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">From Date</label>
            <input v-model="filters.date_from" type="date" @change="applyFilters"
              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40" />
          </div>
          <!-- Date To -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">To Date</label>
            <input v-model="filters.date_to" type="date" @change="applyFilters"
              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40" />
          </div>
        </div>
        <div class="flex items-center justify-between mt-4">
          <p class="text-xs text-slate-400">
            {{ transactions.length }} transaction{{ transactions.length !== 1 ? 's' : '' }} found
          </p>
          <button @click="resetFilters" class="text-xs text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-1">
            <i class="ti ti-x"></i> Clear filters
          </button>
        </div>
      </div>

      <!-- ── Summary Cards ── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 print:border print:shadow-none">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Total In</p>
          <p class="text-xl font-bold text-emerald-600">+${{ formatMoney(summary.total_in) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 print:border print:shadow-none">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Total Out</p>
          <p class="text-xl font-bold text-red-500">-${{ formatMoney(summary.total_out) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 print:border print:shadow-none">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Net Flow</p>
          <p class="text-xl font-bold" :class="summary.net >= 0 ? 'text-slate-800' : 'text-red-600'">
            {{ summary.net >= 0 ? '+' : '' }}${{ formatMoney(Math.abs(summary.net)) }}
          </p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 print:border print:shadow-none">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Transactions</p>
          <p class="text-xl font-bold text-slate-800">{{ summary.count }}</p>
        </div>
      </div>

      <!-- ── Transaction Table ── -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden print:shadow-none print:border-0">
        <div class="px-6 py-4 border-b border-slate-100 print:hidden">
          <h3 class="font-semibold text-slate-800">Transactions</h3>
        </div>

        <div v-if="transactions.length === 0" class="p-12 text-center print:hidden">
          <i class="ti ti-file-off text-3xl text-slate-300 mb-3"></i>
          <p class="text-slate-500">No transactions match your filters.</p>
        </div>

        <template v-else>
          <!-- Desktop / print table -->
          <div class="hidden sm:block overflow-x-auto print:block">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 bg-slate-50 print:bg-gray-100">
                  <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                  <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference</th>
                  <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                  <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Account</th>
                  <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50 print:divide-gray-200">
                <tr v-for="tx in transactions" :key="tx.id"
                    class="hover:bg-slate-50 transition-colors print:hover:bg-transparent">
                  <td class="px-6 py-3 text-slate-600 whitespace-nowrap">{{ tx.created_at }}</td>
                  <td class="px-6 py-3 font-mono text-slate-700 text-xs">{{ tx.reference }}</td>
                  <td class="px-6 py-3">
                    <span
                      :class="{
                        'bg-emerald-100 text-emerald-700': tx.type === 'Deposit',
                        'bg-red-100    text-red-700':      tx.type === 'Withdraw',
                        'bg-blue-100   text-blue-700':     tx.type === 'Transfer',
                      }"
                      class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                    >
                      {{ tx.type }}
                    </span>
                  </td>
                  <td class="px-6 py-3 font-mono text-xs text-slate-600">{{ tx.account_number }}</td>
                  <td class="px-6 py-3 text-slate-500 max-w-[180px] truncate">{{ tx.description || '—' }}</td>
                  <td class="px-6 py-3 text-right font-semibold whitespace-nowrap"
                      :class="tx.sign === '+' ? 'text-emerald-600' : 'text-red-500'">
                    {{ tx.sign }}${{ formatMoney(tx.amount) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="border-t-2 border-slate-200 bg-slate-50 print:bg-gray-100">
                <tr>
                  <td colspan="5" class="px-6 py-3 text-sm font-semibold text-slate-700">Totals</td>
                  <td class="px-6 py-3 text-right">
                    <div class="text-xs text-emerald-600 font-semibold">+${{ formatMoney(summary.total_in) }}</div>
                    <div class="text-xs text-red-500   font-semibold">-${{ formatMoney(summary.total_out) }}</div>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Mobile card list (hidden on sm+) -->
          <div class="sm:hidden divide-y divide-slate-100">
            <div v-for="tx in transactions" :key="tx.id" class="px-4 py-3.5 flex items-start gap-3">
              <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                   :class="tx.sign === '+' ? 'bg-emerald-100' : 'bg-red-100'">
                <i class="text-sm"
                   :class="tx.sign === '+' ? 'ti ti-arrow-down-left text-emerald-600' : 'ti ti-arrow-up-right text-red-600'"></i>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <span
                    :class="{
                      'bg-emerald-100 text-emerald-700': tx.type === 'Deposit',
                      'bg-red-100 text-red-700':         tx.type === 'Withdraw',
                      'bg-blue-100 text-blue-700':       tx.type === 'Transfer',
                    }"
                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                  >{{ tx.type }}</span>
                  <span class="font-bold text-sm flex-shrink-0"
                        :class="tx.sign === '+' ? 'text-emerald-600' : 'text-red-500'">
                    {{ tx.sign }}${{ formatMoney(tx.amount) }}
                  </span>
                </div>
                <p class="text-xs font-mono text-slate-500 truncate mt-0.5">{{ tx.reference }}</p>
                <div class="flex items-center justify-between mt-0.5">
                  <p class="text-xs text-slate-400 font-mono">{{ tx.account_number }}</p>
                  <p class="text-[11px] text-slate-400">{{ tx.created_at }}</p>
                </div>
              </div>
            </div>
            <!-- Mobile totals -->
            <div class="px-4 py-3 bg-slate-50 border-t-2 border-slate-200 flex justify-between text-xs font-semibold">
              <span class="text-slate-600">Totals</span>
              <span>
                <span class="text-emerald-600 mr-3">+${{ formatMoney(summary.total_in) }}</span>
                <span class="text-red-500">-${{ formatMoney(summary.total_out) }}</span>
              </span>
            </div>
          </div>
        </template>
      </div>

      <!-- Print footer -->
      <div class="hidden print:block mt-6 pt-4 border-t border-slate-300 text-xs text-slate-500 flex justify-between">
        <span>Gobaad Bank — Secure Banking</span>
        <span>Do not share this document with unauthorized parties.</span>
      </div>

    </div>
  </CustomerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  transactions:  { type: Array,  default: () => [] },
  accounts:      { type: Array,  default: () => [] },
  filters:       { type: Object, default: () => ({}) },
  summary:       { type: Object, default: () => ({ total_in: 0, total_out: 0, net: 0, count: 0 }) },
  customerName:  { type: String, default: '' },
})

const filters = ref({
  account_id: props.filters.account_id ?? '',
  type:       props.filters.type       ?? '',
  date_from:  props.filters.date_from  ?? '',
  date_to:    props.filters.date_to    ?? '',
})

const today = computed(() => {
  return new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
})

// Build the CSV export URL with the same active filters
const exportUrl = computed(() => {
  const params = new URLSearchParams()
  if (filters.value.account_id) params.set('account_id', filters.value.account_id)
  if (filters.value.type)        params.set('type',       filters.value.type)
  if (filters.value.date_from)   params.set('date_from',  filters.value.date_from)
  if (filters.value.date_to)     params.set('date_to',    filters.value.date_to)
  const qs = params.toString()
  return route('customer.reports.export') + (qs ? '?' + qs : '')
})

function applyFilters() {
  const params = {}
  if (filters.value.account_id) params.account_id = filters.value.account_id
  if (filters.value.type)        params.type        = filters.value.type
  if (filters.value.date_from)   params.date_from   = filters.value.date_from
  if (filters.value.date_to)     params.date_to     = filters.value.date_to

  router.get(route('customer.reports'), params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function resetFilters() {
  filters.value = { account_id: '', type: '', date_from: '', date_to: '' }
  applyFilters()
}

function printReport() {
  window.print()
}

function formatMoney(v) {
  return Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style>
@media print {
  /* Hide everything except the main content slot */
  aside,
  header,
  footer,
  .print\:hidden,
  nav {
    display: none !important;
  }

  body {
    background: white !important;
  }

  main {
    padding: 0 !important;
    margin: 0 !important;
  }

  /* Ensure page breaks don't cut rows */
  tr {
    page-break-inside: avoid;
  }
}
</style>
