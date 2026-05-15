<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    roles: Array,
    allPermissions: Object,
    flash: Object
});

const selectedRole   = ref(props.roles[0] || null);
const showCreateModal = ref(false);
const showEditModal   = ref(false);

const permForm = useForm({
    role_id: selectedRole.value?.id || '',
    permissions: selectedRole.value?.permissions.map(p => p.id) || []
});

const createForm = useForm({
    role_name: '',
    tier: 'Staff',
    description: '',
    txn_limit: '',
});

const editForm = useForm({
    role_name: '',
    description: '',
    txn_limit: '',
});

const selectRole = (role) => {
    selectedRole.value   = role;
    permForm.role_id     = role.id;
    permForm.permissions = role.permissions.map(p => p.id);
};

const togglePermission = (id) => {
    const index = permForm.permissions.indexOf(id);
    if (index > -1) permForm.permissions.splice(index, 1);
    else permForm.permissions.push(id);
};

const submitPermissions = () => {
    permForm.post(route('staff.admin.roles.permissions'), { preserveScroll: true });
};

const submitCreate = () => {
    createForm.post(route('staff.admin.roles.store'), {
        onSuccess: () => { showCreateModal.value = false; createForm.reset(); }
    });
};

const openEdit = (role) => {
    editForm.role_name   = role.role_name;
    editForm.description = role.description;
    editForm.txn_limit   = role.txn_limit || '';
    showEditModal.value  = true;
    // store id for the route
    editForm._roleId     = role.id;
};

const submitEdit = () => {
    editForm.put(route('staff.admin.roles.update', editForm._roleId), {
        onSuccess: () => { showEditModal.value = false; }
    });
};
</script>

