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
          <h1 class="text-lg font-semibold text-gray-900">Cash Allocation</h1>
          <p class="text-xs text-gray-500">Distribute opening floats to tellers · manage active tills</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <Link :href="route('staff.teller.cash-count.index')"
              class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
          Cash Count
        </Link>
        <!-- Vault balance pill -->
        <span class="bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold px-3 py-1 rounded-full">
          Vault: ${{ fmt(vaultBalance) }}
        </span>
      </div>
    </header>

    <!-- ───────────── Flash message ───────────── -->
    <Transition name="fade">
      <div v-if="$page.props.flash?.success"
           class="mx-6 mt-4 bg-green-50 border border-green-300 text-green-800 rounded-lg px-4 py-3 text-sm flex items-start gap-2">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.success }}
      </div>
    </Transition>
    <Transition name="fade">
      <div v-if="pageErrors.error"
           class="mx-6 mt-4 bg-red-50 border border-red-300 text-red-800 rounded-lg px-4 py-3 text-sm flex items-start gap-2">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ pageErrors.error }}
      </div>
    </Transition>

    <div class="max-w-7xl mx-auto px-6 py-6 grid grid-cols-1 xl:grid-cols-3 gap-6">

      <!-- ══════════════════ LEFT: Allocate Form ══════════════════ -->
      <div class="xl:col-span-1 space-y-5">

        <!-- Teller status cards -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Tellers & Supervisors</h2>
            <span class="text-xs text-gray-400">{{ tellers.length }} eligible</span>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-for="t in tellers" :key="t.id"
                 :class="['flex items-center justify-between px-5 py-3 text-sm',
                          t.active_alloc ? 'bg-green-50/50' : '']">
              <div>
                <p class="font-medium text-gray-800">{{ t.full_name }}</p>
                <p class="text-xs text-gray-400">{{ t.role_name }}</p>
              </div>
              <div class="text-right">
                <p v-if="t.active_alloc" class="text-xs font-semibold"
                   :class="t.active_alloc.status === 'acknowledged' ? 'text-green-700' : 'text-amber-600'">
                  {{ t.active_alloc.status === 'acknowledged' ? '● Open' : '⏳ Pending' }}
                </p>
                <p v-else class="text-xs text-gray-400">No till</p>
                <p v-if="t.active_alloc" class="text-xs text-gray-500 mt-0.5">
                  Till: ${{ fmt(t.till_balance) }}
                </p>
              </div>
            </div>
            <p v-if="!tellers.length" class="px-5 py-4 text-xs text-gray-400 text-center">
              No Bank Tellers or Teller Supervisors found in this branch.
            </p>
          </div>
        </div>

        <!-- Allocate Form -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Allocate Float to Teller</h2>
            <p class="text-xs text-gray-400 mt-0.5">Transfers cash from vault to teller's till</p>
          </div>
          <form @submit.prevent="submitAllocation" class="px-5 py-4 space-y-4">
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Select Teller *</label>
              <select v-model="allocForm.teller_id"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                      required>
                <option value="">— choose teller —</option>
                <option v-for="t in availableTellers" :key="t.id" :value="t.id">
                  {{ t.full_name }} ({{ t.role_name }})
                </option>
              </select>
              <p v-if="availableTellers.length < tellers.length" class="text-xs text-amber-600 mt-1">
                Tellers with open tills are hidden.
              </p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Amount ($) *</label>
              <input v-model.number="allocForm.amount" type="number" step="0.01" min="0.01"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                     placeholder="e.g. 5000.00" required />
              <p v-if="allocForm.amount > 0 && allocForm.amount > props.vault_balance"
                 class="text-xs text-red-600 mt-1">
                ⚠ Exceeds vault balance (${{ fmt(props.vault_balance) }})
              </p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Notes</label>
              <textarea v-model="allocForm.notes" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                        placeholder="Optional notes…"></textarea>
            </div>
            <button type="submit"
                    :disabled="allocForm.processing || !allocForm.teller_id || !allocForm.amount"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium py-2 rounded-lg transition-colors">
              <span v-if="allocForm.processing">Allocating…</span>
              <span v-else>Allocate Float →</span>
            </button>
          </form>
        </div>
      </div>

      <!-- ══════════════════ RIGHT: Active + History ══════════════════ -->
      <div class="xl:col-span-2 space-y-5">

        <!-- Active Allocations -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Active Tills</h2>
            <span v-if="active.length" class="bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              {{ active.length }} open
            </span>
          </div>

          <div v-if="!active.length" class="px-5 py-10 text-center text-sm text-gray-400">
            No active till allocations right now.
          </div>

          <div v-else class="divide-y divide-gray-50">
            <div v-for="alloc in active" :key="alloc.id"
                 class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <!-- Left info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-semibold text-sm text-gray-900">{{ alloc.teller?.full_name }}</span>
                  <span :class="statusBadge(alloc.status)" class="text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ alloc.status === 'pending' ? '⏳ Awaiting Acknowledgement' : '● Till Open' }}
                  </span>
                </div>
                <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
                  <span>Float Issued: <strong class="text-gray-700">${{ fmt(alloc.amount) }}</strong></span>
                  <span>Current Balance: <strong :class="alloc.till_balance < alloc.amount ? 'text-amber-600' : 'text-green-700'">
                    ${{ fmt(alloc.till_balance) }}
                  </strong></span>
                  <span>Allocated by: {{ alloc.allocated_by?.full_name }}</span>
                  <span>{{ formatDate(alloc.allocated_at) }}</span>
                </div>
                <p v-if="alloc.notes" class="mt-1 text-xs text-gray-400 italic">{{ alloc.notes }}</p>
              </div>
              <!-- Collect button -->
              <form @submit.prevent="collectTill(alloc.id)">
                <button type="submit"
                        :disabled="collectingId === alloc.id"
                        class="bg-gray-700 hover:bg-gray-900 disabled:opacity-50 text-white text-xs font-medium px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
                  <span v-if="collectingId === alloc.id">Collecting…</span>
                  <span v-else>Collect Cash</span>
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- History -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Allocation History</h2>
          </div>

          <div v-if="!history.data?.length" class="px-5 py-8 text-center text-sm text-gray-400">
            No returned allocations yet.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                <tr>
                  <th class="px-5 py-3 text-left">Teller</th>
                  <th class="px-4 py-3 text-right">Float</th>
                  <th class="px-4 py-3 text-left">Allocated By</th>
                  <th class="px-4 py-3 text-left">Allocated At</th>
                  <th class="px-4 py-3 text-left">Returned At</th>
                  <th class="px-4 py-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="alloc in history.data" :key="alloc.id"
                    class="hover:bg-gray-50/50 transition-colors">
                  <td class="px-5 py-3 font-medium text-gray-800">{{ alloc.teller?.full_name }}</td>
                  <td class="px-4 py-3 text-right font-mono">${{ fmt(alloc.amount) }}</td>
                  <td class="px-4 py-3 text-gray-500">{{ alloc.allocated_by?.full_name }}</td>
                  <td class="px-4 py-3 text-gray-500">{{ formatDate(alloc.allocated_at) }}</td>
                  <td class="px-4 py-3 text-gray-500">{{ formatDate(alloc.returned_at) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full font-medium">Returned</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="history.last_page > 1"
               class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <span>Page {{ history.current_page }} of {{ history.last_page }}</span>
            <div class="flex gap-2">
              <Link v-if="history.prev_page_url" :href="history.prev_page_url"
                    class="px-3 py-1 border rounded hover:bg-gray-50">← Prev</Link>
              <Link v-if="history.next_page_url" :href="history.next_page_url"
                    class="px-3 py-1 border rounded hover:bg-gray-50">Next →</Link>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm, usePage, router } from '@inertiajs/vue3'

const props = defineProps({
  tellers:       { type: Array,  default: () => [] },
  vault_balance: { type: Number, default: 0 },
  active:        { type: Array,  default: () => [] },
  history:       { type: Object, default: () => ({ data: [], last_page: 1 }) },
})

const page        = usePage()
const pageErrors  = computed(() => page.props.errors ?? {})

/* ── Allocate form ── */
const allocForm = useForm({
  teller_id: '',
  amount:    '',
  notes:     '',
})

function submitAllocation() {
  allocForm.post(route('staff.teller.cash-allocation.store'), {
    onSuccess: () => allocForm.reset(),
  })
}

/* ── Tellers without an active till ── */
const availableTellers = computed(() =>
  props.tellers.filter(t => !t.active_alloc)
)

/* ── Collect till ── */
const collectingId = ref(null)
function collectTill(id) {
  collectingId.value = id
  router.post(route('staff.teller.cash-allocation.collect', id), {}, {
    onFinish: () => { collectingId.value = null },
  })
}

/* ── Helpers ── */
const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-US', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function statusBadge(status) {
  if (status === 'pending')      return 'bg-amber-100 text-amber-700'
  if (status === 'acknowledged') return 'bg-green-100 text-green-700'
  return 'bg-gray-100 text-gray-600'
}

const vaultBalance = computed(() => props.vault_balance)
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
