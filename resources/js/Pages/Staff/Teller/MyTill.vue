<template>
  <div class="min-h-screen bg-gray-50">

    <!-- ───────────── Header ───────────── -->
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Link :href="route('staff.dashboard')"
              class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </Link>
        <div>
          <h1 class="text-lg font-semibold text-gray-900">My Till</h1>
          <p class="text-xs text-gray-500">Your current till status and transaction log</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <Link :href="route('staff.teller.cash-count.index')"
              class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
          Cash Count
        </Link>
        <Link :href="route('staff.teller.deposit')"
              class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
          Deposits
        </Link>
      </div>
    </header>

    <!-- ───────────── Flash ───────────── -->
    <Transition name="fade">
      <div v-if="$page.props.flash?.success"
           class="mx-6 mt-4 bg-green-50 border border-green-300 text-green-800 rounded-lg px-4 py-3 text-sm">
        {{ $page.props.flash.success }}
      </div>
    </Transition>

    <div class="max-w-5xl mx-auto px-6 py-6 space-y-5">

      <!-- ══════════ Status Banner ══════════ -->

      <!-- No allocation — till not open -->
      <div v-if="!props.allocation"
           class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-6 text-center">
        <svg class="w-10 h-10 mx-auto text-amber-400 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <h2 class="text-base font-semibold text-amber-800">Your Till is Not Open</h2>
        <p class="text-sm text-amber-600 mt-1">
          Your Vault Cashier has not yet allocated a float to your till today.
          Please contact your supervisor.
        </p>
      </div>

      <!-- Pending — needs acknowledgement -->
      <div v-else-if="props.allocation.status === 'pending'"
           class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 class="text-base font-semibold text-blue-800">Float Received — Acknowledge to Open Your Till</h2>
            <p class="text-sm text-blue-600 mt-1">
              <strong>{{ props.allocation.allocated_by?.full_name }}</strong> allocated
              <strong>${{ fmt(props.allocation.amount) }}</strong> to your till.
              Please count the cash and acknowledge receipt.
            </p>
            <p class="text-xs text-blue-400 mt-1">
              Allocated at {{ formatDate(props.allocation.allocated_at) }}
            </p>
          </div>
          <form @submit.prevent="acknowledge">
            <button type="submit" :disabled="ackForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors whitespace-nowrap">
              <span v-if="ackForm.processing">Processing…</span>
              <span v-else>✓ Acknowledge Receipt</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Acknowledged — till is open -->
      <div v-else-if="props.allocation.status === 'acknowledged'">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- Till balance -->
          <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Till Balance</p>
            <p class="text-2xl font-bold text-green-600 mt-1">${{ fmt(props.till_balance) }}</p>
            <p class="text-xs text-gray-400 mt-1">Live balance in your till</p>
          </div>
          <!-- Opening float -->
          <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Opening Float</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">${{ fmt(props.allocation.amount) }}</p>
            <p class="text-xs text-gray-400 mt-1">Issued by {{ props.allocation.allocated_by?.full_name }}</p>
          </div>
          <!-- Net movement -->
          <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Net Movement</p>
            <p class="text-2xl font-bold mt-1"
               :class="netMovement >= 0 ? 'text-blue-600' : 'text-red-600'">
              {{ netMovement >= 0 ? '+' : '' }}${{ fmt(Math.abs(netMovement)) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
              {{ netMovement >= 0 ? 'More cash in than out' : 'More cash out than in' }}
            </p>
          </div>
        </div>

        <!-- Status bar -->
        <div class="mt-3 bg-green-50 border border-green-200 rounded-lg px-4 py-2.5 flex items-center gap-2 text-sm text-green-700">
          <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse inline-block"></span>
          Till is <strong>OPEN</strong> · Acknowledged at {{ formatDate(props.allocation.acknowledged_at) }}
        </div>
      </div>

      <!-- ══════════ Recent Till Transactions ══════════ -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-700">Till Ledger — Recent Activity</h2>
          <span class="text-xs text-gray-400">Last 20 entries</span>
        </div>

        <div v-if="!props.till_transactions?.length" class="px-5 py-8 text-center text-sm text-gray-400">
          No transactions recorded against your till yet.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
              <tr>
                <th class="px-5 py-3 text-left">Date / Time</th>
                <th class="px-4 py-3 text-left">Description</th>
                <th class="px-4 py-3 text-left">Reference</th>
                <th class="px-4 py-3 text-right">Debit</th>
                <th class="px-4 py-3 text-right">Credit</th>
                <th class="px-4 py-3 text-right">Balance</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="entry in props.till_transactions" :key="entry.id"
                  class="hover:bg-gray-50/50 transition-colors">
                <td class="px-5 py-3 text-gray-500 text-xs whitespace-nowrap">{{ entry.created_at }}</td>
                <td class="px-4 py-3 text-gray-700 max-w-[200px] truncate" :title="entry.description">
                  {{ entry.description }}
                </td>
                <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ entry.reference }}</td>
                <td class="px-4 py-3 text-right font-mono text-red-600">
                  <span v-if="entry.entry_type === 'debit'">${{ fmt(entry.amount) }}</span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-4 py-3 text-right font-mono text-green-600">
                  <span v-if="entry.entry_type === 'credit'">${{ fmt(entry.amount) }}</span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-4 py-3 text-right font-mono text-gray-800">${{ fmt(entry.balance_after) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ══════════ Past Allocations ══════════ -->
      <div v-if="props.history?.length" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="text-sm font-semibold text-gray-700">Past Allocations</h2>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
            <tr>
              <th class="px-5 py-3 text-right">Float</th>
              <th class="px-4 py-3 text-left">Issued By</th>
              <th class="px-4 py-3 text-left">Allocated</th>
              <th class="px-4 py-3 text-left">Returned</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="h in props.history" :key="h.id" class="hover:bg-gray-50/50">
              <td class="px-5 py-3 text-right font-mono">${{ fmt(h.amount) }}</td>
              <td class="px-4 py-3 text-gray-600">{{ h.allocated_by?.full_name }}</td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ formatDate(h.allocated_at) }}</td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ formatDate(h.returned_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  allocation:        { type: Object, default: null },
  till_balance:      { type: Number, default: 0 },
  till_transactions: { type: Array,  default: () => [] },
  history:           { type: Array,  default: () => [] },
})

/* ── Acknowledge form ── */
const ackForm = useForm({})
function acknowledge() {
  ackForm.post(route('staff.teller.cash-allocation.acknowledge', props.allocation.id))
}

/* ── Net movement (till balance minus opening float) ── */
const netMovement = computed(() => {
  if (!props.allocation) return 0
  return props.till_balance - props.allocation.amount
})

/* ── Formatters ── */
const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-US', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
