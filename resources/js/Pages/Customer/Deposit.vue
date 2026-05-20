<template>
  <CustomerLayout>
    <div class="max-w-xl mx-auto space-y-5">

      <!-- Header -->
      <div>
        <h2 class="text-lg font-bold text-slate-800">Deposit Request</h2>
        <p class="text-sm text-slate-500 mt-0.5">Submit a deposit request then visit any branch with your cash to complete it.</p>
      </div>

      <!-- Info banner -->
      <div class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-800">
        <i class="ti ti-info-circle text-blue-500 text-lg mt-0.5 shrink-0"></i>
        <p>After submitting, bring the reference number and cash to any Gobaad Bank branch. A teller will credit your account on the spot.</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-5">

        <!-- Account selection -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Select Account</label>
          <div class="space-y-2">
            <label
              v-for="acc in accounts" :key="acc.id"
              class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all"
              :class="form.account_id === acc.id
                ? 'border-[#0B1929] bg-[#0B1929]/5'
                : 'border-slate-200 hover:border-slate-300'"
            >
              <input type="radio" v-model="form.account_id" :value="acc.id" class="hidden" />
              <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                <i class="ti ti-arrow-down-circle text-emerald-600 text-lg"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 font-mono">{{ acc.account_number }}</p>
                <p class="text-xs text-slate-500">{{ acc.type_name }}</p>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-bold text-slate-800">${{ fmt(acc.balance) }}</p>
                <p class="text-[10px] text-slate-400">current balance</p>
              </div>
              <div class="w-4 h-4 rounded-full border-2 shrink-0"
                   :class="form.account_id === acc.id ? 'border-[#0B1929] bg-[#0B1929]' : 'border-slate-300'">
              </div>
            </label>

            <div v-if="!accounts.length" class="p-6 text-center text-sm text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
              <i class="ti ti-credit-card-off text-3xl block mb-2 text-slate-300"></i>
              No active accounts available.
            </div>
          </div>
          <p v-if="form.errors.account_id" class="mt-1 text-xs text-red-500">{{ form.errors.account_id }}</p>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Amount to Deposit</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-sm">$</span>
            <input
              v-model="form.amount"
              type="number" min="1" step="0.01" placeholder="0.00"
              class="w-full pl-8 pr-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-400 transition-colors"
              :class="form.errors.amount ? 'border-red-400' : 'border-slate-300'"
            />
          </div>
          <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">{{ form.errors.amount }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
            Note <span class="font-normal text-slate-400">(optional)</span>
          </label>
          <input
            v-model="form.description"
            type="text" maxlength="255" placeholder="e.g. Monthly savings"
            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-400 transition-colors"
          />
        </div>

        <!-- Summary -->
        <div v-if="form.account_id && form.amount > 0"
             class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm space-y-1">
          <div class="flex justify-between">
            <span class="text-emerald-700">Account</span>
            <span class="font-mono font-semibold text-emerald-900">{{ selectedAccount?.account_number }}</span>
          </div>
          <div class="flex justify-between border-t border-emerald-100 pt-1 mt-1">
            <span class="font-semibold text-emerald-700">Deposit Amount</span>
            <span class="font-bold text-emerald-800">+${{ fmt(Number(form.amount)) }}</span>
          </div>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="form.processing || !form.account_id || !form.amount"
          class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm"
        >
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <i v-else class="ti ti-arrow-down-circle text-base"></i>
          {{ form.processing ? 'Submitting…' : 'Submit Deposit Request' }}
        </button>
      </form>
    </div>
  </CustomerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

const props = defineProps({
  accounts: { type: Array, default: () => [] },
})

const form = useForm({
  account_id:  null,
  amount:      '',
  description: '',
})

const selectedAccount = computed(() => props.accounts.find(a => a.id === form.account_id) ?? null)

function submit() {
  form.post(route('customer.deposit.post'), {
    onSuccess: () => form.reset(),
  })
}

function fmt(v) {
  return Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
