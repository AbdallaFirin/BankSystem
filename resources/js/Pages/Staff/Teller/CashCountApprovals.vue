<script setup>
import { ref } from 'vue'
import { useForm, usePage, Link, router } from '@inertiajs/vue3'

const props = defineProps({
    pending:      Object,   // paginated — approval_status = 'pending'
    reviewed:     Object,   // paginated — verified or flagged
    pendingCount: Number,
})

const page = usePage()

/* ── Helpers ── */
const fmt = n =>
    '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const fmtDate = d =>
    d ? new Date(d).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : '—'

/* ── Tab ── */
const activeTab = ref('pending')

/* ── Expanded denomination rows ── */
const expanded = ref(new Set())
const toggleExpand = id => {
    expanded.value.has(id) ? expanded.value.delete(id) : expanded.value.add(id)
}

/* ── Approve ── */
const approveForm = useForm({})
const doApprove = id => {
    approveForm.post(route('staff.teller.cash-count.approve', id), {
        preserveScroll: true,
    })
}

/* ── Flag modal ── */
const flagModal  = ref(false)
const flagTarget = ref(null)
const flagForm   = useForm({ flag_reason: '' })

const openFlag = count => {
    flagTarget.value = count
    flagForm.flag_reason = ''
    flagForm.clearErrors()
    flagModal.value = true
}
const closeFlag = () => { flagModal.value = false; flagTarget.value = null }

const submitFlag = () => {
    flagForm.post(route('staff.teller.cash-count.flag', flagTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => closeFlag(),
    })
}

/* ── Denomination label helper ── */
const denomLabel = d => {
    const v = Number(d.denomination)
    return v >= 1
        ? `$${Number.isInteger(v) ? v : v} Bill`
        : `${Math.round(v * 100)}¢ Coin`
}
</script>

