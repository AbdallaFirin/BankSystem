<template>
  <div class="min-h-screen bg-[#070F1A] text-white">
    <div class="max-w-6xl mx-auto px-5 py-7 space-y-6">

      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Link :href="route('staff.branch.settings')"
                class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700
                       flex items-center justify-center text-slate-400 hover:text-white transition-all">
            <i class="ti ti-arrow-left text-base"></i>
          </Link>
          <div class="w-10 h-10 rounded-xl bg-indigo-900/40 flex items-center justify-center">
            <i class="ti ti-users text-indigo-400 text-xl"></i>
          </div>
          <div>
            <h1 class="text-xl font-bold text-white">Staff Management</h1>
            <p class="text-xs text-slate-400">Create, edit, and manage staff in your branch</p>
          </div>
        </div>
        <button @click="openCreate"
                class="flex items-center gap-2 bg-[#C9A84C] hover:bg-[#b8973e] text-[#070F1A] text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
          <i class="ti ti-plus"></i> Add Staff Member
        </button>
      </div>

      <!-- Flash -->
      <Transition name="fade">
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
        </div>
      </Transition>
      <Transition name="fade">
        <div v-if="$page.props.errors?.error"
             class="bg-red-900/40 border border-red-600/40 text-red-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-alert-circle text-lg"></i>{{ $page.props.errors.error }}
        </div>
      </Transition>

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <input v-model="filters.search" @input="applyFilters"
               type="text" placeholder="Search name, ID, email, phone…"
               class="flex-1 min-w-[200px] bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500
                      focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors" />
        <select v-model="filters.role_id" @change="applyFilters"
                class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white
                       focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors">
          <option value="">All Roles</option>
          <option v-for="r in props.roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
        </select>
        <select v-model="filters.status" @change="applyFilters"
                class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white
                       focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        <button v-if="filters.search || filters.role_id || filters.status" @click="clearFilters"
                class="px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-400 hover:text-white text-sm transition-colors">
          Clear
        </button>
      </div>

      <!-- Staff Table -->
      <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
          <h2 class="font-semibold text-white">Branch Staff</h2>
          <span class="text-xs text-slate-400">{{ props.staff_list.length }} members</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-800/60">
              <tr class="text-xs text-slate-400 uppercase tracking-wide">
                <th class="px-5 py-3 text-left">Staff</th>
                <th class="px-4 py-3 text-left">Login ID</th>
                <th class="px-4 py-3 text-left">Role</th>
                <th class="px-4 py-3 text-left">Contact</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-for="s in props.staff_list" :key="s.id"
                  class="hover:bg-slate-800/40 transition-colors cursor-pointer"
                  @click.stop="goToProfile(s.id)">
                <td class="px-5 py-3">
                  <div class="font-medium text-white hover:text-[#C9A84C] transition-colors">{{ s.full_name }}</div>
                  <div class="text-xs text-slate-400">{{ s.email }}</div>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ s.ident_number }}</td>
                <td class="px-4 py-3">
                  <span class="text-xs px-2.5 py-1 rounded-full" :class="roleColor(s.role_name)">
                    {{ s.role_name }}
                  </span>
                </td>
                <td class="px-4 py-3 text-slate-400 text-xs">{{ s.phone }}</td>
                <td class="px-4 py-3 text-center" @click.stop>
                  <button @click="toggleStatus(s)"
                          :class="s.status === 'active'
                            ? 'bg-emerald-900/40 text-emerald-400 hover:bg-red-900/30 hover:text-red-400'
                            : 'bg-red-900/30 text-red-400 hover:bg-emerald-900/30 hover:text-emerald-400'"
                          class="text-xs px-2.5 py-1 rounded-full transition-colors">
                    {{ s.status === 'active' ? '● Active' : '○ Inactive' }}
                  </button>
                </td>
                <td class="px-4 py-3 text-right" @click.stop>
                  <div class="flex items-center justify-end gap-2">
                    <button @click="openEdit(s)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                   bg-indigo-900/30 border border-indigo-700/40 text-indigo-300
                                   hover:bg-indigo-800/50 hover:text-white text-xs font-medium transition-colors">
                      <i class="ti ti-pencil text-sm"></i> Edit
                    </button>
                    <button @click="confirmDelete(s)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                   bg-red-900/30 border border-red-700/40 text-red-400
                                   hover:bg-red-800/50 hover:text-white text-xs font-medium transition-colors">
                      <i class="ti ti-user-off text-sm"></i> Deactivate
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!props.staff_list.length">
                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                  No staff found in this branch.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════ Create / Edit Modal ══════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
          <div class="bg-[#112236] border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-5 border-b border-slate-700/60 flex items-center justify-between">
              <h3 class="font-semibold text-white text-base">
                {{ editingStaff ? 'Edit Staff Member' : 'Add New Staff Member' }}
              </h3>
              <button @click="closeModal" class="text-slate-400 hover:text-white transition-colors">
                <i class="ti ti-x text-lg"></i>
              </button>
            </div>

            <form @submit.prevent="submitForm" class="px-6 py-5 space-y-4">
              <div>
                <label class="field-label">Full Name *</label>
                <input v-model="form.full_name" type="text" class="field-input" required placeholder="e.g. Ahmed Ali Hassan" />
                <p v-if="form.errors.full_name" class="text-red-400 text-xs mt-1">{{ form.errors.full_name }}</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="field-label">Email *</label>
                  <input v-model="form.email" type="email" class="field-input" required placeholder="staff@bank.com" />
                  <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                </div>
                <div>
                  <label class="field-label">Phone *</label>
                  <input v-model="form.phone" type="text" class="field-input" required placeholder="+252..." />
                  <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
                </div>
              </div>
              <div>
                <label class="field-label">Role *</label>
                <select v-model="form.role_id" class="field-input" required>
                  <option value="">— select role —</option>
                  <option v-for="r in props.roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
                </select>
                <p v-if="form.errors.role_id" class="text-red-400 text-xs mt-1">{{ form.errors.role_id }}</p>
              </div>
              <div>
                <label class="field-label">
                  Password {{ editingStaff ? '(leave blank to keep current)' : '*' }}
                </label>
                <input v-model="form.password" type="password" class="field-input"
                       :required="!editingStaff" placeholder="Min. 8 characters" />
                <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
              </div>

              <div class="flex gap-3 pt-2">
                <button type="button" @click="closeModal"
                        class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition-colors">
                  Cancel
                </button>
                <button type="submit" :disabled="form.processing"
                        class="flex-1 bg-[#C9A84C] hover:bg-[#b8973e] disabled:opacity-50 text-[#070F1A] text-sm font-semibold py-2.5 rounded-xl transition-colors">
                  {{ form.processing ? 'Saving…' : (editingStaff ? 'Save Changes' : 'Create Staff') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════ Deactivate Confirm Modal ══════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="deleteTarget"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
          <div class="bg-[#112236] border border-amber-900/60 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center space-y-4">
            <div class="w-14 h-14 rounded-full bg-amber-900/40 flex items-center justify-center mx-auto">
              <i class="ti ti-user-off text-2xl text-amber-400"></i>
            </div>
            <div>
              <h3 class="font-semibold text-white text-base">Deactivate Staff Account?</h3>
              <p class="text-sm text-slate-400 mt-1">
                <strong class="text-white">{{ deleteTarget.full_name }}</strong>'s account will be deactivated.
                They will no longer be able to log in, but all their transaction history and audit records will be preserved.
              </p>
            </div>
            <div class="flex gap-3">
              <button @click="deleteTarget = null"
                      class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition-colors">
                Cancel
              </button>
              <button @click="doDelete"
                      class="flex-1 bg-amber-700 hover:bg-amber-600 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
                Yes, Deactivate
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  staff_list: { type: Array, default: () => [] },
  roles:      { type: Array, default: () => [] },
  filters:    { type: Object, default: () => ({}) },
})

