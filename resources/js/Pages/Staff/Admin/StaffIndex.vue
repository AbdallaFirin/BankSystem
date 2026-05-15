<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    staff:    { type: Array, default: () => [] },
    roles:    Array,
    branches: Array,
    flash:    Object
});

const showCreateModal = ref(false);
const previewId       = ref('');
const loadingId       = ref(false);

const form = useForm({
    full_name: '',
    email:     '',
    phone:     '',
    password:  '',
    role_id:   '',
    branch_id: '',
});

// Live Staff ID preview whenever branch changes
watch(() => form.branch_id, async (branchId) => {
    if (!branchId) { previewId.value = ''; return; }
    loadingId.value = true;
    try {
        const { data } = await axios.get(route('staff.admin.staff.preview-id'), {
            params: { branch_id: branchId }
        });
        previewId.value = data.staff_id;
    } catch {
        previewId.value = '—';
    } finally {
        loadingId.value = false;
    }
});

const openModal = () => {
    form.reset();
    previewId.value = '';
    showCreateModal.value = true;
};

const submit = () => {
    form.post(route('staff.admin.staff.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
            previewId.value = '';
        }
    });
};

const toggleStatus = (member) => {
    const newStatus = member.status === 'active' ? 'inactive' : 'active';
    if (confirm(`${newStatus === 'active' ? 'Activate' : 'Deactivate'} ${member.full_name}?`)) {
        useForm({ status: newStatus }).post(route('staff.admin.staff.status', member.id));
    }
};
</script>

<template>
  <Head title="Staff Directory — Global HQ" />

  <AuthenticatedLayout>
    <div class="shell max-w-7xl mx-auto p-4 md:p-8">

      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[rgba(201,168,76,0.1)] flex items-center justify-center text-2xl text-[#C9A84C]">
            <i class="ti ti-users-group"></i>
          </div>
          <div>
            <h1 class="font-serif text-2xl text-[#F0EBE1]">Global <em class="text-[#C9A84C] not-italic">Staff</em> Directory</h1>
            <p class="text-[12px] text-[#A9B8C6] tracking-wide uppercase mt-1">Full Personnel Roster & Role Matrix</p>
          </div>
        </div>

        <button @click="openModal"
                class="bg-[#C9A84C] text-[#0B1929] px-6 py-2.5 rounded-xl font-bold hover:bg-[#E5C97E] transition shadow-lg flex items-center gap-2">
          <i class="ti ti-user-plus text-lg"></i>
          Onboard New Staff
        </button>
      </div>

      <!-- Flash -->
      <div v-if="$page.props.flash?.success"
           class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-xl mb-10 flex items-center gap-3">
        <i class="ti ti-circle-check text-xl"></i>
        <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
      </div>

      <!-- Staff Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="member in staff" :key="member.id"
             class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6 hover:bg-[rgba(255,255,255,0.03)] transition duration-300 relative group overflow-hidden">
          <div class="absolute top-0 right-0 w-24 h-24 bg-[rgba(201,168,76,0.03)] rounded-bl-full -mr-8 -mt-8 transition-all group-hover:bg-[rgba(201,168,76,0.06)]"></div>

          <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-[rgba(255,255,255,0.05)] border border-[#ffffff08] flex items-center justify-center text-xl font-serif text-[#C9A84C]">
              {{ member.full_name.charAt(0) }}
            </div>
            <div>
              <h3 class="text-base font-medium text-[#F0EBE1]">{{ member.full_name }}</h3>
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
              <span>{{ member.branch?.branch_name }}</span>
            </div>
            <div class="flex items-center gap-3 text-xs text-[#A9B8C6]">
              <i class="ti ti-mail w-4"></i>
              <span>{{ member.email }}</span>
            </div>
            <div v-if="member.phone" class="flex items-center gap-3 text-xs text-[#A9B8C6]">
              <i class="ti ti-phone w-4"></i>
              <span>{{ member.phone }}</span>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <i class="ti ti-activity w-4 text-[#A9B8C6]"></i>
              <span class="capitalize font-medium" :class="member.status === 'active' ? 'text-[#4CAF7D]' : 'text-[#E2635A]'">
                {{ member.status }}
              </span>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-[#ffffff08] flex justify-between items-center">
            <span class="text-[10px] text-[#6B7E8E]">Since {{ new Date(member.created_at).toLocaleDateString() }}</span>
            <button @click="toggleStatus(member)"
                    class="text-[10px] px-3 py-1 rounded border transition font-bold uppercase tracking-wider"
                    :class="member.status === 'active'
                      ? 'border-[#E2635A] text-[#E2635A] hover:bg-[#E2635A] hover:text-white'
                      : 'border-[#4CAF7D] text-[#4CAF7D] hover:bg-[#4CAF7D] hover:text-white'">
              {{ member.status === 'active' ? 'Deactivate' : 'Activate' }}
            </button>
          </div>
        </div>
      </div>

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
              <span v-if="previewId" class="text-[10px] text-[#A9B8C6] italic">This is the ID the staff member will use to log in</span>
            </div>
          </div>

          <form @submit.prevent="submit" class="space-y-5">

            <!-- Full Name -->
            <div class="flex flex-col gap-2">
              <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Full Name</label>
              <input v-model="form.full_name" type="text" required
                     class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
              <p v-if="form.errors.full_name" class="text-xs text-[#E2635A]">{{ form.errors.full_name }}</p>
            </div>

            <!-- Email + Phone -->
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

            <!-- Branch + Role -->
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

            <!-- Password -->
            <div class="flex flex-col gap-2">
              <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Initial Password</label>
              <input v-model="form.password" type="password" required minlength="8"
                     class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] outline-none focus:border-[#C9A84C] transition" />
              <p class="text-[10px] text-[#6B7E8E]">Minimum 8 characters. Staff can change it after first login.</p>
              <p v-if="form.errors.password" class="text-xs text-[#E2635A]">{{ form.errors.password }}</p>
            </div>

            <div class="pt-2">
              <button type="submit" :disabled="form.processing || !previewId"
                      class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-40">
                Initialize Staff Account
                <span v-if="previewId" class="ml-2 opacity-70">(ID: {{ previewId }})</span>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
