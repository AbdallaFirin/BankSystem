<template>
  <div class="min-h-screen bg-[#070F1A] text-white">
    <div class="max-w-6xl mx-auto px-5 py-7 space-y-6">

      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Link :href="route('staff.branch.settings')"
                class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700
                       flex items-center justify-center text-slate-400 hover:text-white transition-all">
            <i class="ti ti-arrow-left text-base"></i>
          </Link>
          <div class="w-10 h-10 rounded-xl bg-amber-900/30 flex items-center justify-center">
            <i class="ti ti-history text-amber-400 text-xl"></i>
          </div>
          <div>
            <h1 class="text-xl font-bold text-white">Branch Audit Trail</h1>
            <p class="text-xs text-slate-400">All staff actions recorded in your branch</p>
          </div>
        </div>
        <span class="text-xs text-slate-500 bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-lg">
          {{ logs.total ?? 0 }} total records
        </span>
      </div>

      <!-- Filters -->
      <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-5 py-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div>
            <label class="field-label">Staff Member</label>
            <select v-model="filterForm.staff_id" class="field-input">
              <option value="">All Staff</option>
              <option v-for="s in props.branch_staff" :key="s.id" :value="s.id">{{ s.full_name }}</option>
            </select>
          </div>
          <div>
            <label class="field-label">Action / Keyword</label>
            <input v-model="filterForm.action" type="text" class="field-input" placeholder="e.g. login, deposit…" />
          </div>
          <div>
            <label class="field-label">From Date</label>
            <input v-model="filterForm.date_from" type="date" class="field-input" />
          </div>
          <div>
            <label class="field-label">To Date</label>
            <input v-model="filterForm.date_to" type="date" class="field-input" />
          </div>
        </div>
        <div class="flex gap-2 mt-3">
          <button @click="applyFilter"
                  class="bg-[#C9A84C] hover:bg-[#b8973e] text-[#070F1A] text-xs font-semibold px-4 py-2 rounded-lg transition-colors">
            Apply Filters
          </button>
          <button @click="clearFilter"
                  class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium px-4 py-2 rounded-lg transition-colors">
            Clear
          </button>
        </div>
      </div>

      <!-- Log Table -->
      <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-800/60">
              <tr class="text-xs text-slate-400 uppercase tracking-wide">
                <th class="px-5 py-3 text-left">Date / Time</th>
                <th class="px-4 py-3 text-left">Staff</th>
                <th class="px-4 py-3 text-left">Action</th>
                <th class="px-4 py-3 text-left">Description</th>
                <th class="px-4 py-3 text-left">Permission</th>
                <th class="px-4 py-3 text-center">Result</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-for="log in logs.data" :key="log.id"
                  class="hover:bg-slate-800/30 transition-colors">
                <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap">
                  {{ fmtDate(log.created_at) }}
                </td>
                <td class="px-4 py-3">
                  <span class="text-white font-medium text-xs">{{ log.staff?.full_name ?? '—' }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="font-mono text-xs text-amber-400">{{ log.action }}</span>
                </td>
                <td class="px-4 py-3 text-slate-300 text-xs max-w-[240px] truncate" :title="log.description">
                  {{ log.description ?? '—' }}
                </td>
                <td class="px-4 py-3">
                  <span v-if="log.permission_used" class="text-xs text-slate-500 font-mono">{{ log.permission_used }}</span>
                  <span v-else class="text-slate-600">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="log.result === 'success' || !log.result
                    ? 'bg-emerald-900/40 text-emerald-400'
                    : 'bg-red-900/40 text-red-400'"
                    class="text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ log.result ?? 'ok' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!logs.data?.length">
                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">
                  No audit records found for the selected filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="logs.last_page > 1"
             class="px-5 py-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-500">
          <span>Page {{ logs.current_page }} of {{ logs.last_page }}</span>
          <div class="flex gap-2">
            <Link v-if="logs.prev_page_url" :href="logs.prev_page_url"
                  class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg">← Prev</Link>
            <Link v-if="logs.next_page_url" :href="logs.next_page_url"
                  class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg">Next →</Link>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  logs:         { type: Object, default: () => ({ data: [], last_page: 1 }) },
  branch_staff: { type: Array,  default: () => [] },
  filters:      { type: Object, default: () => ({}) },
})

const filterForm = ref({
  staff_id:  props.filters.staff_id  ?? '',
  action:    props.filters.action    ?? '',
  date_from: props.filters.date_from ?? '',
  date_to:   props.filters.date_to   ?? '',
})

function applyFilter() {
  router.get(route('staff.branch.audit'), filterForm.value, { preserveState: true })
}
function clearFilter() {
  filterForm.value = { staff_id: '', action: '', date_from: '', date_to: '' }
  router.get(route('staff.branch.audit'), {})
}

const fmtDate = d => d
  ? new Date(d).toLocaleString('en-US', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
  : '—'
</script>

<style scoped>
.field-label { @apply block text-xs font-medium text-slate-400 mb-1.5; }
.field-input {
  @apply w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
         placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors;
}
</style>