const filters = reactive({
  search:  props.filters.search  ?? '',
  role_id: props.filters.role_id ?? '',
  status:  props.filters.status  ?? '',
})

let filterTimer = null
function applyFilters() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    router.get(route('staff.branch.staff.index'), {
      search:  filters.search  || undefined,
      role_id: filters.role_id || undefined,
      status:  filters.status  || undefined,
    }, { preserveState: true, replace: true })
  }, 300)
}

function clearFilters() {
  filters.search  = ''
  filters.role_id = ''
  filters.status  = ''
  router.get(route('staff.branch.staff.index'), {}, { preserveState: true, replace: true })
}

/* ── Modal state ── */
const showModal    = ref(false)
const editingStaff = ref(null)
const deleteTarget = ref(null)

const form = useForm({
  full_name: '', email: '', phone: '', role_id: '', password: '',
})

function openCreate() {
  editingStaff.value = null
  form.reset()
  showModal.value = true
}

function openEdit(s) {
  editingStaff.value = s
  form.full_name = s.full_name
  form.email     = s.email
  form.phone     = s.phone
  form.role_id   = s.role_id
  form.password  = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  form.reset()
  form.clearErrors()
}

function submitForm() {
  if (editingStaff.value) {
    form.put(route('staff.branch.staff.update', editingStaff.value.id), {
      onSuccess: () => closeModal(),
    })
  } else {
    form.post(route('staff.branch.staff.store'), {
      onSuccess: () => closeModal(),
    })
  }
}

/* ── Navigate to profile ── */
function goToProfile(id) {
  router.visit(route('staff.branch.staff.show', id))
}

/* ── Status toggle ── */
function toggleStatus(s) {
  const newStatus = s.status === 'active' ? 'inactive' : 'active'
  router.post(route('staff.branch.staff.status', s.id), { status: newStatus }, { preserveScroll: true })
}

/* ── Delete ── */
function confirmDelete(s) { deleteTarget.value = s }
function doDelete() {
  router.delete(route('staff.branch.staff.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onSuccess: () => { deleteTarget.value = null },
  })
}

/* ── Helpers ── */
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
