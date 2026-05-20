<template>
  <div class="min-h-screen bg-[#070F1A] text-white">
    <div class="max-w-7xl mx-auto px-5 py-7 space-y-6">

      <!-- ───────── Page Header ───────── -->
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <!-- Back button -->
          <Link :href="route('staff.dashboard')"
                class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 hover:border-slate-600
                       flex items-center justify-center text-slate-400 hover:text-white transition-all">
            <i class="ti ti-arrow-left text-base"></i>
          </Link>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#C9A84C]/20 flex items-center justify-center">
              <i class="ti ti-settings-2 text-[#C9A84C] text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">Branch Settings</h1>
              <p class="text-xs text-slate-400">{{ props.branch.branch_name }} · Branch #{{ props.branch.id }}</p>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500">
          <i class="ti ti-shield-lock"></i>
          <span>Changes apply to your branch only</span>
        </div>
      </div>

      <!-- ───────── Flash ───────── -->
      <Transition name="fade">
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>
          {{ $page.props.flash.success }}
        </div>
      </Transition>
      <Transition name="fade">
        <div v-if="$page.props.errors?.error"
             class="bg-red-900/40 border border-red-600/40 text-red-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-alert-circle text-lg"></i>
          {{ $page.props.errors.error }}
        </div>
      </Transition>

      <!-- ───────── Stats Row ───────── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div v-for="s in statCards" :key="s.label"
             class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
          <p class="text-xs text-slate-400 mb-1">{{ s.label }}</p>
          <p class="text-lg font-bold" :class="s.color">{{ s.value }}</p>
        </div>
      </div>

      <!-- ───────── Tabs ───────── -->
      <div class="flex gap-1 bg-slate-900/60 border border-slate-700/60 rounded-xl p-1 w-fit">
        <button v-for="t in tabs" :key="t.id"
                @click="activeTab = t.id"
                :class="['px-4 py-2 rounded-lg text-sm font-medium transition-all', activeTab === t.id
                  ? 'bg-[#C9A84C] text-[#070F1A]'
                  : 'text-slate-400 hover:text-white']">
          <i :class="t.icon + ' mr-1.5'"></i>{{ t.label }}
        </button>
      </div>

      <!-- ════════ TAB: Branch Profile ════════ -->
      <div v-show="activeTab === 'profile'">
        <form @submit.prevent="submitProfile"
              class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="font-semibold text-white">Branch Profile & Contact</h2>
            <span class="text-xs text-slate-500">Visible on printed documents and statements</span>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

            <div class="md:col-span-2">
              <label class="field-label">Branch Name *</label>
              <input v-model="profileForm.branch_name" type="text" class="field-input" required />
            </div>

            <div>
              <label class="field-label">City *</label>
              <input v-model="profileForm.city" type="text" class="field-input" required />
            </div>

            <div>
              <label class="field-label">Phone *</label>
              <input v-model="profileForm.phone" type="text" class="field-input" required />
            </div>

            <div class="md:col-span-2">
              <label class="field-label">Address *</label>
              <input v-model="profileForm.address" type="text" class="field-input" required />
            </div>

            <div>
              <label class="field-label">Email</label>
              <input v-model="profileForm.email" type="email" class="field-input" placeholder="branch@gobaadbank.com" />
            </div>

            <div>
              <label class="field-label">SWIFT / BIC Code</label>
              <input v-model="profileForm.swift_code" type="text" class="field-input" placeholder="e.g. GBADSO1X" />
            </div>

            <div>
              <label class="field-label">Routing / Sort Code</label>
              <input v-model="profileForm.routing_number" type="text" class="field-input" placeholder="e.g. 040-001" />
            </div>

            <div>
              <label class="field-label">Currency</label>
              <select v-model="profileForm.currency_code" class="field-input">
                <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.code }} — {{ c.name }}</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="field-label">Branch Description</label>
              <textarea v-model="profileForm.description" rows="3" class="field-input resize-none"
                        placeholder="Brief description of this branch's services and location…"></textarea>
            </div>
          </div>
          <div class="px-6 pb-5">
            <button type="submit" :disabled="profileForm.processing" class="btn-gold">
              <i class="ti ti-device-floppy mr-2"></i>
              {{ profileForm.processing ? 'Saving…' : 'Save Profile' }}
            </button>
          </div>
        </form>
      </div>

      <!-- ════════ TAB: Operating Hours ════════ -->
      <div v-show="activeTab === 'hours'">
        <form @submit.prevent="submitProfile"
              class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-800">
            <h2 class="font-semibold text-white">Operating Hours & Schedule</h2>
            <p class="text-xs text-slate-500 mt-0.5">Controls when the branch is shown as "open" on documents</p>
          </div>
          <div class="p-6 space-y-6">

            <!-- Working Days checkboxes -->
            <div>
              <label class="field-label mb-3 block">Working Days *</label>
              <div class="flex flex-wrap gap-2">
                <label v-for="day in allDays" :key="day"
                       :class="['flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer transition-all text-sm font-medium select-none',
                                profileForm.working_days.includes(day)
                                  ? 'bg-[#C9A84C]/20 border-[#C9A84C]/60 text-[#C9A84C]'
                                  : 'bg-slate-800 border-slate-600 text-slate-400 hover:border-slate-500']">
                  <input type="checkbox" class="hidden"
                         :checked="profileForm.working_days.includes(day)"
                         @change="toggleDay(day)" />
                  {{ day }}
                </label>
              </div>
            </div>

            <!-- Time range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="field-label">Opening Time *</label>
                <input v-model="profileForm.working_hours_start" type="time" class="field-input" required />
              </div>
              <div>
                <label class="field-label">Closing Time *</label>
                <input v-model="profileForm.working_hours_end" type="time" class="field-input" required />
              </div>
            </div>

            <!-- Timezone -->
            <div>
              <label class="field-label">Timezone</label>
              <select v-model="profileForm.timezone" class="field-input">
                <option v-for="tz in timezones" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
              </select>
            </div>

            <!-- Hours preview -->
            <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4">
              <p class="text-xs text-slate-400 mb-2 font-medium uppercase tracking-wide">Preview</p>
              <p class="text-sm text-white">
                <span class="text-[#C9A84C] font-semibold">{{ profileForm.working_days.join(', ') || '—' }}</span>
                &nbsp;·&nbsp;
                {{ profileForm.working_hours_start }} – {{ profileForm.working_hours_end }}
                <span class="text-slate-400 text-xs ml-2">({{ profileForm.timezone }})</span>
              </p>
            </div>
          </div>
          <div class="px-6 pb-5">
            <button type="submit" :disabled="profileForm.processing" class="btn-gold">
              <i class="ti ti-device-floppy mr-2"></i>
              {{ profileForm.processing ? 'Saving…' : 'Save Hours' }}
            </button>
          </div>
        </form>
      </div>

      <!-- ════════ TAB: Vault ════════ -->
      <div v-show="activeTab === 'vault'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

          <!-- Vault status card -->
          <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800">
              <h2 class="font-semibold text-white">Vault Status</h2>
            </div>
            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between bg-slate-800/60 border border-slate-700 rounded-xl p-4">
                <div>
                  <p class="text-xs text-slate-400 uppercase tracking-wide">Current Vault Balance</p>
                  <p class="text-2xl font-bold text-emerald-400 mt-1 font-mono">${{ fmt(props.stats.vault_balance) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-900/30 flex items-center justify-center">
                  <i class="ti ti-building-bank text-2xl text-emerald-400"></i>
                </div>
              </div>

              <div v-if="props.stats.vault_balance < (props.branch.vault_min_threshold || 0) && props.branch.vault_min_threshold > 0"
                   class="flex items-start gap-2 bg-amber-900/30 border border-amber-600/40 rounded-xl p-3 text-amber-300 text-sm">
                <i class="ti ti-alert-triangle mt-0.5 shrink-0"></i>
                <span>Vault balance is below the minimum threshold of <strong>${{ fmt(props.branch.vault_min_threshold) }}</strong>.</span>
              </div>

              <div class="text-sm text-slate-400 space-y-1">
                <div class="flex justify-between">
                  <span>Active Tills</span>
                  <span class="text-white font-medium">{{ props.stats.active_tills }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Minimum Threshold</span>
                  <span class="text-white font-medium">${{ fmt(props.branch.vault_min_threshold || 0) }}</span>
                </div>
              </div>

              <Link :href="route('staff.teller.cash-allocation.index')"
                    class="flex items-center gap-2 text-sm text-indigo-400 hover:text-indigo-300 transition-colors">
                <i class="ti ti-wallet"></i> Manage Cash Allocations →
              </Link>
            </div>
          </div>

          <!-- Vault Deposit form -->
          <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800">
              <h2 class="font-semibold text-white">Direct Vault Deposit</h2>
              <p class="text-xs text-slate-500 mt-0.5">Add cash received from head office or courier to the vault</p>
            </div>
            <form @submit.prevent="submitVaultDeposit" class="p-6 space-y-4">
              <div>
                <label class="field-label">Amount ($) *</label>
                <input v-model.number="vaultForm.amount" type="number" step="0.01" min="0.01"
                       class="field-input" placeholder="0.00" required />
              </div>
              <div>
                <label class="field-label">Description / Reference</label>
                <input v-model="vaultForm.description" type="text" class="field-input"
                       placeholder="e.g. HQ Cash Transfer — Ref #12345" />
              </div>
              <button type="submit" :disabled="vaultForm.processing || !vaultForm.amount"
                      class="w-full bg-emerald-700 hover:bg-emerald-600 disabled:opacity-50 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
                <i class="ti ti-plus mr-2"></i>
                {{ vaultForm.processing ? 'Processing…' : 'Deposit to Vault' }}
              </button>
            </form>

            <!-- Vault threshold config -->
            <div class="px-6 pb-6">
              <div class="border-t border-slate-800 pt-5">
                <label class="field-label">Minimum Vault Balance Threshold ($)</label>
                <p class="text-xs text-slate-500 mb-2">Alerts appear when vault falls below this amount</p>
                <div class="flex gap-2">
                  <input v-model.number="thresholdVal" type="number" step="100" min="0"
                         class="field-input flex-1" placeholder="e.g. 10000" />
                  <button @click="saveThreshold" :disabled="savingThreshold"
                          class="px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:opacity-50 text-sm text-white rounded-xl transition-colors whitespace-nowrap">
                    {{ savingThreshold ? '…' : 'Set' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ════════ TAB: Staff ════════ -->
      <div v-show="activeTab === 'staff'">
        <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="font-semibold text-white">Branch Staff</h2>
            <span class="text-xs text-slate-400">{{ props.staff_list.length }} members</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-800/60">
                <tr class="text-xs text-slate-400 uppercase tracking-wide">
                  <th class="px-5 py-3 text-left">Name</th>
                  <th class="px-4 py-3 text-left">Role</th>
                  <th class="px-4 py-3 text-center">Till Status</th>
                  <th class="px-4 py-3 text-center">Active</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="s in props.staff_list" :key="s.id"
                    class="hover:bg-slate-800/40 transition-colors">
                  <td class="px-5 py-3 font-medium text-white">{{ s.full_name }}</td>
                  <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full" :class="roleColor(s.role)">{{ s.role || '—' }}</span>
                  </td>
                  <td class="px-4 py-3 text-center text-slate-400 text-xs">—</td>
                  <td class="px-4 py-3 text-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════ TAB: Transaction Limits ════════ -->
      <div v-show="activeTab === 'limits'">
        <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-800">
            <h2 class="font-semibold text-white">Transaction Limits by Role</h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Transactions above a role's limit require supervisor approval. Leave blank for no limit.
            </p>
          </div>

          <div class="divide-y divide-slate-800">
            <div v-for="role in props.roles" :key="role.id"
                 class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-slate-800/30 transition-colors">

              <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                     :class="roleColor(role.role_name).replace('text-', 'bg-').replace('/40', '/20').replace('bg-slate-700', 'bg-slate-700')">
                  <i class="ti ti-user-shield text-sm" :class="roleColor(role.role_name).split(' ').find(c => c.startsWith('text-'))"></i>
                </div>
                <div>
                  <p class="text-sm font-medium text-white">{{ role.role_name }}</p>
                  <p class="text-xs text-slate-500 mt-0.5">
                    {{ role.txn_limit != null
                        ? 'Limit: $' + fmt(role.txn_limit) + ' per transaction'
                        : 'No limit set — all transactions processed immediately' }}
                  </p>
                </div>
              </div>

              <form @submit.prevent="saveLimit(role)"
                    class="flex items-center gap-2 shrink-0">
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                  <input
                    v-model="limitInputs[role.id]"
                    type="number" step="0.01" min="0"
                    placeholder="No limit"
                    class="w-36 bg-slate-800 border border-slate-600 rounded-xl pl-7 pr-3 py-2 text-sm text-white
                           placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors"
                  />
                </div>
                <button type="submit"
                        :disabled="savingLimit[role.id]"
                        class="px-4 py-2 bg-[#C9A84C] hover:bg-[#b8973e] disabled:opacity-50 text-[#070F1A] text-sm font-semibold rounded-xl transition-colors whitespace-nowrap">
                  {{ savingLimit[role.id] ? '…' : 'Set' }}
                </button>
                <button v-if="limitInputs[role.id] !== '' && limitInputs[role.id] != null"
                        type="button"
                        @click="clearLimit(role)"
                        :disabled="savingLimit[role.id]"
                        class="px-3 py-2 bg-slate-700 hover:bg-slate-600 disabled:opacity-50 text-slate-300 text-sm rounded-xl transition-colors"
                        title="Remove limit">
                  <i class="ti ti-x"></i>
                </button>
              </form>
            </div>
          </div>

          <div class="px-6 py-4 bg-slate-800/30 border-t border-slate-800">
            <div class="flex items-start gap-2 text-xs text-slate-500">
              <i class="ti ti-info-circle mt-0.5 shrink-0"></i>
              <span>
                These limits apply globally to all branches. A teller with a $5,000 limit must route anything above that to a
                <strong class="text-slate-400">Teller Supervisor</strong> or <strong class="text-slate-400">Branch Manager</strong> for approval.
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Link, useForm, usePage, router } from '@inertiajs/vue3'

const props = defineProps({
  branch:    { type: Object, required: true },
  stats:     { type: Object, default: () => ({}) },
  staff_list:{ type: Array,  default: () => [] },
  roles:     { type: Array,  default: () => [] },
})

/* ── Tabs ── */
const activeTab = ref('profile')
const tabs = [
  { id: 'profile', label: 'Branch Profile',      icon: 'ti ti-building' },
  { id: 'hours',   label: 'Operating Hours',     icon: 'ti ti-clock' },
  { id: 'vault',   label: 'Vault & Cash',        icon: 'ti ti-building-bank' },
  { id: 'staff',   label: 'Staff',               icon: 'ti ti-users' },
  { id: 'limits',  label: 'Transaction Limits',  icon: 'ti ti-shield-dollar' },
]

/* ── Stats ── */
const statCards = computed(() => [
  { label: 'Staff',            value: props.stats.staff_count    ?? 0, color: 'text-white' },
  { label: 'Customers',        value: props.stats.customer_count ?? 0, color: 'text-blue-400' },
  { label: 'Accounts',         value: props.stats.account_count  ?? 0, color: 'text-purple-400' },
  { label: 'Pending Approvals',value: props.stats.pending_approvals ?? 0, color: props.stats.pending_approvals > 0 ? 'text-amber-400' : 'text-white' },
  { label: 'Active Tills',     value: props.stats.active_tills   ?? 0, color: 'text-green-400' },
  { label: 'Vault Balance',    value: '$' + fmt(props.stats.vault_balance ?? 0), color: 'text-emerald-400' },
])

/* ── Profile form (shared for profile + hours tabs) ── */
const profileForm = useForm({
  branch_name:         props.branch.branch_name         ?? '',
  city:                props.branch.city                ?? '',
  address:             props.branch.address             ?? '',
  phone:               props.branch.phone               ?? '',
  email:               props.branch.email               ?? '',
  swift_code:          props.branch.swift_code          ?? '',
  routing_number:      props.branch.routing_number      ?? '',
  description:         props.branch.description         ?? '',
  working_hours_start: props.branch.working_hours_start ?? '08:00',
  working_hours_end:   props.branch.working_hours_end   ?? '17:00',
  working_days:        props.branch.working_days        ?? ['Mon','Tue','Wed','Thu','Fri'],
  vault_min_threshold: props.branch.vault_min_threshold ?? 0,
  currency_code:       props.branch.currency_code       ?? 'USD',
  timezone:            props.branch.timezone            ?? 'Africa/Nairobi',
})

const allDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

function toggleDay(day) {
  const idx = profileForm.working_days.indexOf(day)
  if (idx >= 0) profileForm.working_days.splice(idx, 1)
  else profileForm.working_days.push(day)
}

function submitProfile() {
  profileForm.post(route('staff.branch.settings.update'), { preserveScroll: true })
}

/* ── Vault deposit ── */
const vaultForm = useForm({ amount: '', description: '' })
function submitVaultDeposit() {
  vaultForm.post(route('staff.branch.vault.deposit'), {
    preserveScroll: true,
    onSuccess: () => vaultForm.reset(),
  })
}

/* ── Threshold quick-save ── */
const thresholdVal  = ref(props.branch.vault_min_threshold ?? 0)
const savingThreshold = ref(false)
function saveThreshold() {
  savingThreshold.value = true
  profileForm.vault_min_threshold = thresholdVal.value
  profileForm.post(route('staff.branch.settings.update'), {
    preserveScroll: true,
    onFinish: () => { savingThreshold.value = false },
  })
}

/* ── Transaction Limits ── */
const limitInputs = reactive(
  Object.fromEntries(props.roles.map(r => [r.id, r.txn_limit ?? '']))
)
const savingLimit = reactive(
  Object.fromEntries(props.roles.map(r => [r.id, false]))
)

function saveLimit(role) {
  savingLimit[role.id] = true
  router.put(
    route('staff.branch.settings.role-limit', role.id),
    { txn_limit: limitInputs[role.id] === '' ? null : limitInputs[role.id] },
    {
      preserveScroll: true,
      onFinish: () => { savingLimit[role.id] = false },
    }
  )
}

function clearLimit(role) {
  limitInputs[role.id] = ''
  saveLimit(role)
}

/* ── Static data ── */
const currencies = [
  { code: 'USD', name: 'US Dollar' },
  { code: 'EUR', name: 'Euro' },
  { code: 'GBP', name: 'British Pound' },
  { code: 'SOS', name: 'Somali Shilling' },
  { code: 'KES', name: 'Kenyan Shilling' },
  { code: 'ETB', name: 'Ethiopian Birr' },
  { code: 'DJF', name: 'Djiboutian Franc' },
]

const timezones = [
  { value: 'Africa/Nairobi',         label: 'Africa/Nairobi (EAT, UTC+3)' },
  { value: 'Africa/Mogadishu',       label: 'Africa/Mogadishu (EAT, UTC+3)' },
  { value: 'Africa/Djibouti',        label: 'Africa/Djibouti (EAT, UTC+3)' },
  { value: 'Africa/Addis_Ababa',     label: 'Africa/Addis_Ababa (EAT, UTC+3)' },
  { value: 'Africa/Johannesburg',    label: 'Africa/Johannesburg (SAST, UTC+2)' },
  { value: 'Europe/London',          label: 'Europe/London (GMT, UTC+0)' },
  { value: 'America/New_York',       label: 'America/New_York (EST, UTC-5)' },
  { value: 'Asia/Dubai',             label: 'Asia/Dubai (GST, UTC+4)' },
]

/* ── Helpers ── */
const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

function roleColor(role) {
  if (!role) return 'bg-slate-700 text-slate-300'
  if (role.includes('Manager'))  return 'bg-[#C9A84C]/20 text-[#C9A84C]'
  if (role.includes('Teller'))   return 'bg-green-900/40 text-green-400'
  if (role.includes('Customer')) return 'bg-blue-900/40 text-blue-400'
  if (role.includes('Compliance'))return 'bg-purple-900/40 text-purple-400'
  if (role.includes('Accounting'))return 'bg-indigo-900/40 text-indigo-400'
  if (role.includes('Admin'))    return 'bg-red-900/40 text-red-400'
  return 'bg-slate-700 text-slate-300'
}
</script>

<style scoped>
.field-label {
  @apply block text-xs font-medium text-slate-400 mb-1.5;
}
.field-input {
  @apply w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
         placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60
         transition-colors;
}
.btn-gold {
  @apply inline-flex items-center bg-[#C9A84C] hover:bg-[#b8973e] disabled:opacity-50
         text-[#070F1A] text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