<template>
  <div class="min-h-screen bg-[#070F1A] text-white">
    <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

      <!-- ── Header ── -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link :href="route('staff.teller.cash-count.index')"
                class="p-2 rounded-lg border border-slate-700 text-slate-400 hover:text-white hover:bg-slate-800 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-white">Cash Count Approvals</h1>
            <p class="text-sm text-slate-400 mt-0.5">Review and verify teller cash counts</p>
          </div>
        </div>
        <!-- Pending badge -->
        <div v-if="pendingCount > 0"
             class="flex items-center gap-2 bg-yellow-900/30 border border-yellow-700/40 text-yellow-400
                    rounded-xl px-4 py-2.5 text-sm font-semibold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ pendingCount }} awaiting review
        </div>
        <div v-else class="flex items-center gap-2 bg-emerald-900/30 border border-emerald-700/40
                           text-emerald-400 rounded-xl px-4 py-2.5 text-sm font-semibold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          All counts reviewed
        </div>
      </div>

      <!-- ── Flash ── -->
      <div v-if="page.props.flash?.success"
           class="bg-emerald-900/40 border border-emerald-500/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ page.props.flash.success }}
      </div>

      <!-- ── Tabs ── -->
      <div class="flex gap-1 bg-slate-900 border border-slate-700 rounded-xl p-1 w-fit">
        <button @click="activeTab = 'pending'"
                class="px-5 py-2 rounded-lg text-sm font-medium transition"
                :class="activeTab === 'pending'
                  ? 'bg-slate-700 text-white'
                  : 'text-slate-400 hover:text-white'">
          Pending
          <span v-if="pendingCount > 0"
                class="ml-2 bg-yellow-500 text-black text-xs font-bold rounded-full px-1.5 py-0.5">
            {{ pendingCount }}
          </span>
        </button>
        <button @click="activeTab = 'reviewed'"
                class="px-5 py-2 rounded-lg text-sm font-medium transition"
                :class="activeTab === 'reviewed'
                  ? 'bg-slate-700 text-white'
                  : 'text-slate-400 hover:text-white'">
          Reviewed
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════
           PENDING TAB
           ═══════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'pending'" class="space-y-4">
        <div v-if="!pending?.data?.length"
             class="bg-slate-900 border border-slate-700 rounded-2xl px-6 py-16 text-center text-slate-500 text-sm">
          No pending cash counts — all counts have been reviewed.
        </div>

        <!-- One card per pending count -->
        <div v-for="c in pending?.data" :key="c.id"
             class="bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden">

          <!-- Card header -->
          <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-700/60">
            <div class="flex items-center gap-3 flex-wrap">
              <span class="text-xs text-slate-500 font-mono">Count #{{ c.id }}</span>
              <span class="capitalize text-xs px-2 py-0.5 rounded-full bg-slate-800 border border-slate-600 text-slate-300">
                {{ c.session_type }}
              </span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-900/30 text-yellow-400 border border-yellow-700/30 font-semibold">
                ⏳ Pending Review
              </span>
              <!-- Reconciliation result -->
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                    :class="{
                      'bg-emerald-900/40 text-emerald-400 border border-emerald-700/30': c.status === 'balanced',
                      'bg-amber-900/30   text-amber-400   border border-amber-700/30':   c.status === 'overage',
                      'bg-red-900/30     text-red-400     border border-red-700/30':      c.status === 'shortage',
                    }">
                {{ c.status.charAt(0).toUpperCase() + c.status.slice(1) }}
              </span>
            </div>
            <span class="text-xs text-slate-400">{{ fmtDate(c.counted_at) }}</span>
          </div>

          <!-- Card body -->
          <div class="px-6 py-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">Teller</div>
                <div class="text-sm font-semibold text-white">{{ c.staff?.full_name ?? '—' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">Physical Count</div>
                <div class="text-sm font-bold font-mono text-white">{{ fmt(c.physical_total) }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">System Balance</div>
                <div class="text-sm font-mono text-slate-300">{{ fmt(c.system_balance) }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">Difference</div>
                <div class="text-sm font-bold font-mono"
                     :class="{
                       'text-emerald-400': c.status === 'balanced',
                       'text-amber-400':   c.status === 'overage',
                       'text-red-400':     c.status === 'shortage',
                     }">
                  {{ c.difference >= 0 ? '+' : '' }}{{ fmt(c.difference) }}
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="c.notes" class="mb-4 px-4 py-3 bg-slate-800/60 rounded-xl border border-slate-700 text-sm text-slate-400">
              <span class="text-slate-500 text-xs uppercase tracking-wide">Notes: </span>{{ c.notes }}
            </div>

            <!-- Toggle denomination breakdown -->
            <button @click="toggleExpand(c.id)"
                    class="flex items-center gap-2 text-xs text-blue-400 hover:text-blue-300 transition mb-3">
              <svg class="w-3.5 h-3.5 transition-transform"
                   :class="expanded.has(c.id) ? 'rotate-90' : ''"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
              {{ expanded.has(c.id) ? 'Hide' : 'Show' }} denomination breakdown
            </button>

            <!-- Denomination table (expandable) -->
            <div v-if="expanded.has(c.id)"
                 class="mb-4 rounded-xl overflow-hidden border border-slate-700">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-slate-800">
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 font-medium">Denomination</th>
                    <th class="px-4 py-2.5 text-center text-xs text-slate-400 font-medium">Type</th>
                    <th class="px-4 py-2.5 text-right text-xs text-slate-400 font-medium">Quantity</th>
                    <th class="px-4 py-2.5 text-right text-xs text-slate-400 font-medium">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                  <tr v-if="!c.denominations?.length">
                    <td colspan="4" class="px-4 py-4 text-center text-slate-500 text-xs">No denominations recorded.</td>
                  </tr>
                  <tr v-for="d in c.denominations" :key="d.id"
                      class="hover:bg-slate-800/40">
                    <td class="px-4 py-2.5 font-mono text-white text-xs">{{ denomLabel(d) }}</td>
                    <td class="px-4 py-2.5 text-center">
                      <span class="text-xs px-1.5 py-0.5 rounded-full"
                            :class="d.denomination >= 1
                              ? 'bg-blue-900/30 text-blue-400'
                              : 'bg-amber-900/20 text-amber-400'">
                        {{ d.denomination >= 1 ? 'Bill' : 'Coin' }}
                      </span>
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-slate-300 text-xs">
                      {{ Number(d.quantity).toLocaleString() }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-emerald-400 font-semibold text-xs">
                      {{ fmt(d.subtotal) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="bg-slate-800">
                    <td colspan="3" class="px-4 py-2.5 text-xs font-semibold text-slate-300">Physical Total</td>
                    <td class="px-4 py-2.5 text-right font-mono font-bold text-white text-xs">
                      {{ fmt(c.physical_total) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-3">
              <button @click="doApprove(c.id)"
                      :disabled="approveForm.processing"
                      class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700
                             text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Verify &amp; Approve
              </button>
              <button @click="openFlag(c)"
                      class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-900/30 hover:bg-red-900/50
                             text-red-400 border border-red-700/40 text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                </svg>
                Flag for Follow-up
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pending?.last_page > 1"
             class="flex items-center justify-between text-sm">
          <span class="text-slate-400 text-xs">
            Showing {{ pending.from }}–{{ pending.to }} of {{ pending.total }}
          </span>
          <div class="flex gap-2">
            <Link v-if="pending.prev_page_url" :href="pending.prev_page_url"
                  class="px-3 py-1.5 rounded-lg border border-slate-600 text-slate-300 text-xs hover:bg-slate-800 transition">
              ← Prev
            </Link>
            <Link v-if="pending.next_page_url" :href="pending.next_page_url"
                  class="px-3 py-1.5 rounded-lg border border-slate-600 text-slate-300 text-xs hover:bg-slate-800 transition">
              Next →
            </Link>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════
           REVIEWED TAB
           ═══════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'reviewed'"
           class="bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden">
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full min-w-[720px]">
            <thead>
              <tr class="bg-slate-800">
                <th class="px-5 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">#</th>
                <th class="px-4 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Counted</th>
                <th class="px-4 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Session</th>
                <th class="px-4 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Teller</th>
                <th class="px-4 py-3 text-right text-xs text-slate-400 font-medium uppercase tracking-wide">Difference</th>
                <th class="px-4 py-3 text-center text-xs text-slate-400 font-medium uppercase tracking-wide">Result</th>
                <th class="px-4 py-3 text-center text-xs text-slate-400 font-medium uppercase tracking-wide">Decision</th>
                <th class="px-4 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Reviewed by</th>
                <th class="px-5 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Reviewed at</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-if="!reviewed?.data?.length">
                <td colspan="9" class="px-5 py-10 text-center text-slate-500 text-sm">No reviewed counts yet.</td>
              </tr>
              <tr v-for="c in reviewed?.data" :key="c.id"
                  class="hover:bg-slate-800/50 transition-colors">
                <td class="px-5 py-3 text-slate-500 text-xs font-mono">{{ c.id }}</td>
                <td class="px-4 py-3 text-xs text-slate-300">{{ fmtDate(c.counted_at) }}</td>
                <td class="px-4 py-3">
                  <span class="capitalize text-xs px-2 py-0.5 rounded-full bg-slate-800 border border-slate-600 text-slate-300">
                    {{ c.session_type }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-slate-300">{{ c.staff?.full_name ?? '—' }}</td>
                <td class="px-4 py-3 text-right font-mono text-sm font-semibold"
                    :class="{
                      'text-emerald-400': c.status === 'balanced',
                      'text-amber-400':   c.status === 'overage',
                      'text-red-400':     c.status === 'shortage',
                    }">
                  {{ c.difference >= 0 ? '+' : '' }}{{ fmt(c.difference) }}
                </td>
                <!-- Result -->
                <td class="px-4 py-3 text-center">
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                        :class="{
                          'bg-emerald-900/40 text-emerald-400 border border-emerald-700/30': c.status === 'balanced',
                          'bg-amber-900/30   text-amber-400   border border-amber-700/30':   c.status === 'overage',
                          'bg-red-900/30     text-red-400     border border-red-700/30':      c.status === 'shortage',
                        }">
                    {{ c.status.charAt(0).toUpperCase() + c.status.slice(1) }}
                  </span>
                </td>
                <!-- Decision -->
                <td class="px-4 py-3 text-center">
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                        :class="{
                          'bg-emerald-900/40 text-emerald-400 border border-emerald-700/30': c.approval_status === 'verified',
                          'bg-red-900/30     text-red-400     border border-red-700/30':      c.approval_status === 'flagged',
                        }">
                    {{ c.approval_status === 'verified' ? '✓ Verified' : '⚑ Flagged' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-slate-300">{{ c.verified_by?.full_name ?? '—' }}</td>
                <td class="px-5 py-3 text-xs text-slate-400">{{ fmtDate(c.verified_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards (reviewed) -->
        <div class="md:hidden divide-y divide-slate-800">
          <div v-if="!reviewed?.data?.length" class="px-5 py-10 text-center text-slate-500 text-sm">
            No reviewed counts yet.
          </div>
          <div v-for="c in reviewed?.data" :key="c.id" class="px-5 py-4 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-500 font-mono">#{{ c.id }} · {{ fmtDate(c.counted_at) }}</span>
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                    :class="{
                      'bg-emerald-900/40 text-emerald-400': c.approval_status === 'verified',
                      'bg-red-900/30     text-red-400':     c.approval_status === 'flagged',
                    }">
                {{ c.approval_status === 'verified' ? '✓ Verified' : '⚑ Flagged' }}
              </span>
            </div>
            <div class="text-sm text-white font-semibold capitalize">{{ c.session_type }} Count</div>
            <div class="text-xs text-slate-400">Teller: {{ c.staff?.full_name ?? '—' }}</div>
            <div class="text-xs text-slate-500">Reviewed by {{ c.verified_by?.full_name ?? '—' }} · {{ fmtDate(c.verified_at) }}</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="reviewed?.last_page > 1"
             class="px-6 py-4 border-t border-slate-700 flex items-center justify-between text-sm">
          <span class="text-slate-400 text-xs">{{ reviewed.from }}–{{ reviewed.to }} of {{ reviewed.total }}</span>
          <div class="flex gap-2">
            <Link v-if="reviewed.prev_page_url" :href="reviewed.prev_page_url + '&tab=reviewed'"
                  class="px-3 py-1.5 rounded-lg border border-slate-600 text-slate-300 text-xs hover:bg-slate-800 transition">
              ← Prev
            </Link>
            <Link v-if="reviewed.next_page_url" :href="reviewed.next_page_url + '&tab=reviewed'"
                  class="px-3 py-1.5 rounded-lg border border-slate-600 text-slate-300 text-xs hover:bg-slate-800 transition">
              Next →
            </Link>
          </div>
        </div>
      </div>

    </div><!-- /max-w -->
  </div>

  <!-- ═══════════════════════════════════════════════════
       FLAG MODAL
       ═══════════════════════════════════════════════════ -->
  <Teleport to="body">
    <Transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="flagModal"
           class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4"
           @click.self="closeFlag">

        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md shadow-2xl">
          <!-- Header -->
          <div class="px-6 py-5 border-b border-slate-700 flex items-center justify-between">
            <div>
              <h3 class="font-semibold text-white">Flag for Follow-up</h3>
              <p class="text-xs text-slate-400 mt-0.5">
                Count #{{ flagTarget?.id }} — {{ flagTarget?.staff?.full_name ?? '' }}
              </p>
            </div>
            <button @click="closeFlag"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-slate-800 transition">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="px-6 py-5 space-y-4">
            <!-- Snapshot -->
            <div class="grid grid-cols-3 gap-3">
              <div class="bg-slate-800 rounded-xl p-3 text-center">
                <div class="text-xs text-slate-500 mb-1">Physical</div>
                <div class="text-sm font-mono font-bold text-white">{{ fmt(flagTarget?.physical_total ?? 0) }}</div>
              </div>
              <div class="bg-slate-800 rounded-xl p-3 text-center">
                <div class="text-xs text-slate-500 mb-1">System</div>
                <div class="text-sm font-mono text-slate-300">{{ fmt(flagTarget?.system_balance ?? 0) }}</div>
              </div>
              <div class="bg-slate-800 rounded-xl p-3 text-center">
                <div class="text-xs text-slate-500 mb-1">Difference</div>
                <div class="text-sm font-mono font-bold"
                     :class="{
                       'text-emerald-400': flagTarget?.status === 'balanced',
                       'text-amber-400':   flagTarget?.status === 'overage',
                       'text-red-400':     flagTarget?.status === 'shortage',
                     }">
                  {{ (flagTarget?.difference ?? 0) >= 0 ? '+' : '' }}{{ fmt(flagTarget?.difference ?? 0) }}
                </div>
              </div>
            </div>

            <!-- Reason -->
            <div>
              <label class="block text-xs text-slate-400 uppercase tracking-wide mb-2">
                Reason for flagging <span class="text-red-400">*</span>
              </label>
              <textarea v-model="flagForm.flag_reason" rows="4"
                        class="w-full bg-slate-800 border border-slate-600 text-white rounded-xl px-4 py-3 text-sm
                               resize-none focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                        placeholder="Describe the discrepancy or reason for escalation…"></textarea>
              <p v-if="flagForm.errors.flag_reason" class="text-red-400 text-xs mt-1">
                {{ flagForm.errors.flag_reason }}
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t border-slate-700 flex gap-3">
            <button @click="closeFlag"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300 text-sm
                           hover:bg-slate-800 transition">
              Cancel
            </button>
            <button @click="submitFlag"
                    :disabled="flagForm.processing || !flagForm.flag_reason.trim()"
                    class="flex-[2] px-4 py-2.5 rounded-xl bg-red-700 hover:bg-red-600 text-white text-sm
                           font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed">
              <span v-if="flagForm.processing">Flagging…</span>
              <span v-else>⚑ Confirm Flag</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
