<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, reactive, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    entries:       { type: Object, default: () => ({}) },
    stats:         { type: Object, default: () => ({}) },
    branches:      { type: Array,  default: () => [] },
    filters:       { type: Object, default: () => ({}) },
    my_branch_id:  { type: Number, default: null },
})

const filters = reactive({
    status:    props.filters.status    ?? '',
    date_from: props.filters.date_from ?? '',
    date_to:   props.filters.date_to   ?? '',
})

let debounce = null
watch(filters, () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        router.get(route('staff.branch.clearing.index'), { ...filters }, { preserveState: true, replace: true })
    }, 300)
})

/* ── Settle modal ── */
const settleTarget  = ref(null)
const showSettle    = ref(false)
const settleForm    = useForm({ notes: '' })

function openSettle(entry) {
    settleTarget.value = entry
    settleForm.notes   = ''
    showSettle.value   = true
}

function submitSettle() {
    settleForm.post(route('staff.branch.clearing.settle', settleTarget.value.id), {
        onSuccess: () => { showSettle.value = false },
    })
}

const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
</script>

<template>
  <Head title="Inter-Branch Clearing" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-7xl mx-auto px-5 py-8 space-y-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <Link :href="route('staff.branch.dashboard')"
                  class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-all">
              <i class="ti ti-arrow-left text-base"></i>
            </Link>
            <div class="w-10 h-10 rounded-xl bg-sky-900/30 flex items-center justify-center">
              <i class="ti ti-arrows-exchange text-sky-400 text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">Inter-Branch Clearing</h1>
              <p class="text-xs text-slate-400">Settle cross-branch transfer obligations</p>
            </div>
          </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-[#0d1f35] border border-amber-500/20 rounded-2xl p-5">
            <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-2">Pending Entries</p>
            <p class="text-3xl font-bold text-amber-400">{{ stats.pending ?? 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">Awaiting settlement</p>
          </div>
          <div class="bg-[#0d1f35] border border-white/5 rounded-2xl p-5">
            <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-2">Pending Value</p>
            <p class="text-3xl font-bold text-white">${{ fmt(stats.total_value) }}</p>
            <p class="text-xs text-slate-500 mt-1">Outstanding obligations</p>
          </div>
          <div class="bg-[#0d1f35] border border-emerald-500/20 rounded-2xl p-5">
            <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-2">Settled</p>
            <p class="text-3xl font-bold text-emerald-400">{{ stats.settled ?? 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">Completed entries</p>
          </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
          <select v-model="filters.status"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="settled">Settled</option>
          </select>
          <input v-model="filters.date_from" type="date"
                 class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors" />
          <input v-model="filters.date_to" type="date"
                 class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors" />
          <button v-if="filters.status || filters.date_from || filters.date_to"
                  @click="Object.assign(filters, { status: '', date_from: '', date_to: '' })"
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
                  <th class="px-5 py-3 text-left">ID</th>
                  <th class="px-4 py-3 text-left">From Branch</th>
                  <th class="px-4 py-3 text-left">To Branch</th>
                  <th class="px-4 py-3 text-left">Transaction</th>
                  <th class="px-4 py-3 text-right">Amount</th>
                  <th class="px-4 py-3 text-center">Status</th>
                  <th class="px-4 py-3 text-left">Created</th>
                  <th class="px-4 py-3 text-left">Settled</th>
                  <th class="px-4 py-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="entry in entries.data" :key="entry.id"
                    class="hover:bg-slate-800/30 transition-colors"
                    :class="entry.status === 'pending' ? 'bg-amber-950/10' : ''">
                  <td class="px-5 py-3 text-slate-400 text-xs">#{{ entry.id }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <span v-if="entry.from_branch?.full_code"
                            class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black tracking-wider font-mono"
                            :class="entry.from_branch_id === my_branch_id
                              ? 'bg-sky-900/50 text-sky-300 border border-sky-700/40'
                              : 'bg-slate-800 text-slate-400 border border-slate-700/40'">
                        {{ entry.from_branch.full_code }}
                      </span>
                      <span class="text-xs font-semibold"
                            :class="entry.from_branch_id === my_branch_id ? 'text-sky-400' : 'text-slate-300'">
                        {{ entry.from_branch?.branch_name ?? '—' }}
                      </span>
                      <span v-if="entry.from_branch_id === my_branch_id"
                            class="text-[9px] text-sky-500 uppercase font-bold">(you)</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <span v-if="entry.to_branch?.full_code"
                            class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black tracking-wider font-mono"
                            :class="entry.to_branch_id === my_branch_id
                              ? 'bg-emerald-900/50 text-emerald-300 border border-emerald-700/40'
                              : 'bg-slate-800 text-slate-400 border border-slate-700/40'">
                        {{ entry.to_branch.full_code }}
                      </span>
                      <span class="text-xs font-semibold"
                            :class="entry.to_branch_id === my_branch_id ? 'text-emerald-400' : 'text-slate-300'">
                        {{ entry.to_branch?.branch_name ?? '—' }}
                      </span>
                      <span v-if="entry.to_branch_id === my_branch_id"
                            class="text-[9px] text-emerald-500 uppercase font-bold">(you)</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-xs font-mono text-white">{{ entry.transaction?.reference ?? '—' }}</p>
                    <p class="text-[10px] text-slate-500 capitalize">{{ entry.transaction?.type }}</p>
                  </td>
                  <td class="px-4 py-3 text-right font-bold text-white text-sm">${{ fmt(entry.amount) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase"
                          :class="entry.status === 'settled'
                            ? 'bg-emerald-900/40 text-emerald-400'
                            : 'bg-amber-900/40 text-amber-400'">
                      {{ entry.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-xs text-slate-400">{{ fmtDate(entry.created_at) }}</td>
                  <td class="px-4 py-3 text-xs text-slate-400">
                    <span v-if="entry.settled_at">
                      {{ fmtDate(entry.settled_at) }}
                      <span class="block text-[10px] text-slate-600">by {{ entry.settled_by?.full_name ?? '—' }}</span>
                    </span>
                    <span v-else class="text-slate-600">—</span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <button v-if="entry.status === 'pending'"
                            @click="openSettle(entry)"
                            class="px-3 py-1.5 bg-sky-900/30 hover:bg-sky-800/50 border border-sky-700/40 text-sky-400 text-xs rounded-lg transition-colors whitespace-nowrap">
                      <i class="ti ti-check mr-1"></i>Settle
                    </button>
                    <span v-else class="text-slate-600 text-xs">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="!entries.data?.length" class="py-16 text-center text-slate-500">
            <i class="ti ti-arrows-exchange text-4xl mb-3 block"></i>
            <p>No clearing entries found.</p>
          </div>

          <!-- Pagination -->
          <div v-if="entries.last_page > 1" class="px-5 py-4 border-t border-slate-800 flex items-center justify-between">
            <span class="text-xs text-slate-400">{{ entries.from }}–{{ entries.to }} of {{ entries.total }}</span>
            <div class="flex gap-1">
              <Link v-if="entries.prev_page_url" :href="entries.prev_page_url"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">← Prev</Link>
              <Link v-if="entries.next_page_url" :href="entries.next_page_url"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">Next →</Link>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Settle Modal -->
    <Teleport to="body">
      <div v-if="showSettle" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-[#0B1929] border border-sky-700/30 w-full max-w-md rounded-2xl p-8 shadow-2xl">
          <div class="flex justify-between items-center mb-5">
            <div>
              <h2 class="text-lg font-bold text-white">Settle Clearing Entry</h2>
              <p class="text-xs text-slate-400 mt-0.5">
                <!-- From branch -->
                <span v-if="settleTarget?.from_branch?.full_code"
                      class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black font-mono bg-sky-900/50 text-sky-300 border border-sky-700/40 mr-1">
                  {{ settleTarget.from_branch.full_code }}
                </span>
                <span class="text-sky-400">{{ settleTarget?.from_branch?.branch_name }}</span>
                <span class="mx-1.5 text-slate-600">→</span>
                <!-- To branch -->
                <span v-if="settleTarget?.to_branch?.full_code"
                      class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black font-mono bg-emerald-900/50 text-emerald-300 border border-emerald-700/40 mr-1">
                  {{ settleTarget.to_branch.full_code }}
                </span>
                <span class="text-emerald-400">{{ settleTarget?.to_branch?.branch_name }}</span>
                <span class="mx-1.5 text-slate-600">·</span>
                <span class="font-bold text-white">${{ fmt(settleTarget?.amount) }}</span>
              </p>
              <p class="text-[10px] text-amber-400/80 mt-1.5 flex items-center gap-1">
                <i class="ti ti-building-bank text-xs"></i>
                Confirming settlement will post vault GL entries for both branches.
              </p>
            </div>
            <button @click="showSettle = false" class="text-slate-400 hover:text-white transition">
              <i class="ti ti-x text-xl"></i>
            </button>
          </div>
          <form @submit.prevent="submitSettle" class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-sky-400 uppercase tracking-wide mb-1.5 block">Settlement Notes (optional)</label>
              <textarea v-model="settleForm.notes" rows="3"
                        placeholder="Add any reconciliation notes…"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-600 transition resize-none"></textarea>
            </div>
            <button type="submit" :disabled="settleForm.processing"
                    class="w-full bg-sky-700 hover:bg-sky-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
              <i class="ti ti-check"></i> Confirm Settlement
            </button>
          </form>
        </div>
      </div>
    </Teleport>

  </AuthenticatedLayout>
</template>
