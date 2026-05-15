<template>
  <div class="min-h-screen bg-[#070F1A] text-white">
    <div class="max-w-5xl mx-auto px-5 py-7 space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Link :href="route('staff.branch.staff.index')"
                class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700
                       flex items-center justify-center text-slate-400 hover:text-white transition-all">
            <i class="ti ti-arrow-left text-base"></i>
          </Link>
          <div>
            <h1 class="text-xl font-bold text-white">Staff Profile</h1>
            <p class="text-xs text-slate-400">Branch Manager View</p>
          </div>
        </div>
        <!-- Quick actions -->
        <div class="flex items-center gap-2">
          <button @click="openEdit"
                  class="flex items-center gap-2 bg-[#C9A84C] hover:bg-[#b8973e] text-[#070F1A] text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
            <i class="ti ti-pencil text-sm"></i> Edit
          </button>
          <button @click="toggleStatus"
                  :class="staff.status === 'active'
                    ? 'bg-red-900/30 text-red-400 hover:bg-red-900/50 border-red-800/40'
                    : 'bg-emerald-900/30 text-emerald-400 hover:bg-emerald-900/50 border-emerald-800/40'"
                  class="flex items-center gap-2 border text-sm font-medium px-4 py-2 rounded-xl transition-colors">
            <i :class="staff.status === 'active' ? 'ti ti-user-off' : 'ti ti-user-check'" class="text-sm"></i>
            {{ staff.status === 'active' ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>

      <!-- Flash -->
      <Transition name="fade">
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check"></i>{{ $page.props.flash.success }}
        </div>
      </Transition>

      <!-- ── Profile Hero ── -->
      <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
        <!-- Banner -->
        <div class="h-24 bg-gradient-to-r from-indigo-900/60 via-slate-800 to-slate-900"></div>
        <div class="px-6 pb-6">
          <!-- Avatar -->
          <div class="-mt-12 mb-4 flex items-end justify-between">
            <div class="relative">
              <div class="w-20 h-20 rounded-2xl border-4 border-slate-900 overflow-hidden bg-slate-700 flex items-center justify-center">
                <img v-if="staff.avatar" :src="'/storage/' + staff.avatar" class="w-full h-full object-cover" />
                <span v-else class="text-2xl font-bold text-white">{{ initials }}</span>
              </div>
              <span :class="staff.status === 'active' ? 'bg-emerald-400' : 'bg-red-400'"
                    class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-slate-900"></span>
            </div>
            <div class="mb-2">
              <span class="text-xs px-2.5 py-1 rounded-full" :class="roleColor(staff.role_name)">
                {{ staff.role_name }}
              </span>
            </div>
          </div>

          <!-- Name + ID -->
          <h2 class="text-xl font-bold text-white">{{ staff.full_name }}</h2>
          <div class="flex flex-wrap gap-3 mt-1.5 text-xs text-slate-400">
            <span class="font-mono bg-slate-800 px-2 py-0.5 rounded">{{ staff.ident_number }}</span>
            <span>{{ staff.branch_name }}</span>
            <span v-if="staff.hired_at">Joined {{ staff.hired_at }}</span>
            <span>Account created {{ staff.created_at }}</span>
          </div>
        </div>
      </div>

      <!-- ── Stats Row ── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-slate-900 border border-slate-700/60 rounded-xl p-4 text-center">
          <p class="text-2xl font-bold text-indigo-400">{{ stats.transactions }}</p>
          <p class="text-xs text-slate-400 mt-1">Transactions</p>
        </div>
        <div class="bg-slate-900 border border-slate-700/60 rounded-xl p-4 text-center">
          <p class="text-2xl font-bold text-emerald-400">{{ stats.cash_counts }}</p>
          <p class="text-xs text-slate-400 mt-1">Cash Counts</p>
        </div>
        <div class="bg-slate-900 border border-slate-700/60 rounded-xl p-4 text-center">
          <p class="text-2xl font-bold" :class="stats.till_open ? 'text-amber-400' : 'text-slate-500'">
            ${{ fmt(stats.till_balance) }}
          </p>
          <p class="text-xs text-slate-400 mt-1">Till Balance</p>
        </div>
        <div class="bg-slate-900 border border-slate-700/60 rounded-xl p-4 text-center">
          <p class="text-2xl font-bold" :class="staff.status === 'active' ? 'text-emerald-400' : 'text-red-400'">
            {{ staff.status === 'active' ? 'Active' : 'Inactive' }}
          </p>
          <p class="text-xs text-slate-400 mt-1">Account Status</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- ── Contact & Personal Info ── -->
        <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-800">
            <h3 class="font-semibold text-white text-sm">Contact & Personal Info</h3>
          </div>
          <div class="px-5 py-4 space-y-3">
            <InfoRow icon="ti-mail"    label="Email"              :value="staff.email" />
            <InfoRow icon="ti-phone"   label="Phone"              :value="staff.phone" />
            <InfoRow icon="ti-gender-bigender" label="Gender"     :value="staff.gender" />
            <InfoRow icon="ti-calendar" label="Date of Birth"     :value="staff.date_of_birth" />
            <InfoRow icon="ti-map-pin" label="Address"            :value="staff.address" />
            <InfoRow icon="ti-phone-call" label="Emergency Contact" :value="staff.emergency_contact" />
          </div>
        </div>

        <!-- ── Recent Activity ── -->
        <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="font-semibold text-white text-sm">Recent Activity</h3>
            <Link :href="route('staff.branch.audit', { staff_id: staff.id })"
                  class="text-xs text-indigo-400 hover:text-indigo-300">View all →</Link>
          </div>
          <div class="divide-y divide-slate-800">
            <div v-for="(act, i) in recentActivity" :key="i"
                 class="px-5 py-3 flex items-start gap-3">
              <div :class="act.result === 'success' || !act.result ? 'bg-emerald-900/30 text-emerald-400' : 'bg-red-900/30 text-red-400'"
                   class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                <i class="ti ti-activity text-xs"></i>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-medium text-white truncate">{{ act.action }}</p>
                <p class="text-xs text-slate-500 truncate">{{ act.description ?? '—' }}</p>
              </div>
              <span class="text-[10px] text-slate-500 whitespace-nowrap">{{ fmtDate(act.created_at) }}</span>
            </div>
            <div v-if="!recentActivity.length" class="px-5 py-6 text-center text-xs text-slate-500">
              No activity recorded yet.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════ Edit Modal ══════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showEdit"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
          <div class="bg-[#112236] border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-5 border-b border-slate-700/60 flex items-center justify-between sticky top-0 bg-[#112236]">
              <h3 class="font-semibold text-white">Edit {{ staff.full_name }}</h3>
              <button @click="showEdit = false" class="text-slate-400 hover:text-white"><i class="ti ti-x text-lg"></i></button>
            </div>
            <form @submit.prevent="submitEdit" class="px-6 py-5 space-y-4">
              <div>
                <label class="field-label">Full Name *</label>
                <input v-model="editForm.full_name" type="text" class="field-input" required />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="field-label">Email *</label>
                  <input v-model="editForm.email" type="email" class="field-input" required />
                </div>
                <div>
                  <label class="field-label">Phone *</label>
                  <input v-model="editForm.phone" type="text" class="field-input" required />
                </div>
              </div>
              <div>
                <label class="field-label">Role *</label>
                <select v-model="editForm.role_id" class="field-input" required>
                  <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
                </select>
              </div>
              <div>
                <label class="field-label">New Password <span class="text-slate-500">(leave blank to keep)</span></label>
                <input v-model="editForm.password" type="password" class="field-input" placeholder="Min. 8 chars" />
              </div>
              <div v-if="editForm.errors.error" class="text-red-400 text-xs">{{ editForm.errors.error }}</div>
              <div class="flex gap-3 pt-1">
                <button type="button" @click="showEdit = false"
                        class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm py-2.5 rounded-xl transition-colors">Cancel</button>
                <button type="submit" :disabled="editForm.processing"
                        class="flex-1 bg-[#C9A84C] hover:bg-[#b8973e] disabled:opacity-50 text-[#070F1A] text-sm font-semibold py-2.5 rounded-xl transition-colors">
                  {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  staff:           { type: Object, required: true },
  stats:           { type: Object, default: () => ({}) },
  recent_activity: { type: Array,  default: () => [] },
  roles:           { type: Array,  default: () => [] },
})

const staff          = computed(() => props.staff)
const recentActivity = computed(() => props.recent_activity)

/* ── Avatar initials ── */
const initials = computed(() => {
  const parts = (staff.value.full_name ?? '?').split(' ')
  return parts.length >= 2 ? parts[0][0] + parts[1][0] : parts[0][0]
})

/* ── Edit modal ── */
const showEdit = ref(false)
const editForm = useForm({
  full_name: '', email: '', phone: '', role_id: '', password: '',
})

function openEdit() {
  editForm.full_name = staff.value.full_name
  editForm.email     = staff.value.email
  editForm.phone     = staff.value.phone
  editForm.role_id   = props.roles.find(r => r.role_name === staff.value.role_name)?.id ?? ''
  editForm.password  = ''
  showEdit.value = true
}

function submitEdit() {
  editForm.put(route('staff.branch.staff.update', staff.value.id), {
    preserveScroll: true,
    onSuccess: () => { showEdit.value = false },
  })
}

/* ── Status toggle ── */
function toggleStatus() {
  const newStatus = staff.value.status === 'active' ? 'inactive' : 'active'
  router.post(route('staff.branch.staff.status', staff.value.id), { status: newStatus }, { preserveScroll: true })
}

/* ── Helpers ── */
const fmt = n => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtDate = d => d ? new Date(d).toLocaleString('en-US', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'
function roleColor(role) {
  if (!role) return 'bg-slate-700 text-slate-300'
  if (role.includes('Manager'))   return 'bg-[#C9A84C]/20 text-[#C9A84C]'
  if (role.includes('Teller'))    return 'bg-green-900/40 text-green-400'
  if (role.includes('Customer'))  return 'bg-blue-900/40 text-blue-400'
  if (role.includes('Compliance'))return 'bg-purple-900/40 text-purple-400'
  if (role.includes('Accounting'))return 'bg-indigo-900/40 text-indigo-400'
  return 'bg-slate-700 text-slate-300'
}
</script>

<!-- Inline sub-component for info rows -->
<script>
const InfoRow = {
  props: { icon: String, label: String, value: [String, Number] },
  template: `
    <div class="flex items-start gap-3">
      <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center shrink-0 mt-0.5">
        <i :class="'ti ' + icon + ' text-xs text-slate-400'"></i>
      </div>
      <div>
        <p class="text-[10px] text-slate-500 uppercase tracking-wide">{{ label }}</p>
        <p class="text-sm text-white mt-0.5">{{ value || '—' }}</p>
      </div>
    </div>
  `
}
export default { components: { InfoRow } }
</script>

<style scoped>
.field-label { @apply block text-xs font-medium text-slate-400 mb-1.5; }
.field-input {
  @apply w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
         placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors;
}
.fade-enter-active, .fade-leave-active { transition: opacity .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-enter-active, .modal-leave-active { transition: opacity .2s, transform .2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(.95); }
</style>
