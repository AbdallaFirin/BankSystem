<template>
  <CustomerLayout>
    <!-- Welcome -->
    <div class="mb-5">
      <h1 class="text-xl sm:text-2xl font-bold text-slate-800">
        Welcome back, <span class="text-[#0B1929]">{{ firstName }}</span> 👋
      </h1>
      <p class="text-sm text-slate-500 mt-1">Here's an overview of your accounts.</p>
    </div>

    <!-- Account Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
      <div v-for="acc in accounts" :key="acc.id"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col gap-3 relative overflow-hidden"
        :class="{ 'opacity-60': acc.is_frozen || acc.status !== 'active' }"
      >
        <!-- Frozen ribbon -->
        <div v-if="acc.is_frozen"
          class="absolute top-3 right-3 bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full">
          Frozen
        </div>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#0B1929] flex items-center justify-center flex-shrink-0">
            <i class="ti ti-credit-card text-[#C9A84C] text-lg"></i>
          </div>
          <div class="min-w-0">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-widest">{{ acc.type_name }}</p>
            <p class="font-mono text-xs text-slate-700 font-semibold truncate">{{ acc.account_number }}</p>
          </div>
        </div>

        <div>
          <p class="text-xs text-slate-400 mb-0.5">Available Balance</p>
          <p class="text-2xl font-bold" :class="acc.balance < 0 ? 'text-red-600' : 'text-slate-800'">
            <span class="text-sm font-normal text-slate-500">$</span>{{ formatAmount(Math.abs(acc.balance)) }}
            <span v-if="acc.balance < 0" class="text-xs font-normal text-red-500">(overdraft)</span>
          </p>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-400 border-t border-slate-100 pt-3">
          <span>{{ acc.branch_name }}</span>
          <span>Opened {{ acc.opened_at }}</span>
        </div>
      </div>

      <!-- Placeholder if no accounts -->
      <div v-if="!accounts.length"
        class="col-span-full bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-8 text-center text-slate-400 text-sm">
        <i class="ti ti-credit-card text-3xl mb-2 block"></i>
        No active accounts found.
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <h2 class="font-bold text-slate-800">Recent Transactions</h2>
        <Link :href="route('customer.transactions')" class="text-sm text-[#0B1929] hover:underline font-medium">
          View all <i class="ti ti-arrow-right text-xs"></i>
        </Link>
      </div>

      <div v-if="recent_transactions.length" class="divide-y divide-slate-100">
        <div v-for="tx in recent_transactions" :key="tx.id" class="flex items-center gap-3 px-4 sm:px-6 py-3.5">
          <!-- Icon -->
          <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
            :class="tx.sign === '+' ? 'bg-emerald-100' : 'bg-red-100'">
            <i class="text-base"
              :class="tx.sign === '+' ? 'ti ti-arrow-down-left text-emerald-600' : 'ti ti-arrow-up-right text-red-600'"></i>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-800 truncate">{{ tx.type }}</p>
            <p class="text-xs text-slate-400 truncate font-mono">{{ tx.account_number }}</p>
          </div>

          <div class="text-right flex-shrink-0">
            <p class="text-sm font-bold" :class="tx.sign === '+' ? 'text-emerald-600' : 'text-red-600'">
              {{ tx.sign }}${{ formatAmount(tx.amount) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ tx.created_at }}</p>
          </div>
        </div>
      </div>

        <div v-else class="px-6 py-10 text-center text-slate-400 text-sm">
        <i class="ti ti-receipt-off text-3xl mb-2 block"></i>
        No recent transactions found.
      </div>
    </div>

    <!-- ── Money Flow Chart ── -->
    <div v-if="recent_transactions.length" class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div>
          <h2 class="font-bold text-slate-800">Recent Money Flow</h2>
          <p class="text-xs text-slate-400 mt-0.5">Last {{ recent_transactions.length }} transactions — income vs. expense</p>
        </div>
        <i class="ti ti-chart-area-line text-[#0B1929] text-xl"></i>
      </div>
      <div class="px-2 py-3">
        <VueApexCharts
          type="bar"
          height="180"
          :options="flowChartOptions"
          :series="flowChartSeries"
        />
      </div>
      <!-- Summary row -->
      <div class="grid grid-cols-3 divide-x divide-slate-100 border-t border-slate-100">
        <div class="px-6 py-3 text-center">
          <p class="text-xs text-slate-400">Total In</p>
          <p class="text-sm font-bold text-emerald-600 mt-0.5">+${{ formatAmount(totalIn) }}</p>
        </div>
        <div class="px-6 py-3 text-center">
          <p class="text-xs text-slate-400">Total Out</p>
          <p class="text-sm font-bold text-red-500 mt-0.5">-${{ formatAmount(totalOut) }}</p>
        </div>
        <div class="px-6 py-3 text-center">
          <p class="text-xs text-slate-400">Net</p>
          <p class="text-sm font-bold mt-0.5" :class="netFlow >= 0 ? 'text-[#0B1929]' : 'text-red-500'">
            {{ netFlow >= 0 ? '+' : '' }}${{ formatAmount(Math.abs(netFlow)) }}
          </p>
        </div>
      </div>
    </div>

  </CustomerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  accounts:             { type: Array, default: () => [] },
  recent_transactions:  { type: Array, default: () => [] },
  unread_count:         { type: Number, default: 0 },
})

const page = usePage()
const firstName = computed(() => {
  const name = page.props.auth?.customer?.full_name ?? ''
  return name.split(' ')[0] ?? name
})

function formatAmount(n) {
  return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/* ── Money Flow Chart ── */
const flowChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 180,
    background: 'transparent',
    toolbar: { show: false },
    stacked: false,
  },
  colors: ['#10b981', '#ef4444'],
  plotOptions: {
    bar: { borderRadius: 4, columnWidth: '60%', distributed: false }
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.recent_transactions.map(t => t.type),
    labels: { style: { colors: '#94a3b8', fontSize: '10px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#94a3b8', fontSize: '10px' },
      formatter: v => v >= 1000 ? `$${(v/1000).toFixed(1)}k` : `$${v}`,
    }
  },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 3 },
  legend: { show: true, labels: { colors: '#64748b' }, fontSize: '11px' },
  tooltip: {
    y: { formatter: v => `$${Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 })}` }
  },
}))

const flowChartSeries = computed(() => {
  const inAmounts  = props.recent_transactions.map(t => t.sign === '+' ? Number(t.amount) : 0)
  const outAmounts = props.recent_transactions.map(t => t.sign === '-' ? Number(t.amount) : 0)
  return [
    { name: 'Money In',  data: inAmounts  },
    { name: 'Money Out', data: outAmounts },
  ]
})

const totalIn  = computed(() => props.recent_transactions.filter(t => t.sign === '+').reduce((s, t) => s + Number(t.amount), 0))
const totalOut = computed(() => props.recent_transactions.filter(t => t.sign === '-').reduce((s, t) => s + Number(t.amount), 0))
const netFlow  = computed(() => totalIn.value - totalOut.value)
</script>
