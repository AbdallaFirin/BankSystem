<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    customers: { type: Object, default: () => ({}) },
    filters:   { type: Object, default: () => ({}) },
})

const filters = reactive({
    search:     props.filters.search     ?? '',
    kyc_status: props.filters.kyc_status ?? '',
})

let debounceTimer = null
watch(filters, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 300)
})

function applyFilters() {
    router.get(route('staff.compliance.customers'), { ...filters }, { preserveState: true, replace: true })
}

function clearFilters() {
    filters.search     = ''
    filters.kyc_status = ''
    router.get(route('staff.compliance.customers'), {}, { preserveState: true, replace: true })
}

const kycBadge = (status) => ({
    pending:      'bg-amber-900/40   text-amber-400   border-amber-700/40',
    under_review: 'bg-blue-900/40    text-blue-400    border-blue-700/40',
    verified:     'bg-emerald-900/40 text-emerald-400 border-emerald-700/40',
    rejected:     'bg-red-900/40     text-red-400     border-red-700/40',
}[status] ?? 'bg-slate-800 text-slate-400 border-slate-700')
</script>

<template>
  <Head title="Customer Review — Compliance" />
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
            <div class="w-10 h-10 rounded-xl bg-blue-900/30 flex items-center justify-center">
              <i class="ti ti-users text-blue-400 text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">Customer Review</h1>
              <p class="text-xs text-slate-400">Search and inspect customer KYC profiles</p>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
          <div class="relative flex-1 min-w-48">
            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input v-model="filters.search" type="text" placeholder="Name, ID number, phone…"
                   class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2.5 text-sm text-white
                          placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#E2635A]/40 focus:border-[#E2635A]/60 transition-colors" />
          </div>
          <select v-model="filters.kyc_status"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#E2635A]/40 transition-colors">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="under_review">Under Review</option>
            <option value="verified">Verified</option>
            <option value="rejected">Rejected</option>
          </select>
          <button v-if="filters.search || filters.kyc_status" @click="clearFilters"
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
                  <th class="px-5 py-3 text-left">Customer</th>
                  <th class="px-4 py-3 text-left">ID Number</th>
                  <th class="px-4 py-3 text-left">Phone</th>
                  <th class="px-4 py-3 text-center">Accounts</th>
                  <th class="px-4 py-3 text-center">KYC Status</th>
                  <th class="px-4 py-3 text-center">Registered</th>
                  <th class="px-4 py-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="c in customers.data" :key="c.id" class="hover:bg-slate-800/30 transition-colors">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-sm font-bold text-[#C9A84C] shrink-0">
                        {{ c.full_name.charAt(0) }}
                      </div>
                      <span class="font-medium text-white">{{ c.full_name }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 font-mono text-slate-300 text-xs">{{ c.id_number }}</td>
                  <td class="px-4 py-3 text-slate-400 text-xs">{{ c.phone }}</td>
                  <td class="px-4 py-3 text-center text-slate-300">{{ c.accounts?.length ?? 0 }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full border font-semibold uppercase"
                          :class="kycBadge(c.kyc_status)">
                      {{ c.kyc_status?.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center text-slate-400 text-xs">
                    {{ new Date(c.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <Link :href="route('staff.compliance.customer-detail', c.id)"
                          class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs text-slate-300 transition-colors">
                      Review
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty state -->
          <div v-if="!customers.data?.length" class="py-16 text-center text-slate-500">
            <i class="ti ti-users-group text-4xl mb-3 block"></i>
            <p>No customers match your filters.</p>
          </div>

          <!-- Pagination -->
          <div v-if="customers.last_page > 1" class="px-5 py-4 border-t border-slate-800 flex items-center justify-between">
            <span class="text-xs text-slate-400">{{ customers.from }}–{{ customers.to }} of {{ customers.total }}</span>
            <div class="flex gap-1">
              <Link v-if="customers.prev_page_url" :href="customers.prev_page_url"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">
                ← Prev
              </Link>
              <Link v-if="customers.next_page_url" :href="customers.next_page_url"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">
                Next →
              </Link>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
