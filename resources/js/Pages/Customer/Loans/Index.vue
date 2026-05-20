<template>
  <CustomerLayout>
    <div class="max-w-5xl mx-auto space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-slate-800">My Loans</h2>
          <p class="text-sm text-slate-500 mt-0.5">View and manage your loan applications</p>
        </div>
        <Link :href="route('customer.loans.apply')"
              class="inline-flex items-center gap-2 bg-[#0B1929] text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-[#1a3a5c] transition-colors">
          <i class="ti ti-plus text-base"></i> Apply for Loan
        </Link>
      </div>

      <!-- Empty state -->
      <div v-if="loans.length === 0"
           class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="ti ti-cash text-slate-400 text-3xl"></i>
        </div>
        <h3 class="font-semibold text-slate-700 mb-1">No Loan Applications</h3>
        <p class="text-sm text-slate-500 mb-6">You haven't applied for a loan yet.</p>
        <Link :href="route('customer.loans.apply')"
              class="inline-flex items-center gap-2 bg-[#0B1929] text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-[#1a3a5c] transition-colors">
          Apply Now
        </Link>
      </div>

      <!-- Loan cards -->
      <div v-else class="space-y-4">
        <div
          v-for="loan in loans"
          :key="loan.id"
          class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 flex-wrap mb-3">
                <span class="text-lg font-bold text-slate-800">${{ formatMoney(loan.amount) }}</span>
                <span :class="statusBadge(loan.status_color)"
                      class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize">
                  {{ formatStatus(loan.status) }}
                </span>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                <div>
                  <p class="text-xs text-slate-400 uppercase tracking-wide">Account</p>
                  <p class="font-medium text-slate-700 mt-0.5">{{ loan.account_number }}</p>
                </div>
                <div>
                  <p class="text-xs text-slate-400 uppercase tracking-wide">Term</p>
                  <p class="font-medium text-slate-700 mt-0.5">{{ loan.term_months }} months</p>
                </div>
                <div>
                  <p class="text-xs text-slate-400 uppercase tracking-wide">Interest</p>
                  <p class="font-medium text-slate-700 mt-0.5">{{ loan.interest_rate }}%</p>
                </div>
                <div>
                  <p class="text-xs text-slate-400 uppercase tracking-wide">Monthly EMI</p>
                  <p class="font-medium text-slate-700 mt-0.5">${{ formatMoney(loan.monthly_payment) }}</p>
                </div>
              </div>

              <!-- Progress bar for disbursed loans -->
              <div v-if="loan.status === 'disbursed' && loan.repayments_count > 0" class="mt-4">
                <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                  <span>{{ loan.paid_count }} / {{ loan.repayments_count }} installments paid</span>
                  <span>${{ formatMoney(loan.amount_paid) }} of ${{ formatMoney(loan.total_repayable) }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2">
                  <div
                    class="bg-[#0B1929] h-2 rounded-full transition-all"
                    :style="{ width: progressPct(loan) + '%' }"
                  ></div>
                </div>
              </div>

              <p class="text-xs text-slate-400 mt-3">
                Applied: {{ loan.created_at }}
                <span v-if="loan.disbursed_at"> · Disbursed: {{ loan.disbursed_at }}</span>
              </p>
            </div>

            <Link :href="route('customer.loans.show', { id: loan.id })"
                  class="shrink-0 text-sm font-medium text-[#0B1929] hover:underline flex items-center gap-1">
              View <i class="ti ti-chevron-right text-sm"></i>
            </Link>
          </div>
        </div>
      </div>

    </div>
  </CustomerLayout>
</template>

<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  loans: { type: Array, default: () => [] },
})

function formatMoney(v) {
  return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 })
}

function formatStatus(s) {
  return s.replace(/_/g, ' ')
}

function statusBadge(color) {
  const map = {
    amber:  'bg-amber-100 text-amber-800',
    blue:   'bg-blue-100 text-blue-800',
    green:  'bg-emerald-100 text-emerald-800',
    indigo: 'bg-indigo-100 text-indigo-800',
    slate:  'bg-slate-100 text-slate-600',
    red:    'bg-red-100 text-red-700',
  }
  return map[color] ?? map.slate
}

function progressPct(loan) {
  if (!loan.total_repayable) return 0
  return Math.min(100, Math.round((loan.amount_paid / loan.total_repayable) * 100))
}
</script>
