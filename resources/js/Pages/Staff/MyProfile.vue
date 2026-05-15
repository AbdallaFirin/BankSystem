<template>
  <div class="min-h-screen bg-[#070F1A] text-white">
    <div class="max-w-4xl mx-auto px-5 py-7 space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('staff.dashboard')"
              class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700
                     flex items-center justify-center text-slate-400 hover:text-white transition-all">
          <i class="ti ti-arrow-left text-base"></i>
        </Link>
        <div>
          <h1 class="text-xl font-bold text-white">My Profile</h1>
          <p class="text-xs text-slate-400">Manage your personal information and security</p>
        </div>
      </div>

      <!-- Flash messages -->
      <Transition name="fade">
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
        </div>
      </Transition>

      <!-- ── Profile Hero Card ── -->
      <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
        <div class="h-20 bg-gradient-to-r from-[#C9A84C]/20 via-slate-800 to-slate-900"></div>
        <div class="px-6 pb-6">
          <div class="-mt-10 flex items-end justify-between mb-4">
            <!-- Avatar with upload -->
            <div class="relative group">
              <div class="w-20 h-20 rounded-2xl border-4 border-slate-900 overflow-hidden bg-slate-700 flex items-center justify-center">
                <img v-if="staff.avatar" :src="'/storage/' + staff.avatar" class="w-full h-full object-cover" />
                <span v-else class="text-2xl font-bold text-white">{{ initials }}</span>
              </div>
              <!-- Upload overlay -->
              <label class="absolute inset-0 rounded-2xl bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity
                            flex items-center justify-center cursor-pointer">
                <i class="ti ti-camera text-white text-lg"></i>
                <input type="file" class="hidden" accept="image/*" @change="uploadAvatar" />
              </label>
            </div>
            <!-- Status pill -->
            <div class="mb-2 flex items-center gap-2">
              <span :class="staff.status === 'active' ? 'text-emerald-400' : 'text-red-400'" class="text-xs font-medium">
                {{ staff.status === 'active' ? '● Active' : '○ Inactive' }}
              </span>
            </div>
          </div>

          <h2 class="text-xl font-bold text-white">{{ staff.full_name }}</h2>
          <div class="flex flex-wrap gap-3 mt-1.5 text-xs text-slate-400">
            <span class="px-2 py-0.5 rounded-full text-xs" :class="roleColor(staff.role_name)">{{ staff.role_name }}</span>
            <span class="font-mono bg-slate-800 px-2 py-0.5 rounded">{{ staff.ident_number }}</span>
            <span>{{ staff.branch_name }}</span>
          </div>
        </div>
      </div>

      <!-- ── Stats ── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div class="bg-slate-900 border border-slate-700/60 rounded-xl p-4 text-center">
          <p class="text-2xl font-bold text-indigo-400">{{ stats.transactions }}</p>
          <p class="text-xs text-slate-400 mt-1">Transactions Processed</p>
        </div>
        <div class="bg-slate-900 border border-slate-700/60 rounded-xl p-4 text-center">
          <p class="text-2xl font-bold text-emerald-400">{{ stats.cash_counts }}</p>
          <p class="text-xs text-slate-400 mt-1">Cash Counts</p>
        </div>
        <div class="bg-slate-900 border border-slate-700/60 rounded-xl p-4 text-center">
          <p class="text-2xl font-bold" :class="stats.till_open ? 'text-amber-400' : 'text-slate-500'">
            ${{ fmt(stats.till_balance) }}
          </p>
          <p class="text-xs text-slate-400 mt-1">
            {{ stats.till_open ? 'Till Open' : 'No Active Till' }}
          </p>
        </div>
      </div>

      <!-- ── Tabs ── -->
      <div class="flex gap-1 bg-slate-900/60 border border-slate-700/60 rounded-xl p-1 w-fit">
        <button v-for="t in tabs" :key="t.id" @click="activeTab = t.id"
                :class="['px-4 py-2 rounded-lg text-sm font-medium transition-all',
                         activeTab === t.id ? 'bg-[#C9A84C] text-[#070F1A]' : 'text-slate-400 hover:text-white']">
          <i :class="t.icon + ' mr-1.5'"></i>{{ t.label }}
        </button>
      </div>

      <!-- ════════ TAB: Personal Info ════════ -->
      <div v-show="activeTab === 'info'">
        <form @submit.prevent="submitInfo"
              class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-800">
            <h2 class="font-semibold text-white">Personal Information</h2>
            <p class="text-xs text-slate-500 mt-0.5">Update your contact details and personal info</p>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="field-label">Full Name *</label>
              <input v-model="infoForm.full_name" type="text" class="field-input" required />
              <p v-if="infoForm.errors.full_name" class="text-red-400 text-xs mt-1">{{ infoForm.errors.full_name }}</p>
            </div>
            <div>
              <label class="field-label">Email *</label>
              <input v-model="infoForm.email" type="email" class="field-input" required />
              <p v-if="infoForm.errors.email" class="text-red-400 text-xs mt-1">{{ infoForm.errors.email }}</p>
            </div>
            <div>
              <label class="field-label">Phone *</label>
              <input v-model="infoForm.phone" type="text" class="field-input" required />
              <p v-if="infoForm.errors.phone" class="text-red-400 text-xs mt-1">{{ infoForm.errors.phone }}</p>
            </div>
            <div>
              <label class="field-label">Gender</label>
              <select v-model="infoForm.gender" class="field-input">
                <option value="">— select —</option>
                <option>Male</option><option>Female</option><option>Other</option>
              </select>
            </div>
            <div>
              <label class="field-label">Date of Birth</label>
              <input v-model="infoForm.date_of_birth" type="date" class="field-input" />
            </div>
            <div class="md:col-span-2">
              <label class="field-label">Address</label>
              <input v-model="infoForm.address" type="text" class="field-input" placeholder="Your residential address" />
            </div>
            <div class="md:col-span-2">
              <label class="field-label">Emergency Contact</label>
              <input v-model="infoForm.emergency_contact" type="text" class="field-input" placeholder="Name — Phone number" />
            </div>
            <!-- Read-only info -->
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-800">
              <div class="bg-slate-800/60 rounded-xl px-4 py-3">
                <p class="text-[10px] text-slate-500 uppercase">Staff ID</p>
                <p class="text-sm font-mono text-white mt-1">{{ staff.ident_number }}</p>
              </div>
              <div class="bg-slate-800/60 rounded-xl px-4 py-3">
                <p class="text-[10px] text-slate-500 uppercase">Role</p>
                <p class="text-sm text-white mt-1">{{ staff.role_name }}</p>
              </div>
              <div class="bg-slate-800/60 rounded-xl px-4 py-3">
                <p class="text-[10px] text-slate-500 uppercase">Branch</p>
                <p class="text-sm text-white mt-1">{{ staff.branch_name }}</p>
              </div>
            </div>
          </div>
          <div class="px-6 pb-5">
            <button type="submit" :disabled="infoForm.processing" class="btn-gold">
              <i class="ti ti-device-floppy mr-2"></i>
              {{ infoForm.processing ? 'Saving…' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>

      <!-- ════════ TAB: Security ════════ -->
      <div v-show="activeTab === 'security'">

        <!-- Force-change notice -->
        <div v-if="forcePwdChange"
             class="mb-4 bg-amber-500/10 border border-amber-500/30 rounded-xl px-5 py-4 flex items-start gap-3">
          <i class="ti ti-lock-exclamation text-amber-400 text-xl mt-0.5 shrink-0"></i>
          <div>
            <p class="text-sm font-semibold text-amber-300">Password change required</p>
            <p class="text-xs text-amber-200/70 mt-0.5">
              Your current password was set by an administrator.
              Enter it below along with a new password you choose.
            </p>
          </div>
        </div>

        <form @submit.prevent="submitPassword"
              class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-800">
            <h2 class="font-semibold text-white">Change Password</h2>
            <p class="text-xs text-slate-500 mt-0.5">Use a strong password of at least 8 characters</p>
          </div>
          <div class="p-6 space-y-4 max-w-md">

            <!-- Current Password -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="field-label !mb-0">Current Password *</label>
                <span v-if="forcePwdChange"
                      class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/25">
                  <i class="ti ti-lock text-[10px] mr-0.5"></i>Admin-set · read-only
                </span>
              </div>
              <div class="relative">
                <!-- When temp password exists: show it pre-filled and disabled -->
                <input v-if="hasTempPwd"
                       v-model="passwordForm.current_password"
                       :type="showCurrentPwd ? 'text' : 'password'"
                       disabled
                       class="field-input pr-10 cursor-not-allowed bg-slate-700/60 border-amber-500/30 text-amber-200 font-mono tracking-widest select-none"
                       required />
                <!-- When force_password_change but no temp_password: disabled empty field -->
                <input v-else-if="forcePwdChange"
                       v-model="passwordForm.current_password"
                       type="password"
                       disabled
                       placeholder="Password set by administrator"
                       class="field-input pr-10 cursor-not-allowed bg-slate-700/40 border-amber-500/20 placeholder-amber-500/40"
                       required />
                <!-- Normal case: editable -->
                <input v-else
                       v-model="passwordForm.current_password"
                       :type="showCurrentPwd ? 'text' : 'password'"
                       class="field-input pr-10"
                       required />
                <button v-if="hasTempPwd" type="button" @click="showCurrentPwd = !showCurrentPwd"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-amber-400/60 hover:text-amber-300 transition-colors">
                  <i :class="showCurrentPwd ? 'ti ti-eye-off' : 'ti ti-eye'" class="text-base"></i>
                </button>
                <button v-else-if="!forcePwdChange" type="button" @click="showCurrentPwd = !showCurrentPwd"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                  <i :class="showCurrentPwd ? 'ti ti-eye-off' : 'ti ti-eye'" class="text-base"></i>
                </button>
              </div>
              <p v-if="hasTempPwd" class="text-[11px] text-amber-400/70 mt-1.5 flex items-center gap-1">
                <i class="ti ti-info-circle text-xs"></i>
                Your administrator-assigned password is shown above. Just set your new password below.
              </p>
              <p v-if="passwordForm.errors.current_password" class="text-red-400 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
            </div>

            <!-- New Password -->
            <div>
              <label class="field-label">New Password *</label>
              <div class="relative">
                <input v-model="passwordForm.new_password"
                       :type="showNewPwd ? 'text' : 'password'"
                       class="field-input pr-10" required />
                <button type="button" @click="showNewPwd = !showNewPwd"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                  <i :class="showNewPwd ? 'ti ti-eye-off' : 'ti ti-eye'" class="text-base"></i>
                </button>
              </div>
              <p v-if="passwordForm.errors.new_password" class="text-red-400 text-xs mt-1">{{ passwordForm.errors.new_password }}</p>
            </div>

            <!-- Confirm New Password -->
            <div>
              <label class="field-label">Confirm New Password *</label>
              <div class="relative">
                <input v-model="passwordForm.new_password_confirmation"
                       :type="showConfirmPwd ? 'text' : 'password'"
                       class="field-input pr-10" required />
                <button type="button" @click="showConfirmPwd = !showConfirmPwd"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                  <i :class="showConfirmPwd ? 'ti ti-eye-off' : 'ti ti-eye'" class="text-base"></i>
                </button>
              </div>
            </div>

          </div>
          <div class="px-6 pb-5">
            <button type="submit" :disabled="passwordForm.processing" class="btn-gold">
              <i class="ti ti-lock mr-2"></i>
              {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
            </button>
          </div>
        </form>
      </div>

      <!-- ════════ TAB: Activity ════════ -->
      <div v-show="activeTab === 'activity'">
        <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-800">
            <h2 class="font-semibold text-white text-sm">Recent Activity Log</h2>
          </div>
          <div class="divide-y divide-slate-800">
            <div v-for="(act, i) in recentActivity" :key="i"
                 class="px-5 py-3.5 flex items-start gap-3">
              <div :class="act.result === 'success' || !act.result ? 'bg-emerald-900/30 text-emerald-400' : 'bg-red-900/30 text-red-400'"
                   class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                <i class="ti ti-activity text-sm"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white">{{ act.action }}</p>
                <p class="text-xs text-slate-500 mt-0.5 truncate">{{ act.description ?? '—' }}</p>
              </div>
              <span class="text-[11px] text-slate-500 whitespace-nowrap">{{ fmtDate(act.created_at) }}</span>
            </div>
            <div v-if="!recentActivity.length" class="px-5 py-10 text-center text-sm text-slate-500">
              No activity recorded yet.
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
  staff:           { type: Object, required: true },
  stats:           { type: Object, default: () => ({}) },
  recent_activity: { type: Array,  default: () => [] },
})

const recentActivity = computed(() => props.recent_activity)

/* ── Initials ── */
const initials = computed(() => {
  const parts = (props.staff.full_name ?? '?').split(' ')
  return parts.length >= 2 ? parts[0][0] + parts[1][0] : parts[0][0]
})

/* ── Tabs — auto-open Security when forced or ?tab=security ── */
const urlTab = new URLSearchParams(window.location.search).get('tab')
const forcePwdChange = !!page.props.auth?.user?.force_password_change
const activeTab = ref((forcePwdChange || urlTab === 'security') ? 'security' : 'info')

const tabs = [
  { id: 'info',     label: 'Personal Info', icon: 'ti ti-user' },
  { id: 'security', label: 'Security',      icon: 'ti ti-lock' },
  { id: 'activity', label: 'Activity',      icon: 'ti ti-history' },
]

/* ── Personal info form ── */
const infoForm = useForm({
  full_name:         props.staff.full_name         ?? '',
  email:             props.staff.email             ?? '',
  phone:             props.staff.phone             ?? '',
  gender:            props.staff.gender            ?? '',
  date_of_birth:     props.staff.date_of_birth     ?? '',
  address:           props.staff.address           ?? '',
  emergency_contact: props.staff.emergency_contact ?? '',
})
function submitInfo() {
  infoForm.post(route('staff.profile.info'), { preserveScroll: true })
}

/* ── Password form ── */
// Pre-fill current_password from the temp_password when force_password_change is active
const hasTempPwd = computed(() => forcePwdChange && !!props.staff.temp_password)

const passwordForm = useForm({
  current_password:          props.staff.temp_password ?? '',
  new_password:              '',
  new_password_confirmation: '',
})
function submitPassword() {
  passwordForm.post(route('staff.profile.password'), {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
  })
}

/* ── Password visibility toggles ── */
// Show temp password in plain text by default so the user can read it
const showCurrentPwd = ref(hasTempPwd.value)
const showNewPwd     = ref(false)
const showConfirmPwd = ref(false)

/* ── Avatar upload ── */
function uploadAvatar(e) {
  const file = e.target.files?.[0]
  if (!file) return
  const form = new FormData()
  form.append('avatar', file)
  router.post(route('staff.profile.avatar'), form, {
    forceFormData: true,
    preserveScroll: true,
  })
}

/* ── Helpers ── */
const fmt = n => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtDate = d => d ? new Date(d).toLocaleString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
function roleColor(role) {
  if (!role) return 'bg-slate-700/60 text-slate-300'
  if (role.includes('Manager'))   return 'bg-[#C9A84C]/20 text-[#C9A84C]'
  if (role.includes('Teller'))    return 'bg-green-900/40 text-green-400'
  if (role.includes('Customer'))  return 'bg-blue-900/40 text-blue-400'
  if (role.includes('Compliance'))return 'bg-purple-900/40 text-purple-400'
  if (role.includes('Accounting'))return 'bg-indigo-900/40 text-indigo-400'
  return 'bg-slate-700/60 text-slate-300'
}
</script>

<style scoped>
.field-label { @apply block text-xs font-medium text-slate-400 mb-1.5; }
.field-input {
  @apply w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
         placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors;
}
.btn-gold {
  @apply inline-flex items-center bg-[#C9A84C] hover:bg-[#b8973e] disabled:opacity-50
         text-[#070F1A] text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors;
}
.fade-enter-active, .fade-leave-active { transition: opacity .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
