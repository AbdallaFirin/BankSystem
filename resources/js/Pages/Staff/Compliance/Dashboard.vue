<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    stats:          { type: Object, default: () => ({}) },
    recent_sars:    { type: Array,  default: () => [] },
    recent_flagged: { type: Array,  default: () => [] },
})

const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const riskColor = (level) => ({
    low:      'text-emerald-400 bg-emerald-900/30 border-emerald-700/40',
    medium:   'text-amber-400   bg-amber-900/30   border-amber-700/40',
    high:     'text-orange-400  bg-orange-900/30  border-orange-700/40',
    critical: 'text-red-400     bg-red-900/30     border-red-700/40',
}[level] ?? 'text-slate-400 bg-slate-800 border-slate-700')

const sarStatusColor = (s) => ({
    draft:        'text-slate-400  bg-slate-800',
    submitted:    'text-amber-400  bg-amber-900/30',
    under_review: 'text-blue-400   bg-blue-900/30',
    closed:       'text-emerald-400 bg-emerald-900/30',
}[s] ?? 'text-slate-400 bg-slate-800')

const typeLabel = (t) => ({
    structuring:      'Structuring',
    money_laundering: 'Money Laundering',
    fraud:            'Fraud',
    identity_theft:   'Identity Theft',
    unusual_activity: 'Unusual Activity',
    other:            'Other',
}[t] ?? t)
</script>

