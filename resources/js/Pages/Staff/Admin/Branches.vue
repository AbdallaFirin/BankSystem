<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    branches: Array,
    allStaff: Array,
    flash: Object
});

/* ── View mode & filters ── */
const viewMode    = ref('cards')   // 'cards' | 'list'
const searchQuery = ref('')
const filterCity  = ref('')
const filterStatus = ref('')

const uniqueCities = computed(() => [...new Set((props.branches ?? []).map(b => b.city).filter(Boolean))].sort())

const filtered = computed(() => {
    let list = props.branches ?? []
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        list = list.filter(b =>
            b.branch_name.toLowerCase().includes(q) ||
            b.city?.toLowerCase().includes(q) ||
            (b.full_code || b.branch_number || '').toLowerCase().includes(q)
        )
    }
    if (filterCity.value)   list = list.filter(b => b.city === filterCity.value)
    if (filterStatus.value) list = list.filter(b => b.status === filterStatus.value)
    return list
})

/* ── Modals ── */
const showCreateModal  = ref(false);
const showEditModal    = ref(false);
const showManagerModal = ref(false);
const editTarget       = ref(null);
const managerTarget    = ref(null);

const form = useForm({ branch_name: '', branch_code: '', city: '', address: '', phone: '' });
const editForm = useForm({ branch_name: '', branch_code: '', city: '', address: '', phone: '' });
const managerForm = useForm({ manager_id: '' });

const submit = () => {
    form.post(route('staff.admin.branches.store'), {
        onSuccess: () => { showCreateModal.value = false; form.reset(); }
    });
};

const openEdit = (branch) => {
    editTarget.value = branch;
    editForm.branch_name = branch.branch_name;
    editForm.branch_code = branch.branch_code ?? '';
    editForm.city        = branch.city;
    editForm.address     = branch.address;
    editForm.phone       = branch.phone;
    showEditModal.value  = true;
};

const submitEdit = () => {
    editForm.put(route('staff.admin.branches.update', editTarget.value.id), {
        onSuccess: () => { showEditModal.value = false; }
    });
};

const toggleStatus = (branch) => {
    if (confirm(`${branch.status === 'active' ? 'Deactivate' : 'Activate'} ${branch.branch_name}?`)) {
        useForm({}).post(route('staff.admin.branches.status', branch.id));
    }
};

const openManager = (branch) => {
    managerTarget.value    = branch;
    managerForm.manager_id = branch.manager_id || '';
    showManagerModal.value = true;
};

const submitManager = () => {
    managerForm.post(route('staff.admin.branches.manager', managerTarget.value.id), {
        onSuccess: () => { showManagerModal.value = false; }
    });
};
</script>

