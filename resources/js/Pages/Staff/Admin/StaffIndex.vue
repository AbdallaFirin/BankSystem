<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    staff:    { type: Array, default: () => [] },
    roles:    Array,
    branches: Array,
    flash:    Object
});

/* ── View mode & filters ── */
const viewMode      = ref('cards')
const searchQuery   = ref('')
const filterBranch  = ref('')
const filterRole    = ref('')
const filterStatus  = ref('')

const filtered = computed(() => {
    let list = props.staff ?? []
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        list = list.filter(m =>
            m.full_name.toLowerCase().includes(q) ||
            m.email.toLowerCase().includes(q) ||
            (m.ident_number || '').toLowerCase().includes(q) ||
            (m.phone || '').includes(q)
        )
    }
    if (filterBranch.value) list = list.filter(m => m.branch?.id == filterBranch.value)
    if (filterRole.value)   list = list.filter(m => m.role?.id == filterRole.value)
    if (filterStatus.value) list = list.filter(m => m.status === filterStatus.value)
    return list
})

/* ── Create modal ── */
const showCreateModal = ref(false)
const previewId       = ref('')
const loadingId       = ref(false)

const form = useForm({
    full_name: '', email: '', phone: '', password: '', role_id: '', branch_id: '',
})

watch(() => form.branch_id, async (branchId) => {
    if (!branchId) { previewId.value = ''; return }
    loadingId.value = true
    try {
        const { data } = await axios.get(route('staff.admin.staff.preview-id'), { params: { branch_id: branchId } })
        previewId.value = data.staff_id
    } catch { previewId.value = '—' }
    finally { loadingId.value = false }
})

const openModal = () => { form.reset(); previewId.value = ''; showCreateModal.value = true }

const submit = () => {
    form.post(route('staff.admin.staff.store'), {
        onSuccess: () => { showCreateModal.value = false; form.reset(); previewId.value = '' }
    })
}

/* ── Edit modal ── */
const showEditModal = ref(false)
const editTarget    = ref(null)

const editForm = useForm({
    full_name: '', email: '', phone: '', role_id: '', branch_id: '',
})

const openEdit = (member) => {
    editTarget.value      = member
    editForm.full_name    = member.full_name
    editForm.email        = member.email
    editForm.phone        = member.phone ?? ''
    editForm.role_id      = member.role?.id ?? ''
    editForm.branch_id    = member.branch?.id ?? ''
    showEditModal.value   = true
}

const submitEdit = () => {
    editForm.put(route('staff.admin.staff.update', editTarget.value.id), {
        onSuccess: () => { showEditModal.value = false }
    })
}

/* ── Reset Password modal ── */
const showResetModal  = ref(false)
const resetTarget     = ref(null)

const resetForm = useForm({
    password: '', password_confirmation: '',
})

const openReset = (member) => {
    resetTarget.value = member
    resetForm.reset()
    showResetModal.value = true
}

const submitReset = () => {
    resetForm.post(route('staff.admin.staff.reset-password', resetTarget.value.id), {
        onSuccess: () => { showResetModal.value = false; resetForm.reset() }
    })
}

/* ── Status toggle ── */
const toggleStatus = (member) => {
    const newStatus = member.status === 'active' ? 'inactive' : 'active'
    if (confirm(`${newStatus === 'active' ? 'Activate' : 'Deactivate'} ${member.full_name}?`)) {
        useForm({ status: newStatus }).post(route('staff.admin.staff.status', member.id))
    }
}

function roleTierColor(role) {
    const t = role?.tier ?? ''
    if (t === 'System')     return 'bg-[rgba(201,168,76,0.12)] text-[#C9A84C] border-[rgba(201,168,76,0.25)]'
    if (t === 'Branch')     return 'bg-indigo-900/30 text-indigo-300 border-indigo-700/30'
    if (t === 'Front-line') return 'bg-emerald-900/30 text-emerald-300 border-emerald-700/30'
    if (t === 'Operations') return 'bg-purple-900/30 text-purple-300 border-purple-700/30'
    return 'bg-slate-800/50 text-slate-400 border-slate-700/30'
}
</script>