<template>
  <Head title="Compliance Hub" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-7xl mx-auto px-5 py-7 space-y-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#E2635A]/20 flex items-center justify-center">
              <i class="ti ti-shield-lock text-[#E2635A] text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">Compliance Hub</h1>
              <p class="text-xs text-slate-400">AML & KYC oversight dashboard</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Link :href="route('staff.compliance.index')"
                  class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-sm rounded-xl transition-colors flex items-center gap-2">
              <i class="ti ti-file-search"></i> KYC Queue
              <span v-if="stats.pending_kyc > 0"
                    class="px-1.5 py-0.5 rounded-full bg-amber-500 text-[#070F1A] text-xs font-bold leading-none">
                {{ stats.pending_kyc }}
              </span>
            </Link>
            <Link :href="route('staff.compliance.reports')"
                  class="px-4 py-2 bg-[#E2635A]/20 hover:bg-[#E2635A]/30 border border-[#E2635A]/30 text-[#E2635A] text-sm rounded-xl transition-colors flex items-center gap-2">
              <i class="ti ti-plus"></i> File SAR
            </Link>
          </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <div class="bg-slate-900 border border-amber-700/40 rounded-xl px-4 py-3">
            <p class="text-xs text-slate-400 mb-1">Pending KYC</p>
            <p class="text-2xl font-bold" :class="stats.pending_kyc > 0 ? 'text-amber-400' : 'text-white'">
              {{ stats.pending_kyc ?? 0 }}
            </p>
          </div>
          <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
            <p class="text-xs text-slate-400 mb-1">Verified This Month</p>
            <p class="text-2xl font-bold text-emerald-400">{{ stats.verified_month ?? 0 }}</p>
          </div>
          <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
            <p class="text-xs text-slate-400 mb-1">Rejected Total</p>
            <p class="text-2xl font-bold text-slate-300">{{ stats.rejected_total ?? 0 }}</p>
          </div>
          <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
            <p class="text-xs text-slate-400 mb-1">Flagged Txns</p>
            <p class="text-2xl font-bold" :class="stats.flagged_txn > 0 ? 'text-orange-400' : 'text-white'">
              {{ stats.flagged_txn ?? 0 }}
            </p>
          </div>
          <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
            <p class="text-xs text-slate-400 mb-1">Open SARs</p>
            <p class="text-2xl font-bold" :class="stats.open_sars > 0 ? 'text-blue-400' : 'text-white'">
              {{ stats.open_sars ?? 0 }}
            </p>
          </div>
          <div class="bg-slate-900 border border-red-700/40 rounded-xl px-4 py-3">
            <p class="text-xs text-slate-400 mb-1">Critical SARs</p>
            <p class="text-2xl font-bold" :class="stats.critical_sars > 0 ? 'text-red-400' : 'text-white'">
              {{ stats.critical_sars ?? 0 }}
            </p>
          </div>
        </div>

        <!-- Two-column content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

          <!-- Recent SARs -->
          <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
              <h2 class="font-semibold text-white">Recent SARs</h2>
              <Link :href="route('staff.compliance.reports')" class="text-xs text-[#E2635A] hover:underline">View all →</Link>
            </div>
            <div class="divide-y divide-slate-800">
              <div v-for="sar in recent_sars" :key="sar.id"
                   class="px-6 py-4 hover:bg-slate-800/30 transition-colors">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-white font-mono">{{ sar.reference }}</p>
                    <p class="text-xs text-slate-400 mt-0.5 truncate">
                      {{ typeLabel(sar.type) }}
                      <span v-if="sar.customer"> · {{ sar.customer.full_name }}</span>
                    </p>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold uppercase"
                          :class="riskColor(sar.risk_level)">{{ sar.risk_level }}</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold"
                          :class="sarStatusColor(sar.status)">{{ sar.status.replace('_',' ') }}</span>
                  </div>
                </div>
              </div>
              <div v-if="!recent_sars.length" class="px-6 py-8 text-center text-slate-500 text-sm">
                No SARs filed yet.
              </div>
            </div>
          </div>

          <!-- Recent Flagged Transactions -->
          <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
              <h2 class="font-semibold text-white">Flagged Transactions</h2>
              <Link :href="route('staff.compliance.transactions', { is_flagged: 'true' })"
                    class="text-xs text-[#E2635A] hover:underline">View all →</Link>
            </div>
            <div class="divide-y divide-slate-800">
              <div v-for="txn in recent_flagged" :key="txn.id"
                   class="px-6 py-4 hover:bg-slate-800/30 transition-colors">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-white font-mono">{{ txn.reference }}</p>
                    <p class="text-xs text-slate-400 mt-0.5 truncate">
                      {{ txn.primary_account?.customer?.full_name ?? '—' }}
                    </p>
                    <p v-if="txn.flag_reason" class="text-xs text-orange-400/80 mt-1 truncate">
                      <i class="ti ti-flag-3 mr-1"></i>{{ txn.flag_reason }}
                    </p>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-orange-400">${{ fmt(txn.amount) }}</p>
                    <p class="text-[10px] text-slate-500 capitalize mt-0.5">{{ txn.type }}</p>
                  </div>
                </div>
              </div>
              <div v-if="!recent_flagged.length" class="px-6 py-8 text-center text-slate-500 text-sm">
                No flagged transactions.
              </div>
            </div>
          </div>
        </div>

        <!-- Quick links -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <Link :href="route('staff.compliance.index')"
                class="bg-slate-900 border border-slate-700/60 hover:border-[#E2635A]/40 rounded-xl p-4 transition-colors group">
            <i class="ti ti-file-search text-2xl text-[#E2635A] mb-2 block"></i>
            <p class="text-sm font-medium text-white">KYC Queue</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ stats.pending_kyc }} pending</p>
          </Link>
          <Link :href="route('staff.compliance.customers')"
                class="bg-slate-900 border border-slate-700/60 hover:border-[#E2635A]/40 rounded-xl p-4 transition-colors group">
            <i class="ti ti-users text-2xl text-blue-400 mb-2 block"></i>
            <p class="text-sm font-medium text-white">Customer Review</p>
            <p class="text-xs text-slate-400 mt-0.5">All customers</p>
          </Link>
          <Link :href="route('staff.compliance.transactions')"
                class="bg-slate-900 border border-slate-700/60 hover:border-orange-700/40 rounded-xl p-4 transition-colors">
            <i class="ti ti-activity text-2xl text-orange-400 mb-2 block"></i>
            <p class="text-sm font-medium text-white">Transaction Monitor</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ stats.flagged_txn }} flagged</p>
          </Link>
          <Link :href="route('staff.compliance.reports')"
                class="bg-slate-900 border border-slate-700/60 hover:border-red-700/40 rounded-xl p-4 transition-colors">
            <i class="ti ti-report-analytics text-2xl text-red-400 mb-2 block"></i>
            <p class="text-sm font-medium text-white">SAR Reports</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ stats.open_sars }} open</p>
          </Link>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
