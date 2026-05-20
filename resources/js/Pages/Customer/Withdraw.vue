<template>
  <CustomerLayout>
    <div class="max-w-xl mx-auto space-y-5">

      <!-- Header -->
      <div>
        <h2 class="text-lg font-bold text-slate-800">Withdrawal Request</h2>
        <p class="text-sm text-slate-500 mt-0.5">Your account will be debited immediately. Visit any branch to collect your cash.</p>
      </div>

      <!-- Info banner -->
      <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
        <i class="ti ti-alert-triangle text-amber-500 text-lg mt-0.5 shrink-0"></i>
        <p>Your account balance is debited immediately on submission. Bring your reference number to any Gobaad Bank branch to receive the cash.</p>
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
                ? 'border-red-500 bg-red-50/50'
                : 'border-slate-200 hover:border-slate-300'"
            >
              <input type="radio" v-model="form.account_id" :value="acc.id" class="hidden" />
              <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                <i class="ti ti-arrow-up-circle text-red-600 text-lg"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 font-mono">{{ acc.account_number }}</p>
                <p class="text-xs text-slate-500">{{ acc.type_name }}</p>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-bold" :class="acc.balance < 0 ? 'text-red-600' : 'text-slate-800'">${{ fmt(acc.balance) }}</p>
                <p class="text-[10px] text-slate-400">available</p>
              </div>
              <div class="w-4 h-4 rounded-full border-2 shrink-0"
                   :class="form.account_id === acc.id ? 'border-red-500 bg-red-500' : 'border-slate-300'">
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
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Amount to Withdraw</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-sm">$</span>
            <input
              v-model="form.amount"
              type="number" min="1" step="0.01" placeholder="0.00"
              class="w-full pl-8 pr-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400/40 focus:border-red-400 transition-colors"
              :class="form.errors.amount ? 'border-red-400' : 'border-slate-300'"
            />
          </div>
          <!-- Balance hint -->
          <p v-if="selectedAccount" class="mt-1 text-xs text-slate-400">
            Available balance: <span class="font-semibold" :class="selectedAccount.balance < 0 ? 'text-red-500' : 'text-slate-600'">${{ fmt(selectedAccount.balance) }}</span>
            <span v-if="form.amount && Number(form.amount) > selectedAccount.balance" class="ml-2 text-red-500 font-semibold">
              ⚠ Exceeds balance
            </span>
          </p>
          <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">{{ form.errors.amount }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
            Note <span class="font-normal text-slate-400">(optional)</span>
          </label>
          <input
            v-model="form.description"
            type="text" maxlength="255" placeholder="e.g. Rent payment, personal use…"
            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400/40 focus:border-red-400 transition-colors"
          />
        </div>

        <!-- Summary -->
        <div v-if="form.account_id && form.amount > 0"
             class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm space-y-1">
          <div class="flex justify-between">
            <span class="text-red-700">From Account</span>
            <span class="font-mono font-semibold text-red-900">{{ selectedAccount?.account_number }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-red-700">Current Balance</span>
            <span class="font-semibold text-red-900">${{ fmt(selectedAccount?.balance) }}</span>
          </div>
          <div class="flex justify-between border-t border-red-100 pt-1 mt-1">
            <span class="font-semibold text-red-700">Balance After</span>
            <span class="font-bold" :class="balanceAfter < 0 ? 'text-red-600' : 'text-red-900'">${{ fmt(balanceAfter) }}</span>
          </div>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="form.processing || !form.account_id || !form.amount"
          class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm"
        >
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <i v-else class="ti ti-arrow-up-circle text-base"></i>
          {{ form.processing ? 'Processing…' : 'Confirm Withdrawal' }}
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

const balanceAfter = computed(() => {
  if (!selectedAccount.value || !form.amount) return selectedAccount.value?.balance ?? 0
  return selectedAccount.value.balance - Number(form.amount)
})

function submit() {
  form.post(route('customer.withdraw.post'), {
    onSuccess: () => form.reset(),
  })
}

function fmt(v) {
  return Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
