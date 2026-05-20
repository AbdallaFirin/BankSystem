<template>
  <CustomerLayout>
    <div class="space-y-6 max-w-5xl mx-auto">

      <!-- ── Profile Header ── -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="h-24 bg-gradient-to-r from-[#0B1929] to-[#1a3a5c]"></div>
        <div class="px-6 pb-6 -mt-10 flex items-end gap-4 flex-wrap">
          <div class="w-20 h-20 rounded-2xl bg-[#C9A84C]/20 border-4 border-white flex items-center justify-center text-[#C9A84C] text-2xl font-bold shadow-md shrink-0">
            {{ initials }}
          </div>
          <div class="pb-1 flex-1 min-w-0">
            <h2 class="text-xl font-bold text-slate-800 truncate">{{ customer.full_name }}</h2>
            <div class="flex items-center gap-3 mt-1 flex-wrap">
              <span class="text-sm text-slate-500">{{ customer.phone }}</span>
              <span :class="kycBadge(customer.kyc_status)"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                <i class="ti ti-shield-check"></i>
                KYC {{ customer.kyc_status ?? 'pending' }}
              </span>
              <span :class="customer.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                <i class="ti ti-circle-filled text-[8px]"></i>
                {{ customer.status ?? '—' }}
              </span>
            </div>
          </div>
          <div class="hidden sm:flex flex-col items-end text-right shrink-0 pb-1">
            <p class="text-xs text-slate-400 uppercase tracking-wider">Member since</p>
            <p class="text-sm font-semibold text-slate-700">{{ customer.created_at }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ customer.branch_name }}</p>
          </div>
        </div>
      </div>

      <!-- ── Info Grid ── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Personal Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
              <i class="ti ti-user text-blue-600"></i>
            </div>
            <h3 class="font-semibold text-slate-800">Personal Information</h3>
          </div>
          <dl class="space-y-3">
            <div v-for="row in personalRows" :key="row.label" class="flex justify-between gap-2 sm:gap-3">
              <dt class="text-xs text-slate-500 shrink-0 w-32 sm:w-40 pt-0.5">{{ row.label }}</dt>
              <dd class="text-sm text-slate-800 font-medium text-right break-words min-w-0">{{ row.value || '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Identity Document -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
              <i class="ti ti-id-badge text-amber-600"></i>
            </div>
            <h3 class="font-semibold text-slate-800">Identity Document</h3>
          </div>
          <dl class="space-y-3">
            <div v-for="row in identityRows" :key="row.label" class="flex justify-between gap-3">
              <dt class="text-xs text-slate-500 shrink-0 w-40 pt-0.5">{{ row.label }}</dt>
              <dd class="text-sm text-slate-800 font-medium text-right break-words">{{ row.value || '—' }}</dd>
            </div>
          </dl>
          <!-- KYC docs -->
          <div v-if="customer.kyc_documents?.length" class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Uploaded Documents</p>
            <ul class="space-y-1.5">
              <li v-for="doc in customer.kyc_documents" :key="doc.id"
                  class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-1.5 text-slate-600">
                  <i class="ti ti-file-description text-slate-400"></i>
                  {{ doc.doc_type }}
                </span>
                <span :class="{
                        'bg-emerald-100 text-emerald-700': doc.status === 'approved',
                        'bg-amber-100 text-amber-700':    doc.status === 'pending',
                        'bg-red-100 text-red-700':        doc.status === 'rejected',
                      }"
                      class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase">
                  {{ doc.status }}
                </span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Contact Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
              <i class="ti ti-phone text-green-600"></i>
            </div>
            <h3 class="font-semibold text-slate-800">Contact Information</h3>
          </div>
          <dl class="space-y-3">
            <div v-for="row in contactRows" :key="row.label" class="flex justify-between gap-3">
              <dt class="text-xs text-slate-500 shrink-0 w-40 pt-0.5">{{ row.label }}</dt>
              <dd class="text-sm text-slate-800 font-medium text-right break-words">{{ row.value || '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Address -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
              <i class="ti ti-map-pin text-purple-600"></i>
            </div>
            <h3 class="font-semibold text-slate-800">Address</h3>
          </div>
          <dl class="space-y-3">
            <div v-for="row in addressRows" :key="row.label" class="flex justify-between gap-3">
              <dt class="text-xs text-slate-500 shrink-0 w-40 pt-0.5">{{ row.label }}</dt>
              <dd class="text-sm text-slate-800 font-medium text-right break-words">{{ row.value || '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Employment & Financial -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
              <i class="ti ti-briefcase text-teal-600"></i>
            </div>
            <h3 class="font-semibold text-slate-800">Employment & Financial</h3>
          </div>
          <dl class="space-y-3">
            <div v-for="row in employmentRows" :key="row.label" class="flex justify-between gap-3">
              <dt class="text-xs text-slate-500 shrink-0 w-40 pt-0.5">{{ row.label }}</dt>
              <dd class="text-sm text-slate-800 font-medium text-right break-words">{{ row.value || '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Next of Kin -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
              <i class="ti ti-users text-rose-600"></i>
            </div>
            <h3 class="font-semibold text-slate-800">Next of Kin</h3>
          </div>
          <dl class="space-y-3">
            <div v-for="row in kinRows" :key="row.label" class="flex justify-between gap-3">
              <dt class="text-xs text-slate-500 shrink-0 w-40 pt-0.5">{{ row.label }}</dt>
              <dd class="text-sm text-slate-800 font-medium text-right break-words">{{ row.value || '—' }}</dd>
            </div>
          </dl>
        </div>

      </div>

      <!-- ── Change Password ── -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header toggle -->
        <button
          type="button"
          @click="pwOpen = !pwOpen"
          class="w-full flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition-colors group"
        >
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
              <i class="ti ti-lock text-slate-600"></i>
            </div>
            <div class="text-left">
              <h3 class="font-semibold text-slate-800 text-sm">Change Password</h3>
              <p class="text-xs text-slate-400 mt-0.5">Update your login password</p>
            </div>
          </div>
          <i :class="pwOpen ? 'ti ti-chevron-up' : 'ti ti-chevron-down'"
             class="text-slate-400 group-hover:text-slate-600 transition-colors"></i>
        </button>

        <!-- Collapsible form -->
        <Transition name="slide-down">
          <div v-if="pwOpen" class="border-t border-slate-100 px-6 py-5">

            <!-- Forced-change notice (shown only if needed) -->
            <div v-if="forced"
                 class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-5">
              <i class="ti ti-lock-exclamation text-amber-600 text-lg shrink-0 mt-0.5"></i>
              <p class="text-sm text-amber-700">
                You are using a temporary password. Please set a new personal password.
              </p>
            </div>

            <form @submit.prevent="submitPassword" class="space-y-4 max-w-md">

              <!-- Current password — hidden when forced -->
              <div v-if="!forced">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">
                  Current Password
                </label>
                <div class="relative">
                  <input
                    v-model="pwForm.current_password"
                    :type="showCurrent ? 'text' : 'password'"
                    placeholder="Enter your current password"
                    autocomplete="current-password"
                    class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
                    :class="{ 'border-red-400': pwForm.errors.current_password }"
                  />
                  <button type="button" @click="showCurrent = !showCurrent"
                    class="absolute inset-y-0 right-3 flex items-center text-slate-400">
                    <i :class="showCurrent ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
                  </button>
                </div>
                <p v-if="pwForm.errors.current_password" class="mt-1 text-xs text-red-500">
                  {{ pwForm.errors.current_password }}
                </p>
              </div>

              <!-- New password -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">
                  New Password
                </label>
                <div class="relative">
                  <input
                    v-model="pwForm.password"
                    :type="showNew ? 'text' : 'password'"
                    placeholder="Minimum 8 characters"
                    autocomplete="new-password"
                    class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
                    :class="{ 'border-red-400': pwForm.errors.password }"
                  />
                  <button type="button" @click="showNew = !showNew"
                    class="absolute inset-y-0 right-3 flex items-center text-slate-400">
                    <i :class="showNew ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
                  </button>
                </div>
                <p v-if="pwForm.errors.password" class="mt-1 text-xs text-red-500">{{ pwForm.errors.password }}</p>
              </div>

              <!-- Confirm password -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">
                  Confirm New Password
                </label>
                <div class="relative">
                  <input
                    v-model="pwForm.password_confirmation"
                    :type="showNew ? 'text' : 'password'"
                    placeholder="Re-enter your new password"
                    autocomplete="new-password"
                    class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
                    :class="{ 'border-red-400': pwForm.errors.password_confirmation }"
                  />
                </div>
                <p v-if="pwForm.errors.password_confirmation" class="mt-1 text-xs text-red-500">
                  {{ pwForm.errors.password_confirmation }}
                </p>
              </div>

              <!-- Strength bar -->
              <div v-if="pwForm.password" class="space-y-1">
                <div class="flex gap-1">
                  <div v-for="i in 4" :key="i"
                    class="h-1 flex-1 rounded-full transition-colors"
                    :class="pwStrength >= i ? pwStrengthColor : 'bg-slate-200'"
                  ></div>
                </div>
                <p class="text-[11px] text-slate-400">{{ pwStrengthLabel }}</p>
              </div>

              <button
                type="submit"
                :disabled="pwForm.processing"
                class="w-full bg-[#0B1929] hover:bg-[#1a3a5c] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 disabled:opacity-60"
              >
                <svg v-if="pwForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ pwForm.processing ? 'Saving…' : 'Update Password' }}
              </button>
            </form>
          </div>
        </Transition>
      </div>

      <!-- ── Accounts Summary ── -->
      <div v-if="customer.accounts?.length" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-2 mb-5">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
            <i class="ti ti-credit-card text-indigo-600"></i>
          </div>
          <h3 class="font-semibold text-slate-800">My Accounts</h3>
        </div>
        <div class="overflow-x-auto -mx-1">
          <table class="w-full text-sm min-w-[520px]">
            <thead>
              <tr class="border-b border-slate-100">
                <th class="text-left pb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider px-1">Account Number</th>
                <th class="text-left pb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider px-1">Type</th>
                <th class="text-left pb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider px-1">Branch</th>
                <th class="text-right pb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider px-1">Balance</th>
                <th class="text-left pb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider px-1">Status</th>
                <th class="text-left pb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider px-1">Opened</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="acc in customer.accounts" :key="acc.id" class="hover:bg-slate-50 transition-colors">
                <td class="py-3 px-1 font-mono text-slate-700 font-medium">{{ acc.account_number }}</td>
                <td class="py-3 px-1 text-slate-600">{{ acc.type_name }}</td>
                <td class="py-3 px-1 text-slate-600">{{ acc.branch_name }}</td>
                <td class="py-3 px-1 text-right font-semibold" :class="acc.balance >= 0 ? 'text-slate-800' : 'text-red-600'">
                  ${{ formatMoney(acc.balance) }}
                </td>
                <td class="py-3 px-1">
                  <span :class="{
                          'bg-emerald-100 text-emerald-700': acc.status === 'active' && !acc.is_frozen,
                          'bg-blue-100 text-blue-700':       acc.status === 'active' && acc.is_frozen,
                          'bg-slate-100 text-slate-500':     acc.status !== 'active',
                        }"
                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase">
                    {{ acc.is_frozen ? 'Frozen' : acc.status }}
                  </span>
                </td>
                <td class="py-3 px-1 text-slate-500">{{ acc.opened_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </CustomerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

const props = defineProps({
  customer: { type: Object, required: true },
  forced:   { type: Boolean, default: false },
})

// ── Change Password ───────────────────────────────────────────
const pwOpen   = ref(false)
const showCurrent = ref(false)
const showNew     = ref(false)

const pwForm = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

function submitPassword() {
  pwForm.post(route('customer.change-password.post'), {
    onSuccess: () => {
      pwForm.reset()
      pwOpen.value = false
    },
  })
}

const pwStrength = computed(() => {
  const pw = pwForm.password
  if (!pw) return 0
  let s = 0
  if (pw.length >= 8)           s++
  if (/[A-Z]/.test(pw))        s++
  if (/[0-9]/.test(pw))        s++
  if (/[^A-Za-z0-9]/.test(pw)) s++
  return s
})

const pwStrengthColor = computed(() => {
  if (pwStrength.value <= 1) return 'bg-red-400'
  if (pwStrength.value === 2) return 'bg-amber-400'
  if (pwStrength.value === 3) return 'bg-blue-400'
  return 'bg-emerald-500'
})

const pwStrengthLabel = computed(() => {
  return ['', 'Weak', 'Fair', 'Good', 'Strong'][pwStrength.value] ?? ''
})

const initials = computed(() => {
  const name = props.customer?.full_name ?? ''
  return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'
})

// ── Section data rows ─────────────────────────────────────────
const personalRows = computed(() => [
  { label: 'Full Name',      value: props.customer.full_name },
  { label: 'Date of Birth',  value: props.customer.dob },
  { label: 'Gender',         value: props.customer.gender },
  { label: 'Marital Status', value: props.customer.marital_status },
  { label: 'Nationality',    value: props.customer.nationality },
  { label: 'Religion',       value: props.customer.religion },
])

const identityRows = computed(() => [
  { label: 'ID Type',      value: props.customer.id_type },
  { label: 'ID Number',    value: props.customer.id_number },
  { label: 'Issue Date',   value: props.customer.id_issue_date },
  { label: 'Expiry Date',  value: props.customer.id_expiry_date },
  { label: 'KYC Status',   value: props.customer.kyc_status },
])

const contactRows = computed(() => [
  { label: 'Phone',             value: props.customer.phone },
  { label: 'Secondary Phone',   value: props.customer.secondary_phone },
  { label: 'Email',             value: props.customer.email },
  { label: 'Preferred Contact', value: props.customer.preferred_contact_method },
])

const addressRows = computed(() => [
  { label: 'Region / City',          value: props.customer.region_city },
  { label: 'District',               value: props.customer.district },
  { label: 'Street / Neighbourhood', value: props.customer.street_neighbourhood },
  { label: 'Full Address',           value: props.customer.address },
])

const employmentRows = computed(() => [
  { label: 'Employment Status',            value: props.customer.employment_status },
  { label: 'Employer',                     value: props.customer.employer_name },
  { label: 'Monthly Income Range',         value: props.customer.monthly_income_range },
  { label: 'Source of Funds',              value: props.customer.source_of_funds },
  { label: 'Expected Monthly Txns',        value: props.customer.expected_monthly_transactions },
])

const kinRows = computed(() => [
  { label: 'Name',         value: props.customer.next_of_kin_name },
  { label: 'Relationship', value: props.customer.next_of_kin_relation },
  { label: 'Phone',        value: props.customer.next_of_kin_phone },
])

// ── Helpers ───────────────────────────────────────────────────
function kycBadge(status) {
  const map = {
    approved: 'bg-emerald-100 text-emerald-700',
    pending:  'bg-amber-100 text-amber-700',
    rejected: 'bg-red-100 text-red-700',
  }
  return map[status] ?? 'bg-slate-100 text-slate-500'
}

function formatMoney(v) {
  return Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active { transition: max-height 0.25s ease, opacity 0.2s ease; overflow: hidden; }
.slide-down-enter-from,
.slide-down-leave-to     { max-height: 0; opacity: 0; }
.slide-down-enter-to,
.slide-down-leave-from   { max-height: 600px; opacity: 1; }
</style>