<template>
    <Head title="Branch Management" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto p-4 md:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-[#ffffff14] gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.2)] flex items-center justify-center text-3xl text-[#C9A84C]">
                        <i class="ti ti-building-bank"></i>
                    </div>
                    <div>
                        <h1 class="font-serif text-3xl text-[#F0EBE1]">Bank <em class="text-[#C9A84C] not-italic">Branches</em></h1>
                        <p class="text-[12px] text-[#A9B8C6] tracking-[0.2em] uppercase mt-1.5 font-bold">
                            Network Infrastructure &amp; Performance
                            <span class="ml-2 text-[#C9A84C]">{{ filtered.length }} shown</span>
                        </p>
                    </div>
                </div>
                <button @click="showCreateModal = true"
                        class="bg-[#C9A84C] text-[#0B1929] px-6 py-3 rounded-xl font-bold hover:bg-[#E5C97E] transition shadow-lg flex items-center gap-2">
                    <i class="ti ti-plus text-lg"></i> Establish New Branch
                </button>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success"
                 class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-xl mb-6 flex items-center gap-3">
                <i class="ti ti-circle-check text-xl"></i>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- ── Filter / Search Bar ── -->
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <!-- Search -->
                <div class="relative flex-1 min-w-[180px]">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-[#6B7E8E]"></i>
                    <input v-model="searchQuery" type="text" placeholder="Search branch name, city, code…"
                           class="w-full bg-[#0B1929] border border-[#ffffff14] text-[#F0EBE1] text-sm rounded-xl pl-9 pr-4 py-2.5 focus:border-[#C9A84C] outline-none transition" />
                </div>

                <!-- City filter -->
                <select v-model="filterCity"
                        class="bg-[#0B1929] border border-[#ffffff14] text-[#A9B8C6] text-sm rounded-xl px-4 py-2.5 focus:border-[#C9A84C] outline-none transition">
                    <option value="">All Cities</option>
                    <option v-for="city in uniqueCities" :key="city" :value="city">{{ city }}</option>
                </select>

                <!-- Status filter -->
                <select v-model="filterStatus"
                        class="bg-[#0B1929] border border-[#ffffff14] text-[#A9B8C6] text-sm rounded-xl px-4 py-2.5 focus:border-[#C9A84C] outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <!-- Clear filters -->
                <button v-if="searchQuery || filterCity || filterStatus"
                        @click="searchQuery = ''; filterCity = ''; filterStatus = ''"
                        class="text-[#6B7E8E] hover:text-[#C9A84C] text-sm flex items-center gap-1 transition">
                    <i class="ti ti-x"></i> Clear
                </button>

                <!-- Spacer -->
                <div class="flex-1"></div>

                <!-- View toggle -->
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
                <i class="ti ti-building-bank text-5xl mb-3 block"></i>
                <p class="text-lg font-medium">No branches match your filters</p>
                <button @click="searchQuery = ''; filterCity = ''; filterStatus = ''"
                        class="mt-3 text-sm text-[#C9A84C] hover:underline">Clear filters</button>
            </div>

            <!-- ═══ CARDS VIEW ═══ -->
            <div v-if="viewMode === 'cards' && filtered.length"
                 class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-8">
                <div v-for="branch in filtered" :key="branch.id"
                     class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-8 hover:border-[#C9A84C] transition duration-300 group"
                     :class="branch.status !== 'active' ? 'opacity-60' : ''">

                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-xl font-serif text-[#F0EBE1] group-hover:text-[#C9A84C] transition">{{ branch.branch_name }}</h3>
                                <span class="px-2 py-0.5 rounded font-mono text-[10px] font-black tracking-widest bg-[rgba(201,168,76,0.12)] text-[#C9A84C] border border-[rgba(201,168,76,0.25)]">
                                    {{ branch.full_code || branch.branch_number }}
                                </span>
                            </div>
                            <p class="text-sm text-[#A9B8C6]">{{ branch.city }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border"
                              :class="branch.status === 'active'
                                ? 'bg-[rgba(76,175,125,0.1)] text-[#4CAF7D] border-[rgba(76,175,125,0.2)]'
                                : 'bg-[rgba(226,99,90,0.1)] text-[#E2635A] border-[rgba(226,99,90,0.2)]'">
                            {{ branch.status }}
                        </span>
                    </div>

                    <div class="mb-6">
                        <span class="text-[10px] text-[#6B7E8E] uppercase font-bold tracking-widest">Branch Manager</span>
                        <p class="text-sm text-[#F0EBE1] mt-1">
                            <span v-if="branch.manager">{{ branch.manager.full_name }}</span>
                            <span v-else class="text-[#6B7E8E] italic">Unassigned</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div class="space-y-1">
                            <span class="text-[10px] text-[#6B7E8E] uppercase font-bold tracking-widest">Active Accounts</span>
                            <p class="text-2xl text-[#F0EBE1] font-medium">{{ branch.accounts_count }}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] text-[#6B7E8E] uppercase font-bold tracking-widest">Branch Staff</span>
                            <p class="text-2xl text-[#F0EBE1] font-medium">{{ branch.staff_count }}</p>
                        </div>
                        <div class="col-span-2 space-y-1 pt-4 border-t border-[#ffffff08]">
                            <span class="text-[10px] text-[#6B7E8E] uppercase font-bold tracking-widest">Cumulative Balance</span>
                            <p class="text-2xl text-[#C9A84C] font-serif">${{ (branch.total_balance || 0).toLocaleString() }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-6 border-t border-[#ffffff08]">
                        <div class="flex-1 text-[11px] text-[#A9B8C6] leading-relaxed">
                            <i class="ti ti-map-pin mr-1 text-[#C9A84C]"></i> {{ branch.address }}<br>
                            <i class="ti ti-phone mr-1 text-[#C9A84C]"></i> {{ branch.phone }}
                        </div>
                        <button @click="openManager(branch)" title="Assign Manager"
                                class="w-10 h-10 rounded-lg bg-[rgba(255,255,255,0.03)] text-[#6B7E8E] hover:bg-[rgba(201,168,76,0.08)] hover:text-[#C9A84C] transition flex items-center justify-center">
                            <i class="ti ti-user-star"></i>
                        </button>
                        <button @click="openEdit(branch)" title="Edit Branch"
                                class="w-10 h-10 rounded-lg bg-[rgba(255,255,255,0.03)] text-[#6B7E8E] hover:bg-[rgba(255,255,255,0.06)] hover:text-[#C9A84C] transition flex items-center justify-center">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button @click="toggleStatus(branch)"
                                class="w-10 h-10 rounded-lg bg-[rgba(255,255,255,0.03)] transition flex items-center justify-center"
                                :class="branch.status === 'active' ? 'text-[#E2635A] hover:bg-[rgba(226,99,90,0.08)]' : 'text-[#4CAF7D] hover:bg-[rgba(76,175,125,0.08)]'">
                            <i :class="branch.status === 'active' ? 'ti ti-toggle-right' : 'ti ti-toggle-left'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══ LIST VIEW ═══ -->
            <div v-if="viewMode === 'list' && filtered.length"
                 class="bg-[#112236] border border-[#ffffff14] rounded-2xl overflow-hidden">
                <!-- Table header -->
                <div class="grid grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] gap-4 px-6 py-3 border-b border-[#ffffff0a] bg-[#0B1929]/50">
                    <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold">Branch</span>
                    <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold">City</span>
                    <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold">Manager</span>
                    <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold text-right">Staff</span>
                    <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold text-right">Balance</span>
                    <span class="text-[10px] uppercase tracking-widest text-[#6B7E8E] font-bold text-right">Actions</span>
                </div>
                <div v-for="branch in filtered" :key="branch.id"
                     class="grid grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] gap-4 items-center px-6 py-4 border-b border-[#ffffff08] last:border-0 hover:bg-[rgba(255,255,255,0.02)] transition"
                     :class="branch.status !== 'active' ? 'opacity-60' : ''">

                    <!-- Name + code + status -->
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.15)] flex items-center justify-center text-[#C9A84C] shrink-0">
                            <i class="ti ti-building-bank text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-[#F0EBE1] truncate">{{ branch.branch_name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="font-mono text-[9px] bg-[rgba(201,168,76,0.1)] text-[#C9A84C] px-1.5 py-0.5 rounded border border-[rgba(201,168,76,0.2)]">
                                    {{ branch.full_code || branch.branch_number }}
                                </span>
                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border"
                                      :class="branch.status === 'active'
                                        ? 'text-[#4CAF7D] border-[rgba(76,175,125,0.2)] bg-[rgba(76,175,125,0.08)]'
                                        : 'text-[#E2635A] border-[rgba(226,99,90,0.2)] bg-[rgba(226,99,90,0.08)]'">
                                    {{ branch.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <span class="text-sm text-[#A9B8C6] truncate">{{ branch.city }}</span>

                    <span class="text-sm text-[#A9B8C6] truncate">
                        {{ branch.manager?.full_name || '—' }}
                    </span>

                    <span class="text-sm text-[#F0EBE1] text-right">{{ branch.staff_count }}</span>

                    <span class="text-sm text-[#C9A84C] font-semibold text-right font-mono">
                        ${{ (branch.total_balance || 0).toLocaleString() }}
                    </span>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-1 justify-end">
                        <button @click="openManager(branch)" title="Assign Manager"
                                class="w-8 h-8 rounded-lg text-[#6B7E8E] hover:bg-[rgba(201,168,76,0.08)] hover:text-[#C9A84C] transition flex items-center justify-center">
                            <i class="ti ti-user-star text-sm"></i>
                        </button>
                        <button @click="openEdit(branch)" title="Edit"
                                class="w-8 h-8 rounded-lg text-[#6B7E8E] hover:bg-[rgba(255,255,255,0.06)] hover:text-[#C9A84C] transition flex items-center justify-center">
                            <i class="ti ti-edit text-sm"></i>
                        </button>
                        <button @click="toggleStatus(branch)"
                                class="w-8 h-8 rounded-lg transition flex items-center justify-center"
                                :class="branch.status === 'active'
                                    ? 'text-[#E2635A] hover:bg-[rgba(226,99,90,0.08)]'
                                    : 'text-[#4CAF7D] hover:bg-[rgba(76,175,125,0.08)]'">
                            <i :class="branch.status === 'active' ? 'ti ti-toggle-right text-sm' : 'ti ti-toggle-left text-sm'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── Modals (unchanged) ─── -->

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Establish <span class="text-[#C9A84C]">New Bank Branch</span></h2>
                        <button @click="showCreateModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Branch Legal Name</label>
                            <input v-model="form.branch_name" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            <p v-if="form.errors.branch_name" class="text-[11px] text-red-400">{{ form.errors.branch_name }}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Branch Code Prefix</label>
                            <div class="flex items-center gap-0">
                                <input v-model="form.branch_code" type="text" maxlength="10" placeholder="e.g. BOS"
                                       @input="form.branch_code = form.branch_code.toUpperCase()"
                                       class="flex-1 bg-white/5 border border-white/10 rounded-l-xl p-4 text-[#C9A84C] font-mono font-bold tracking-widest focus:border-[#C9A84C] outline-none transition uppercase" />
                                <div class="px-4 py-4 bg-[rgba(201,168,76,0.08)] border border-l-0 border-[rgba(201,168,76,0.25)] rounded-r-xl text-[#C9A84C] font-mono font-black tracking-widest text-sm whitespace-nowrap">
                                    + <span class="text-white">101</span> (auto)
                                </div>
                            </div>
                            <p class="text-[10px] text-[#6B7E8E]">Final code e.g. <span class="text-[#C9A84C] font-mono">{{ form.branch_code || 'BOS' }}101</span></p>
                            <p v-if="form.errors.branch_code" class="text-[11px] text-red-400">{{ form.errors.branch_code }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">City</label>
                                <input v-model="form.city" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Contact Phone</label>
                                <input v-model="form.phone" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Geographic Address</label>
                            <textarea v-model="form.address" required rows="2" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition resize-none"></textarea>
                        </div>
                        <div class="pt-4">
                            <button type="submit" :disabled="form.processing" class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-50">
                                Confirm &amp; Initialize Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Modal -->
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Edit <span class="text-[#C9A84C]">Branch</span></h2>
                        <button @click="showEditModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <form @submit.prevent="submitEdit" class="space-y-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Branch Legal Name</label>
                            <input v-model="editForm.branch_name" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            <p v-if="editForm.errors.branch_name" class="text-[11px] text-red-400">{{ editForm.errors.branch_name }}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Branch Code Prefix</label>
                            <div class="flex items-center gap-0">
                                <input v-model="editForm.branch_code" type="text" maxlength="10" placeholder="e.g. BOS"
                                       @input="editForm.branch_code = editForm.branch_code.toUpperCase()"
                                       class="flex-1 bg-white/5 border border-white/10 rounded-l-xl p-4 text-[#C9A84C] font-mono font-bold tracking-widest focus:border-[#C9A84C] outline-none transition uppercase" />
                                <div class="px-5 py-4 bg-[rgba(255,255,255,0.03)] border border-l-0 border-white/10 rounded-r-xl text-slate-400 font-mono font-black tracking-widest text-sm">
                                    {{ editTarget?.branch_number ?? '—' }}
                                    <span class="text-[9px] text-slate-600 block font-normal tracking-normal">locked</span>
                                </div>
                            </div>
                            <p class="text-[10px] text-[#6B7E8E]">
                                Full code: <span class="text-[#C9A84C] font-mono">{{ editForm.branch_code || '?' }}{{ editTarget?.branch_number }}</span>
                            </p>
                            <p v-if="editForm.errors.branch_code" class="text-[11px] text-red-400">{{ editForm.errors.branch_code }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">City</label>
                                <input v-model="editForm.city" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Contact Phone</label>
                                <input v-model="editForm.phone" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Geographic Address</label>
                            <textarea v-model="editForm.address" required rows="2" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition resize-none"></textarea>
                        </div>
                        <div class="pt-4">
                            <button type="submit" :disabled="editForm.processing" class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-50">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Assign Manager Modal -->
            <div v-if="showManagerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-md rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Assign <span class="text-[#C9A84C]">Branch Manager</span></h2>
                        <button @click="showManagerModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <p class="text-sm text-[#A9B8C6] mb-6">Select a staff member to manage <strong class="text-[#F0EBE1]">{{ managerTarget?.branch_name }}</strong>.</p>
                    <form @submit.prevent="submitManager" class="space-y-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Manager</label>
                            <select v-model="managerForm.manager_id" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition">
                                <option value="">— Unassign —</option>
                                <option v-for="s in allStaff" :key="s.id" :value="s.id">{{ s.full_name }}</option>
                            </select>
                        </div>
                        <button type="submit" :disabled="managerForm.processing" class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-50">
                            Confirm Assignment
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
