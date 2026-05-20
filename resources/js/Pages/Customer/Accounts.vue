<template>
  <CustomerLayout>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800">My Accounts</h1>
      <p class="text-sm text-slate-500 mt-1">All your accounts with current balances and details.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div v-for="acc in accounts" :key="acc.id"
        class="bg-white rounded-2xl border shadow-sm overflow-hidden"
        :class="acc.is_frozen ? 'border-red-200' : 'border-slate-200'"
      >
        <!-- Card header -->
        <div class="px-6 py-4 flex items-center justify-between border-b"
          :class="acc.is_frozen ? 'bg-red-50 border-red-100' : 'bg-slate-50 border-slate-100'">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#0B1929] flex items-center justify-center">
              <i class="ti ti-credit-card text-[#C9A84C] text-lg"></i>
            </div>
            <div>
              <p class="font-bold text-slate-800 text-sm">{{ acc.type_name }}</p>
              <p class="font-mono text-xs text-slate-500">{{ acc.account_number }}</p>
            </div>
          </div>
          <div class="flex flex-col items-end gap-1">
            <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full"
              :class="{
                'bg-emerald-100 text-emerald-700': acc.status === 'active' && !acc.is_frozen,
                'bg-red-100 text-red-700':         acc.is_frozen,
                'bg-slate-100 text-slate-600':     acc.status !== 'active',
              }">
              {{ acc.is_frozen ? 'Frozen' : acc.status }}
            </span>
          </div>
        </div>

        <!-- Balance -->
        <div class="px-6 py-5">
          <p class="text-xs text-slate-400 mb-1 uppercase tracking-wider">Current Balance</p>
          <p class="text-3xl font-bold" :class="acc.balance < 0 ? 'text-red-600' : 'text-slate-800'">
            <span class="text-base font-normal text-slate-400">$</span>{{ formatAmount(Math.abs(acc.balance)) }}
            <span v-if="acc.balance < 0" class="text-sm font-normal text-red-500 ml-1">overdraft</span>
          </p>
        </div>

        <!-- Details -->
        <div class="px-6 pb-5 grid grid-cols-2 gap-3">
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wider mb-0.5">Opened</p>
            <p class="text-sm font-semibold text-slate-700">{{ acc.opened_at ?? '—' }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wider mb-0.5">Branch</p>
            <p class="text-sm font-semibold text-slate-700 truncate">{{ acc.branch_name }}</p>
          </div>
        </div>

        <!-- Frozen notice -->
        <div v-if="acc.is_frozen"
          class="mx-6 mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3 flex items-start gap-2">
          <i class="ti ti-lock text-red-600 text-base flex-shrink-0 mt-0.5"></i>
          <p class="text-xs text-red-700">
            This account is frozen. Transactions are suspended. Contact your nearest branch for assistance.
          </p>
        </div>
      </div>

      <div v-if="!accounts.length"
        class="col-span-full bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-12 text-center text-slate-400">
        <i class="ti ti-credit-card-off text-4xl mb-3 block"></i>
        <p class="text-sm">No accounts found.</p>
      </div>
    </div>
  </CustomerLayout>
</template>

<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

defineProps({
  accounts: { type: Array, default: () => [] },
})

function formatAmount(n) {
  return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