<template>
    <Head title="RBAC Administration" />

    <AuthenticatedLayout>
        <div class="shell max-w-7xl mx-auto p-4 md:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-[rgba(232,168,48,0.1)] border border-[rgba(232,168,48,0.2)] flex items-center justify-center text-3xl text-[#E8A830]">
                        <i class="ti ti-shield-lock"></i>
                    </div>
                    <div>
                        <h1 class="font-serif text-3xl text-[#F0EBE1]">Access <em class="text-[#E8A830] not-italic">Control</em></h1>
                        <p class="text-[12px] text-[#A9B8C6] tracking-[0.2em] uppercase mt-1.5 font-bold">RBAC Hierarchy & Permission Matrix</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div v-if="$page.props.flash?.success" class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] px-4 py-2 rounded-lg flex items-center gap-2 animate-fade">
                        <i class="ti ti-circle-check"></i>
                        <span class="text-sm">{{ $page.props.flash.success }}</span>
                    </div>
                    <button @click="showCreateModal = true" class="bg-[#E8A830] text-[#0B1929] px-5 py-2.5 rounded-xl font-bold hover:bg-[#F2BC5C] transition flex items-center gap-2">
                        <i class="ti ti-plus"></i> New Role
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Role List -->
                <div class="lg:col-span-4 space-y-3">
                    <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-4 px-2">Bank User Roles</h2>
                    <div v-for="role in roles" :key="role.id"
                         class="w-full text-left p-5 rounded-2xl border transition-all duration-300 cursor-pointer group"
                         :class="selectedRole?.id === role.id ? 'bg-[rgba(232,168,48,0.05)] border-[#E8A830] shadow-lg scale-[1.02]' : 'bg-[rgba(255,255,255,0.02)] border-[#ffffff08] hover:bg-[rgba(255,255,255,0.05)]'"
                         @click="selectRole(role)">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-base font-serif" :class="selectedRole?.id === role.id ? 'text-[#E8A830]' : 'text-[#F0EBE1]'">{{ role.role_name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-[#A9B8C6] font-bold tracking-tighter">{{ role.tier }}</span>
                                <button @click.stop="openEdit(role)" class="opacity-0 group-hover:opacity-100 text-[#6B7E8E] hover:text-[#E8A830] transition">
                                    <i class="ti ti-edit text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-[#6B7E8E] line-clamp-1 italic">"{{ role.description }}"</p>
                        <p v-if="role.txn_limit" class="text-[10px] text-[#A9B8C6] mt-1">
                            Txn Limit: <span class="text-[#E8A830] font-mono">${{ Number(role.txn_limit).toLocaleString() }}</span>
                        </p>
                    </div>
                </div>

                <!-- Permission Matrix -->
                <div class="lg:col-span-8">
                    <div v-if="selectedRole" class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-8 sticky top-24">
                        <div class="flex items-center justify-between mb-8 border-b border-[#ffffff08] pb-6">
                            <div>
                                <h3 class="text-xl text-[#F0EBE1] font-serif">Permissions for <span class="text-[#E8A830]">{{ selectedRole.role_name }}</span></h3>
                                <p class="text-xs text-[#A9B8C6] mt-1">Changes take effect immediately across all assigned staff.</p>
                            </div>
                            <button @click="submitPermissions" :disabled="permForm.processing" class="bg-[#E8A830] text-[#0B1929] px-6 py-2.5 rounded-xl font-bold hover:bg-[#F2BC5C] transition shadow-lg flex items-center gap-2">
                                <i class="ti ti-device-floppy text-lg"></i>
                                {{ permForm.processing ? 'Saving...' : 'Apply Changes' }}
                            </button>
                        </div>

                        <div class="space-y-10 max-h-[600px] overflow-y-auto pr-4 custom-scrollbar">
                            <div v-for="(perms, module) in allPermissions" :key="module" class="space-y-4">
                                <h4 class="text-[10px] uppercase tracking-[0.2em] text-[#E8A830] font-bold opacity-60 flex items-center gap-3">
                                    {{ module }}
                                    <div class="flex-1 h-[1px] bg-[#ffffff08]"></div>
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="p in perms" :key="p.id"
                                         @click="togglePermission(p.id)"
                                         class="p-4 rounded-2xl border transition-all cursor-pointer group flex items-start gap-4"
                                         :class="permForm.permissions.includes(p.id) ? 'bg-[rgba(76,175,125,0.05)] border-[rgba(76,175,125,0.3)]' : 'bg-transparent border-[#ffffff08] grayscale opacity-60 hover:grayscale-0 hover:opacity-100 hover:border-[#ffffff14]'">
                                        <div class="w-6 h-6 rounded-lg flex items-center justify-center transition"
                                             :class="permForm.permissions.includes(p.id) ? 'bg-[#4CAF7D] text-[#0B1929]' : 'bg-[#ffffff08] text-[#6B7E8E]'">
                                            <i class="ti" :class="permForm.permissions.includes(p.id) ? 'ti-check' : 'ti-lock-access'"></i>
                                        </div>
                                        <div>
                                            <p class="text-[13px] font-medium transition" :class="permForm.permissions.includes(p.id) ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">{{ p.permission_key }}</p>
                                            <p class="text-[11px] text-[#A9B8C6] mt-0.5 leading-snug">{{ p.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Role Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Create <span class="text-[#E8A830]">New Role</span></h2>
                        <button @click="showCreateModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <form @submit.prevent="submitCreate" class="space-y-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Role Name</label>
                            <input v-model="createForm.role_name" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition" />
                            <p v-if="createForm.errors.role_name" class="text-xs text-[#E2635A]">{{ createForm.errors.role_name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Tier</label>
                                <select v-model="createForm.tier" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition">
                                    <option value="System">System</option>
                                    <option value="Branch">Branch</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Txn Limit ($)</label>
                                <input v-model="createForm.txn_limit" type="number" min="0" placeholder="Leave blank = unlimited" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Description</label>
                            <textarea v-model="createForm.description" rows="2" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition resize-none"></textarea>
                        </div>
                        <button type="submit" :disabled="createForm.processing" class="w-full bg-[#E8A830] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#F2BC5C] transition disabled:opacity-50">
                            Create Role
                        </button>
                    </form>
                </div>
            </div>

            <!-- Edit Role Modal -->
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Edit <span class="text-[#E8A830]">Role</span></h2>
                        <button @click="showEditModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <form @submit.prevent="submitEdit" class="space-y-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Role Name</label>
                            <input v-model="editForm.role_name" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Txn Limit ($)</label>
                            <input v-model="editForm.txn_limit" type="number" min="0" placeholder="Leave blank = unlimited" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Description</label>
                            <textarea v-model="editForm.description" rows="2" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition resize-none"></textarea>
                        </div>
                        <button type="submit" :disabled="editForm.processing" class="w-full bg-[#E8A830] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#F2BC5C] transition disabled:opacity-50">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(232,168,48,0.2); border-radius: 10px; }
.animate-fade { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
</style>