<template>
  <Head title="Staff Directory — Global HQ" />
  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto p-4 md:p-8">

      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-[#ffffff14] gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[rgba(201,168,76,0.1)] flex items-center justify-center text-2xl text-[#C9A84C]">
            <i class="ti ti-users-group"></i>
          </div>
          <div>
            <h1 class="font-serif text-2xl text-[#F0EBE1]">Global <em class="text-[#C9A84C] not-italic">Staff</em> Directory</h1>
            <p class="text-[12px] text-[#A9B8C6] tracking-wide uppercase mt-1">
              Full Personnel Roster &amp; Role Matrix
              <span class="ml-2 text-[#C9A84C]">{{ filtered.length }} shown</span>
            </p>
          </div>
        </div>
        <button @click="openModal"
                class="bg-[#C9A84C] text-[#0B1929] px-6 py-2.5 rounded-xl font-bold hover:bg-[#E5C97E] transition shadow-lg flex items-center gap-2">
          <i class="ti ti-user-plus text-lg"></i> Onboard New Staff
        </button>
      </div>

      <!-- Flash -->
      <div v-if="$page.props.flash?.success"
           class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-xl mb-6 flex items-center gap-3">
        <i class="ti ti-circle-check text-xl"></i>
        <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
      </div>

      <!-- ── Filter Bar ── -->
      <div class="flex flex-wrap items-center gap-3 mb-6">
        <div class="relative flex-1 min-w-[180px]">
          <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-[#6B7E8E]"></i>
          <input v-model="searchQuery" type="text" placeholder="Search name, email, ID, phone…"
                 class="w-full bg-[#0B1929] border border-[#ffffff14] text-[#F0EBE1] text-sm rounded-xl pl-9 pr-4 py-2.5 focus:border-[#C9A84C] outline-none transition" />
        </div>

        <select v-model="filterBranch"
                class="bg-[#0B1929] border border-[#ffffff14] text-[#A9B8C6] text-sm rounded-xl px-4 py-2.5 focus:border-[#C9A84C] outline-none transition">
          <option value="">All Branches</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
        </select>

        <select v-model="filterRole"
                class="bg-[#0B1929] border border-[#ffffff14] text-[#A9B8C6] text-sm rounded-xl px-4 py-2.5 focus:border-[#C9A84C] outline-none transition">
          <option value="">All Roles</option>
          <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
        </select>

        <select v-model="filterStatus"
                class="bg-[#0B1929] border border-[#ffffff14] text-[#A9B8C6] text-sm rounded-xl px-4 py-2.5 focus:border-[#C9A84C] outline-none transition">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="invited">Invited (Pending)</option>
          <option value="inactive">Inactive</option>
        </select>

        <button v-if="searchQuery || filterBranch || filterRole || filterStatus"
                @click="searchQuery = ''; filterBranch = ''; filterRole = ''; filterStatus = ''"
                class="text-[#6B7E8E] hover:text-[#C9A84C] text-sm flex items-center gap-1 transition">
          <i class="ti ti-x"></i> Clear
        </button>

        <div class="flex-1"></div>

        <div class="flex items-center gap-1 bg-[#0B1929] border border-[#ffffff14] rounded-xl p-1">
          <button @click="viewMode = 'cards'"
                  :class="viewMode === 'cards' ? 'bg-[#C9A84C] text-[#0B1929]' : 'text-[#6B7E8E] hover:text-white'"
                  class="w-8 h-8 rounded-lg flex items-center justify-center transition font-bold">
            <i class="ti ti-layout-grid text-sm"></i>
          </button>
          <button @click="viewMode = 'list'"
                  :class="viewMode === 'list' ? 'bg-[#C9A84C] text-[#0B1929]' : 'text-[#6B7E8E] hover:text-white'"
                  class="w-8 h-8 rounded-lg flex items-center justify-center transition font-bold">
            <i class="ti ti-list text-sm"></i>
          </button>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="filtered.length === 0" class="py-20 text-center text-[#6B7E8E]">
        <i class="ti ti-users text-5xl mb-3 block"></i>
        <p class="text-lg font-medium">No staff match your filters</p>
        <button @click="searchQuery = ''; filterBranch = ''; filterRole = ''; filterStatus = ''"
                class="mt-3 text-sm text-[#C9A84C] hover:underline">Clear filters</button>
      </div>

      <!-- ═══ CARDS VIEW ═══ -->
      <div v-if="viewMode === 'cards' && filtered.length"
           class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="member in filtered" :key="member.id"
             class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6 hover:bg-[rgba(255,255,255,0.03)] transition duration-300 relative group overflow-hidden"
             :class="member.status !== 'active' ? 'opacity-60' : ''">
          <div class="absolute top-0 right-0 w-24 h-24 bg-[rgba(201,168,76,0.03)] rounded-bl-full -mr-8 -mt-8 transition-all group-hover:bg-[rgba(201,168,76,0.06)]"></div>

          <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-[rgba(255,255,255,0.05)] border border-[#ffffff08] flex items-center justify-center text-xl font-serif text-[#C9A84C] overflow-hidden shrink-0">
              <img v-if="member.avatar" :src="'/storage/' + member.avatar" class="w-full h-full object-cover" />
              <span v-else>{{ member.full_name.charAt(0) }}</span>
            </div>
            <div class="min-w-0">
              <h3 class="text-base font-medium text-[#F0EBE1] truncate">{{ member.full_name }}</h3>
              <span class="text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold">{{ member.role?.role_name }}</span>
              <div v-if="member.ident_number" class="mt-1.5">
                <span class="text-[10px] font-mono bg-[rgba(201,168,76,0.1)] text-[#E8A830] px-2 py-0.5 rounded border border-[rgba(201,168,76,0.2)]">
                  ID: {{ member.ident_number }}
                </span>
              </div>
            </div>
          </div>

          <div class="space-y-2.5">
            <div class="flex items-center gap-3 text-xs text-[#A9B8C6]">
              <i class="ti ti-building w-4"></i>
              <span class="truncate">{{ member.branch?.branch_name }}</span>
            </div>
            <div class="flex items-center gap-3 text-xs text-[#A9B8C6]">
              <i class="ti ti-mail w-4"></i>
              <span class="truncate">{{ member.email }}</span>
            </div>
            <div v-if="member.phone" class="flex items-center gap-3 text-xs text-[#A9B8C6]">
              <i class="ti ti-phone w-4"></i>
              <span>{{ member.phone }}</span>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <i class="ti ti-activity w-4 text-[#A9B8C6]"></i>
              <span class="capitalize font-medium"
                :class="member.status === 'active' ? 'text-[#4CAF7D]' : member.status === 'invited' ? 'text-[#C9A84C]' : 'text-[#E2635A]'">
                {{ member.status === 'invited' ? 'Invited — Pending' : member.status }}
              </span>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-[#ffffff08] flex justify-between items-center gap-2">
            <span class="text-[9px] px-2 py-0.5 rounded border uppercase font-bold tracking-wider shrink-0" :class="roleTierColor(member.role)">
              {{ member.role?.tier }}
            </span>
            <div class="flex items-center gap-1 ml-auto">
              <button @click="openEdit(member)" title="Edit Profile"
                      class="w-8 h-8 rounded-lg text-[#6B7E8E] hover:bg-[rgba(201,168,76,0.08)] hover:text-[#C9A84C] transition flex items-center justify-center">
                <i class="ti ti-edit text-sm"></i>
              </button>
              <button @click="openReset(member)" title="Reset Password"
                      class="w-8 h-8 rounded-lg text-[#6B7E8E] hover:bg-[rgba(255,100,100,0.08)] hover:text-[#E2635A] transition flex items-center justify-center">
                <i class="ti ti-key text-sm"></i>
              </button>
              <button @click="toggleStatus(member)" title="Toggle Status"
                      class="text-[10px] px-3 py-1 rounded border transition font-bold uppercase tracking-wider"
                      :class="member.status === 'active'
                        ? 'border-[#E2635A] text-[#E2635A] hover:bg-[#E2635A] hover:text-white'
                        : 'border-[#4CAF7D] text-[#4CAF7D] hover:bg-[#4CAF7D] hover:text-white'">
                {{ member.status === 'active' ? 'Deactivate' : 'Activate' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ LIST VIEW ═══ -->
      <div v-if="viewMode === 'list' && filtered.length"
           class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl overflow-hidden">
        <div class="grid grid-cols-[2fr_1.2fr_1.2fr_1fr_auto] gap-4 px-6 py-3 border-b border-[#ffffff0a] bg-[#0B1929]/50">
          <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold">Staff Member</span>
          <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold">Branch</span>
          <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold">Role</span>
          <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold">Status</span>
          <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold text-right">Actions</span>
        </div>

        <div v-for="member in filtered" :key="member.id"
             class="grid grid-cols-[2fr_1.2fr_1.2fr_1fr_auto] gap-4 items-center px-6 py-3.5 border-b border-[#ffffff08] last:border-0 hover:bg-[rgba(255,255,255,0.02)] transition"
             :class="member.status !== 'active' ? 'opacity-60' : ''">

          <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-[rgba(255,255,255,0.05)] border border-[#ffffff08] flex items-center justify-center text-sm font-bold text-[#C9A84C] shrink-0 overflow-hidden">
              <img v-if="member.avatar" :src="'/storage/' + member.avatar" class="w-full h-full object-cover" />
              <span v-else>{{ member.full_name.charAt(0) }}</span>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-[#F0EBE1] truncate">{{ member.full_name }}</p>
              <p class="text-[10px] font-mono text-[#6B7E8E]">{{ member.ident_number }}</p>
            </div>
          </div>

          <span class="text-xs text-[#A9B8C6] truncate">{{ member.branch?.branch_name ?? '—' }}</span>

          <span class="text-xs text-[#F0EBE1] truncate">{{ member.role?.role_name }}</span>

          <span class="text-xs font-semibold capitalize"
                :class="member.status === 'active' ? 'text-[#4CAF7D]' : 'text-[#E2635A]'">
            {{ member.status }}
          </span>

          <div class="flex items-center gap-1 justify-end">
            <button @click="openEdit(member)" title="Edit Profile"
                    class="w-8 h-8 rounded-lg text-[#6B7E8E] hover:bg-[rgba(201,168,76,0.08)] hover:text-[#C9A84C] transition flex items-center justify-center">
              <i class="ti ti-edit text-sm"></i>
            </button>
            <button @click="openReset(member)" title="Reset Password"
                    class="w-8 h-8 rounded-lg text-[#6B7E8E] hover:bg-[rgba(255,100,100,0.08)] hover:text-[#E2635A] transition flex items-center justify-center">
              <i class="ti ti-key text-sm"></i>
            </button>
            <button @click="toggleStatus(member)"
                    class="text-[10px] px-3 py-1 rounded border transition font-bold uppercase tracking-wider whitespace-nowrap"
                    :class="member.status === 'active'
                      ? 'border-[#E2635A] text-[#E2635A] hover:bg-[#E2635A] hover:text-white'
                      : 'border-[#4CAF7D] text-[#4CAF7D] hover:bg-[#4CAF7D] hover:text-white'">
              {{ member.status === 'active' ? 'Deactivate' : 'Activate' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ─────────────────────────────────────
           MODALS
           ───────────────────────────────────── -->

      <!-- Onboard Modal -->
      <div v-if="showCreateModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl max-h-[90vh] overflow-y-auto">

          <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-serif text-[#F0EBE1]">Onboard <span class="text-[#C9A84C]">New Staff</span></h2>
            <button @click="showCreateModal = false" class="text-[#6B7E8E] hover:text-white transition">
              <i class="ti ti-x text-2xl"></i>
            </button>
          </div>

          <!-- Auto-generated ID preview -->
          <div class="mb-6 p-4 rounded-2xl border"
               :class="previewId ? 'bg-[rgba(201,168,76,0.06)] border-[rgba(201,168,76,0.2)]' : 'bg-white/[0.02] border-[#ffffff08]'">
            <p class="text-[10px] text-[#6B7E8E] uppercase font-bold tracking-widest mb-1">Auto-Generated Staff Login ID</p>
            <div class="flex items-center gap-3">
              <span class="text-2xl font-mono font-bold" :class="previewId ? 'text-[#C9A84C]' : 'text-[#3a5068]'">
                {{ loadingId ? '...' : (previewId || 'Select a branch') }}
              </span>
              <span v-if="previewId" class="text-[10px] text-[#A9B8C6] italic">Staff will use this ID to log in</span>
            </div>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <div class="flex flex-col gap-2">
              <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Full Name</label>
              <input v-model="form.full_name" type="text" required
                     class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
              <p v-if="form.errors.full_name" class="text-xs text-[#E2635A]">{{ form.errors.full_name }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Email Address</label>
                <input v-model="form.email" type="email" required
                       class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
                <p v-if="form.errors.email" class="text-xs text-[#E2635A]">{{ form.errors.email }}</p>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Phone Number</label>
                <input v-model="form.phone" type="text" required placeholder="+252…"
                       class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
                <p v-if="form.errors.phone" class="text-xs text-[#E2635A]">{{ form.errors.phone }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Primary Branch</label>
                <select v-model="form.branch_id" required
                        class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition">
                  <option value="" disabled>Select branch…</option>
                  <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
                </select>
                <p v-if="form.errors.branch_id" class="text-xs text-[#E2635A]">{{ form.errors.branch_id }}</p>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Assigned Role</label>
                <select v-model="form.role_id" required
                        class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition">
                  <option value="" disabled>Select role…</option>
                  <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
                </select>
                <p v-if="form.errors.role_id" class="text-xs text-[#E2635A]">{{ form.errors.role_id }}</p>
              </div>
            </div>

            <!-- Invite info note — replaces password field -->
            <div class="bg-[rgba(201,168,76,0.07)] border border-[#C9A84C]/20 rounded-xl p-4 flex gap-3">
              <i class="ti ti-mail-forward text-[#C9A84C] text-lg shrink-0 mt-0.5"></i>
              <div>
                <p class="text-sm font-semibold text-[#C9A84C] mb-0.5">Invite by Email</p>
                <p class="text-xs text-[#7a9ab5] leading-relaxed">
                  An invitation email will be sent to the staff member's email address.
                  They will set their own password when they accept the invite.
                  The account stays <strong class="text-[#C9A84C]">pending</strong> until they accept.
                </p>
              </div>
            </div>

            <div class="pt-2">
              <button type="submit" :disabled="form.processing || !previewId"
                      class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-40 flex items-center justify-center gap-2">
                <i class="ti ti-send"></i>
                Send Invite &amp; Create Account
                <span v-if="previewId" class="ml-2 opacity-70">(ID: {{ previewId }})</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Staff Modal -->
      <div v-if="showEditModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl max-h-[90vh] overflow-y-auto">

          <div class="flex justify-between items-center mb-6">
            <div>
              <h2 class="text-2xl font-serif text-[#F0EBE1]">Edit <span class="text-[#C9A84C]">Staff Profile</span></h2>
              <p class="text-xs text-[#6B7E8E] mt-1 font-mono">{{ editTarget?.ident_number }}</p>
            </div>
            <button @click="showEditModal = false" class="text-[#6B7E8E] hover:text-white transition">
              <i class="ti ti-x text-2xl"></i>
            </button>
          </div>

          <form @submit.prevent="submitEdit" class="space-y-5">
            <div class="flex flex-col gap-2">
              <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Full Name</label>
              <input v-model="editForm.full_name" type="text" required
                     class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
              <p v-if="editForm.errors.full_name" class="text-xs text-[#E2635A]">{{ editForm.errors.full_name }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Email Address</label>
                <input v-model="editForm.email" type="email" required
                       class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
                <p v-if="editForm.errors.email" class="text-xs text-[#E2635A]">{{ editForm.errors.email }}</p>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Phone Number</label>
                <input v-model="editForm.phone" type="text"
                       class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
                <p v-if="editForm.errors.phone" class="text-xs text-[#E2635A]">{{ editForm.errors.phone }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Primary Branch</label>
                <select v-model="editForm.branch_id" required
                        class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition">
                  <option value="" disabled>Select branch…</option>
                  <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
                </select>
                <p v-if="editForm.errors.branch_id" class="text-xs text-[#E2635A]">{{ editForm.errors.branch_id }}</p>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Assigned Role</label>
                <select v-model="editForm.role_id" required
                        class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition">
                  <option value="" disabled>Select role…</option>
                  <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
                </select>
                <p v-if="editForm.errors.role_id" class="text-xs text-[#E2635A]">{{ editForm.errors.role_id }}</p>
              </div>
            </div>

            <div class="pt-2 flex gap-3">
              <button type="button" @click="showEditModal = false"
                      class="flex-1 border border-[#ffffff14] text-[#A9B8C6] font-bold py-4 rounded-xl hover:bg-white/5 transition">
                Cancel
              </button>
              <button type="submit" :disabled="editForm.processing"
                      class="flex-1 bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-40">
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reset Password Modal -->
      <div v-if="showResetModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-md rounded-3xl p-8 shadow-2xl">

          <div class="flex justify-between items-center mb-6">
            <div>
              <h2 class="text-2xl font-serif text-[#F0EBE1]">Reset <span class="text-[#C9A84C]">Password</span></h2>
              <p class="text-xs text-[#6B7E8E] mt-1">{{ resetTarget?.full_name }}</p>
            </div>
            <button @click="showResetModal = false" class="text-[#6B7E8E] hover:text-white transition">
              <i class="ti ti-x text-2xl"></i>
            </button>
          </div>

          <!-- Warning -->
          <div class="mb-6 p-4 rounded-xl bg-[rgba(226,99,90,0.08)] border border-[rgba(226,99,90,0.2)] flex items-start gap-3">
            <i class="ti ti-alert-triangle text-[#E2635A] mt-0.5 shrink-0"></i>
            <p class="text-xs text-[#E2635A] leading-relaxed">
              The staff member will be forced to change this password on their next login.
            </p>
          </div>

          <form @submit.prevent="submitReset" class="space-y-5">
            <div class="flex flex-col gap-2">
              <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">New Password</label>
              <input v-model="resetForm.password" type="password" required minlength="8"
                     class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
              <p v-if="resetForm.errors.password" class="text-xs text-[#E2635A]">{{ resetForm.errors.password }}</p>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Confirm Password</label>
              <input v-model="resetForm.password_confirmation" type="password" required
                     class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
            </div>

            <div class="pt-2 flex gap-3">
              <button type="button" @click="showResetModal = false"
                      class="flex-1 border border-[#ffffff14] text-[#A9B8C6] font-bold py-4 rounded-xl hover:bg-white/5 transition">
                Cancel
              </button>
              <button type="submit" :disabled="resetForm.processing"
                      class="flex-1 bg-[#E2635A] text-white font-bold py-4 rounded-xl hover:bg-red-500 transition disabled:opacity-40">
                Reset Password
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
