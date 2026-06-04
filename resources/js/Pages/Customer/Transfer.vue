<template>
  <CustomerLayout>
    <div class="max-w-2xl mx-auto space-y-4 sm:space-y-5">

      <!-- ── Header ── -->
      <div>
        <h2 class="text-lg font-bold text-slate-800">Transfer Funds</h2>
        <p class="text-sm text-slate-500 mt-0.5">Send money between your accounts or to another account holder.</p>
      </div>

      <!-- ── Transfer Type Tabs ── -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-1.5 flex gap-1.5">
        <button
          @click="transferType = 'own'"
          class="flex-1 py-2.5 px-4 rounded-xl text-sm font-semibold transition-all"
          :class="transferType === 'own'
            ? 'bg-[#0B1929] text-white shadow-sm'
            : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
        >
          <i class="ti ti-arrows-exchange mr-1.5"></i>
          My Accounts
        </button>
        <button
          @click="transferType = 'external'"
          class="flex-1 py-2.5 px-4 rounded-xl text-sm font-semibold transition-all"
          :class="transferType === 'external'
            ? 'bg-[#0B1929] text-white shadow-sm'
            : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
        >
          <i class="ti ti-send mr-1.5"></i>
          To Another Customer
        </button>
      </div>

      <!-- ── Form Card ── -->
      <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-6 space-y-4 sm:space-y-5">

        <!-- From Account -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">From Account</label>
          <div class="space-y-2">
            <label
              v-for="acc in accounts"
              :key="acc.id"
              class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all"
              :class="form.from_account_id === acc.id
                ? 'border-[#0B1929] bg-[#0B1929]/5'
                : 'border-slate-200 hover:border-slate-300'"
            >
              <input type="radio" v-model="form.from_account_id" :value="acc.id" class="hidden" />
              <div class="w-9 h-9 rounded-lg bg-[#0B1929] flex items-center justify-center flex-shrink-0">
                <i class="ti ti-credit-card text-[#C9A84C]"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 font-mono">{{ acc.account_number }}</p>
                <p class="text-xs text-slate-500">{{ acc.type_name }}</p>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="text-sm font-bold" :class="acc.balance < 0 ? 'text-red-600' : 'text-slate-800'">
                  ${{ formatMoney(acc.balance) }}
                </p>
                <p class="text-[10px] text-slate-400">available</p>
              </div>
              <div class="w-4 h-4 rounded-full border-2 flex-shrink-0"
                :class="form.from_account_id === acc.id ? 'border-[#0B1929] bg-[#0B1929]' : 'border-slate-300'">
                <div v-if="form.from_account_id === acc.id" class="w-full h-full rounded-full bg-white scale-[0.4]"></div>
              </div>
            </label>
          </div>
          <p v-if="form.errors.from_account_id" class="mt-1 text-xs text-red-500">{{ form.errors.from_account_id }}</p>
        </div>

        <!-- Divider with arrow -->
        <div class="flex items-center gap-3">
          <div class="flex-1 h-px bg-slate-100"></div>
          <div class="w-8 h-8 rounded-full bg-[#C9A84C]/10 flex items-center justify-center">
            <i class="ti ti-arrow-down text-[#C9A84C] text-sm"></i>
          </div>
          <div class="flex-1 h-px bg-slate-100"></div>
        </div>

        <!-- To Account — Own -->
        <div v-if="transferType === 'own'">
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">To Account</label>
          <div class="space-y-2">
            <label
              v-for="acc in destinationAccounts"
              :key="acc.id"
              class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all"
              :class="form.to_own_account_id === acc.id
                ? 'border-emerald-600 bg-emerald-50/50'
                : 'border-slate-200 hover:border-slate-300'"
            >
              <input type="radio" v-model="form.to_own_account_id" :value="acc.id" class="hidden" />
              <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-credit-card text-emerald-600"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 font-mono">{{ acc.account_number }}</p>
                <p class="text-xs text-slate-500">{{ acc.type_name }}</p>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="text-sm font-bold" :class="acc.balance < 0 ? 'text-red-600' : 'text-slate-800'">
                  ${{ formatMoney(acc.balance) }}
                </p>
              </div>
              <div class="w-4 h-4 rounded-full border-2 flex-shrink-0"
                :class="form.to_own_account_id === acc.id ? 'border-emerald-600 bg-emerald-600' : 'border-slate-300'">
              </div>
            </label>
            <div v-if="destinationAccounts.length === 0" class="p-4 text-center text-sm text-slate-400 rounded-xl bg-slate-50 border border-dashed border-slate-200">
              <i class="ti ti-credit-card-off block text-2xl mb-1 text-slate-300"></i>
              No other active accounts to transfer to.
            </div>
          </div>
          <p v-if="form.errors.to_own_account_id" class="mt-1 text-xs text-red-500">{{ form.errors.to_own_account_id }}</p>
        </div>

        <!-- To Account — External -->
        <div v-if="transferType === 'external'">
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Recipient Account Number</label>

          <!-- Beneficiary quick-select -->
          <div v-if="beneficiaries.length" class="mb-2">
            <p class="text-[11px] text-slate-400 mb-1.5 uppercase tracking-wider">Quick select from saved</p>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="b in beneficiaries" :key="b.id" type="button"
                @click="selectBeneficiary(b)"
                class="text-xs px-3 py-1.5 rounded-full border transition-colors"
                :class="form.to_account_number === b.account_number
                  ? 'bg-[#0B1929] border-[#0B1929] text-white'
                  : 'border-slate-300 text-slate-600 hover:border-[#C9A84C] hover:text-[#0B1929]'">
                <i class="ti ti-user mr-1"></i>{{ b.nickname }}
              </button>
            </div>
          </div>

          <div class="relative">
            <input
              v-model="form.to_account_number"
              @input="onAccountNumberInput"
              type="text"
              placeholder="Enter account number (e.g. CAR000123)"
              class="w-full px-4 py-2.5 border rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40 focus:border-[#C9A84C] transition-colors"
              :class="lookupResult?.found === true ? 'border-emerald-400 bg-emerald-50' :
                      lookupResult?.found === false ? 'border-red-300' : 'border-slate-300'"
            />
            <div v-if="lookupLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
              <i class="ti ti-loader-2 animate-spin text-slate-400"></i>
            </div>
          </div>
          <!-- Lookup result -->
          <div v-if="lookupResult?.found === true" class="mt-2 flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
            <i class="ti ti-circle-check text-emerald-600"></i>
            Account holder: <strong>{{ lookupResult.holder_name }}</strong>
          </div>
          <div v-if="lookupResult?.found === false" class="mt-2 flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
            <i class="ti ti-alert-circle text-red-500"></i>
            {{ lookupResult.message }}
          </div>
          <p v-if="form.errors.to_account_number" class="mt-1 text-xs text-red-500">{{ form.errors.to_account_number }}</p>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Amount</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-sm">$</span>
            <input
              v-model="form.amount"
              type="number"
              min="0.01"
              step="0.01"
              placeholder="0.00"
              class="w-full pl-8 pr-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 transition-colors"
              :class="exceedsBalance
                ? 'border-red-400 bg-red-50 focus:ring-red-400/40 focus:border-red-400'
                : form.errors.amount
                  ? 'border-red-400 focus:ring-red-400/40 focus:border-red-400'
                  : 'border-slate-300 focus:ring-[#C9A84C]/40 focus:border-[#C9A84C]'"
            />
          </div>
          <!-- Available balance hint -->
          <div class="mt-1 flex items-center justify-between">
            <p v-if="selectedFromAccount" class="text-xs text-slate-400">
              Available: <span class="font-semibold text-slate-600">${{ formatMoney(selectedFromAccount.balance) }}</span>
            </p>
            <p v-if="exceedsBalance" class="text-xs text-red-600 font-semibold flex items-center gap-1">
              <i class="ti ti-alert-triangle"></i>
              Exceeds available balance by ${{ formatMoney(enteredAmount - selectedFromAccount.balance) }}
            </p>
          </div>
          <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">{{ form.errors.amount }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Description <span class="font-normal text-slate-400">(optional)</span></label>
          <input
            v-model="form.description"
            type="text"
            maxlength="255"
            placeholder="e.g. Rent payment, savings top-up…"
            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40 focus:border-[#C9A84C] transition-colors"
          />
        </div>

        <!-- Summary preview -->
        <div v-if="canShowSummary"
          class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 space-y-1.5 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500">From</span>
            <span class="font-mono font-semibold text-slate-700">{{ selectedFromAccount?.account_number }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">To</span>
            <span class="font-mono font-semibold text-slate-700">
              {{ transferType === 'own' ? selectedToOwnAccount?.account_number : form.to_account_number }}
              <span v-if="transferType === 'external' && lookupResult?.found" class="font-sans text-xs text-slate-500 ml-1">({{ lookupResult.holder_name }})</span>
            </span>
          </div>
          <div class="flex justify-between border-t border-slate-200 pt-1.5 mt-1">
            <span class="font-semibold text-slate-700">Amount</span>
            <span class="font-bold text-[#0B1929]">${{ form.amount ? formatMoney(Number(form.amount)) : '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Balance After</span>
            <span class="font-semibold" :class="balanceAfter < 0 ? 'text-red-600' : 'text-slate-700'">
              ${{ formatMoney(balanceAfter) }}
            </span>
          </div>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="form.processing || !canSubmit"
          class="w-full bg-[#0B1929] hover:bg-[#1a3a5c] disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm"
        >
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <i v-else class="ti ti-send text-base"></i>
          {{ form.processing ? 'Processing…' : 'Send Transfer' }}
        </button>
      </form>
    </div>
  </CustomerLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

const props = defineProps({
  accounts:      { type: Array, default: () => [] },
  beneficiaries: { type: Array, default: () => [] },
})

const transferType = ref('own')

const form = useForm({
  from_account_id:   null,
  transfer_type:     'own',
  to_account_number: '',
  to_own_account_id: null,
  amount:            '',
  description:       '',
})

// Auto-select first account on mount
onMounted(() => {
  if (props.accounts.length > 0) {
    form.from_account_id = props.accounts[0].id
  }
})

// Keep form.transfer_type in sync with tab
watch(transferType, (val) => {
  form.transfer_type = val
  form.to_account_number = ''
  form.to_own_account_id = null
  lookupResult.value = null
})

// Source account selection helper
const selectedFromAccount = computed(() =>
  props.accounts.find(a => a.id === form.from_account_id) ?? null
)

// Destination accounts = all except the selected source
const destinationAccounts = computed(() =>
  props.accounts.filter(a => a.id !== form.from_account_id)
)

const selectedToOwnAccount = computed(() =>
  props.accounts.find(a => a.id === form.to_own_account_id) ?? null
)

// Balance checks
const enteredAmount = computed(() => Number(form.amount) || 0)
const balanceAfter  = computed(() => (selectedFromAccount.value?.balance ?? 0) - enteredAmount.value)
const exceedsBalance = computed(() => {
  if (!selectedFromAccount.value || enteredAmount.value <= 0) return false
  return enteredAmount.value > selectedFromAccount.value.balance
})

// ── Beneficiary quick-select ───────────────────
function selectBeneficiary(b) {
  form.to_account_number = b.account_number
  lookupResult.value = { found: true, holder_name: b.account_holder_name }
}

// ── External account lookup ────────────────────
const lookupResult = ref(null)
const lookupLoading = ref(false)
let lookupTimer = null

function onAccountNumberInput() {
  lookupResult.value = null
  clearTimeout(lookupTimer)
  const num = form.to_account_number.trim()
  if (num.length < 3) return
  lookupLoading.value = true
  lookupTimer = setTimeout(async () => {
    try {
      const res = await fetch(route('customer.transfer.lookup') + '?account=' + encodeURIComponent(num))
      lookupResult.value = await res.json()
    } catch {
      lookupResult.value = null
    } finally {
      lookupLoading.value = false
    }
  }, 500)
}

// ── Submit guard ────────────────────────────────
const canSubmit = computed(() => {
  if (!form.from_account_id || !form.amount || Number(form.amount) <= 0) return false
  if (exceedsBalance.value) return false
  if (transferType.value === 'own') return !!form.to_own_account_id && form.to_own_account_id !== form.from_account_id
  if (transferType.value === 'external') return !!form.to_account_number && lookupResult.value?.found === true
  return false
})

const canShowSummary = computed(() => {
  if (!form.from_account_id || !form.amount || Number(form.amount) <= 0) return false
  if (transferType.value === 'own') return !!form.to_own_account_id && form.to_own_account_id !== form.from_account_id
  if (transferType.value === 'external') return lookupResult.value?.found === true
  return false
})

function submit() {
  form.transfer_type = transferType.value
  form.post(route('customer.transfer.post'), {
    onSuccess: () => {
      form.reset()
      lookupResult.value = null
      transferType.value = 'own'
    },
  })
}

function formatMoney(v) {
  return Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
