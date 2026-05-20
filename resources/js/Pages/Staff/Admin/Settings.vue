<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    accountTypes: Array,
    roles: Array,
    flash: Object
});

// ── Account Type modals ──────────────────────────────────────────────────────
const showAddTypeModal  = ref(false);
const showEditTypeModal = ref(false);
const editTypeTarget    = ref(null);

const addTypeForm = useForm({
    type_name: '',
    interest_rate: '',
    min_balance: '',
    withdrawal_limit: '',
    overdraft_allowed: false,
    overdraft_limit: '',
});

const editTypeForm = useForm({
    type_name: '',
    interest_rate: '',
    min_balance: '',
    withdrawal_limit: '',
    overdraft_allowed: false,
    overdraft_limit: '',
});

const openEditType = (type) => {
    editTypeTarget.value           = type;
    editTypeForm.type_name         = type.type_name;
    editTypeForm.interest_rate     = type.interest_rate;
    editTypeForm.min_balance       = type.min_balance;
    editTypeForm.withdrawal_limit  = type.withdrawal_limit || '';
    editTypeForm.overdraft_allowed = type.overdraft_allowed;
    editTypeForm.overdraft_limit   = type.overdraft_limit || '';
    showEditTypeModal.value        = true;
};

const submitAddType = () => {
    addTypeForm.post(route('staff.admin.settings.account-types.store'), {
        onSuccess: () => { showAddTypeModal.value = false; addTypeForm.reset(); }
    });
};

const submitEditType = () => {
    editTypeForm.put(route('staff.admin.settings.account-types.update', editTypeTarget.value.id), {
        onSuccess: () => { showEditTypeModal.value = false; }
    });
};

// ── Role Limit modals ────────────────────────────────────────────────────────
const showEditLimitModal = ref(false);
const editLimitTarget    = ref(null);

const editLimitForm = useForm({ txn_limit: '' });

const openEditLimit = (role) => {
    editLimitTarget.value    = role;
    editLimitForm.txn_limit  = role.txn_limit || '';
    showEditLimitModal.value = true;
};

const submitEditLimit = () => {
    editLimitForm.put(route('staff.admin.settings.role-limit', editLimitTarget.value.id), {
        onSuccess: () => { showEditLimitModal.value = false; }
    });
};
</script>

<template>
    <Head title="System Configuration" />

    <AuthenticatedLayout>
        <div class="shell max-w-7xl mx-auto p-4 md:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.2)] flex items-center justify-center text-3xl text-[#C9A84C]">
                        <i class="ti ti-settings-2"></i>
                    </div>
                    <div>
                        <h1 class="font-serif text-3xl text-[#F0EBE1]">Global <em class="text-[#C9A84C] not-italic">Settings</em></h1>
                        <p class="text-[12px] text-[#A9B8C6] tracking-[0.2em] uppercase mt-1.5 font-bold">Banking Products & Operational Limits</p>
                    </div>
                </div>
            </div>

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-xl mb-10 flex items-center gap-3">
                <i class="ti ti-circle-check text-xl"></i>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">

                <!-- Account Types -->
                <div class="space-y-6">
                    <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold px-2">Account Types & Interest Rates</h2>
                    <div class="bg-[#112236] border border-[#ffffff14] rounded-3xl overflow-hidden shadow-xl">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-white/[0.02] border-b border-[#ffffff08]">
                                    <th class="px-6 py-4 text-[10px] uppercase text-[#C9A84C]">Product Name</th>
                                    <th class="px-6 py-4 text-[10px] uppercase text-[#C9A84C]">Rate (%)</th>
                                    <th class="px-6 py-4 text-[10px] uppercase text-[#C9A84C]">Min Balance</th>
                                    <th class="px-6 py-4 text-[10px] uppercase text-[#A9B8C6] text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#ffffff05]">
                                <tr v-for="type in accountTypes" :key="type.id" class="hover:bg-white/[0.01]">
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-medium text-[#F0EBE1]">{{ type.type_name }}</p>
                                        <p v-if="type.overdraft_allowed" class="text-[10px] text-emerald-400 uppercase font-bold mt-1">
                                            Overdraft Enabled<span v-if="type.overdraft_limit"> · Max ${{ Number(type.overdraft_limit).toLocaleString() }}</span><span v-else> · Unlimited</span>
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 text-sm font-mono text-[#F0EBE1]">{{ type.interest_rate }}%</td>
                                    <td class="px-6 py-5 text-sm text-[#A9B8C6] font-mono">${{ Number(type.min_balance).toLocaleString() }}</td>
                                    <td class="px-6 py-5 text-right">
                                        <button @click="openEditType(type)" class="text-[#6B7E8E] hover:text-[#C9A84C] transition">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="p-6 bg-white/[0.02] border-t border-[#ffffff05]">
                            <button @click="showAddTypeModal = true" class="w-full py-3 rounded-xl border border-dashed border-[#ffffff20] text-[#A9B8C6] text-xs font-bold uppercase tracking-widest hover:border-[#C9A84C] hover:text-[#C9A84C] transition">
                                <i class="ti ti-plus mr-2"></i> Add Account Product
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Role Transaction Limits -->
                <div class="space-y-6">
                    <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold px-2">Operational Transaction Limits</h2>
                    <div class="bg-[#112236] border border-[#ffffff14] rounded-3xl overflow-hidden shadow-xl">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-white/[0.02] border-b border-[#ffffff08]">
                                    <th class="px-6 py-4 text-[10px] uppercase text-[#E8A830]">Role</th>
                                    <th class="px-6 py-4 text-[10px] uppercase text-[#E8A830]">Max Single Txn</th>
                                    <th class="px-6 py-4 text-[10px] uppercase text-[#A9B8C6] text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#ffffff05]">
                                <tr v-for="role in roles" :key="role.id" class="hover:bg-white/[0.01]">
                                    <td class="px-6 py-5 text-sm font-medium text-[#F0EBE1]">{{ role.role_name }}</td>
                                    <td class="px-6 py-5 text-sm font-mono" :class="role.txn_limit ? 'text-[#F0EBE1]' : 'text-[#6B7E8E] italic'">
                                        {{ role.txn_limit ? '$' + Number(role.txn_limit).toLocaleString() : 'Unlimited' }}
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <button @click="openEditLimit(role)" class="text-[#6B7E8E] hover:text-[#E8A830] transition">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="p-6 bg-[#E8A830]/[0.02] border-t border-[#ffffff05]">
                            <p class="text-[11px] text-[#6B7E8E] text-center italic">
                                <i class="ti ti-alert-circle mr-1"></i> Limits above $50,000 require System Administrator intervention.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Add Account Type Modal ──────────────────────────────────── -->
            <div v-if="showAddTypeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Add <span class="text-[#C9A84C]">Account Product</span></h2>
                        <button @click="showAddTypeModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <form @submit.prevent="submitAddType" class="space-y-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Product Name</label>
                            <input v-model="addTypeForm.type_name" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Interest Rate (%)</label>
                                <input v-model="addTypeForm.interest_rate" type="number" step="0.01" min="0" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Min Balance ($)</label>
                                <input v-model="addTypeForm.min_balance" type="number" step="0.01" min="0" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Withdrawal Limit ($) <span class="text-[#6B7E8E] normal-case">(optional)</span></label>
                            <input v-model="addTypeForm.withdrawal_limit" type="number" step="0.01" min="0" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" v-model="addTypeForm.overdraft_allowed" class="w-4 h-4 accent-[#C9A84C]" />
                            <span class="text-sm text-[#A9B8C6]">Overdraft Allowed</span>
                        </label>
                        <div v-if="addTypeForm.overdraft_allowed" class="flex flex-col gap-2">
                            <label class="text-[11px] text-emerald-400 font-bold uppercase tracking-widest">Max Overdraft Amount ($) <span class="text-[#6B7E8E] normal-case">(leave blank for unlimited)</span></label>
                            <input v-model="addTypeForm.overdraft_limit" type="number" step="0.01" min="0" placeholder="e.g. 500.00"
                                   class="bg-white/5 border border-emerald-600/30 rounded-xl p-4 text-[#F0EBE1] focus:border-emerald-400 outline-none transition" />
                        </div>
                        <button type="submit" :disabled="addTypeForm.processing" class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-50">
                            Add Product
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── Edit Account Type Modal ─────────────────────────────────── -->
            <div v-if="showEditTypeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-lg rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Edit <span class="text-[#C9A84C]">Account Product</span></h2>
                        <button @click="showEditTypeModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <form @submit.prevent="submitEditType" class="space-y-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Product Name</label>
                            <input v-model="editTypeForm.type_name" type="text" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Interest Rate (%)</label>
                                <input v-model="editTypeForm.interest_rate" type="number" step="0.01" min="0" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Min Balance ($)</label>
                                <input v-model="editTypeForm.min_balance" type="number" step="0.01" min="0" required class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#C9A84C] font-bold uppercase tracking-widest">Withdrawal Limit ($) <span class="text-[#6B7E8E] normal-case">(optional)</span></label>
                            <input v-model="editTypeForm.withdrawal_limit" type="number" step="0.01" min="0" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#C9A84C] outline-none transition" />
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" v-model="editTypeForm.overdraft_allowed" class="w-4 h-4 accent-[#C9A84C]" />
                            <span class="text-sm text-[#A9B8C6]">Overdraft Allowed</span>
                        </label>
                        <div v-if="editTypeForm.overdraft_allowed" class="flex flex-col gap-2">
                            <label class="text-[11px] text-emerald-400 font-bold uppercase tracking-widest">Max Overdraft Amount ($) <span class="text-[#6B7E8E] normal-case">(leave blank for unlimited)</span></label>
                            <input v-model="editTypeForm.overdraft_limit" type="number" step="0.01" min="0" placeholder="e.g. 500.00"
                                   class="bg-white/5 border border-emerald-600/30 rounded-xl p-4 text-[#F0EBE1] focus:border-emerald-400 outline-none transition" />
                        </div>
                        <button type="submit" :disabled="editTypeForm.processing" class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#E5C97E] transition disabled:opacity-50">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── Edit Role Limit Modal ───────────────────────────────────── -->
            <div v-if="showEditLimitModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0B1929] border border-[#ffffff14] w-full max-w-md rounded-3xl p-8 shadow-2xl">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-serif text-[#F0EBE1]">Set <span class="text-[#E8A830]">Transaction Limit</span></h2>
                        <button @click="showEditLimitModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
                    </div>
                    <p class="text-sm text-[#A9B8C6] mb-6">Updating limit for <strong class="text-[#F0EBE1]">{{ editLimitTarget?.role_name }}</strong>.</p>
                    <form @submit.prevent="submitEditLimit" class="space-y-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] text-[#E8A830] font-bold uppercase tracking-widest">Max Single Transaction ($)</label>
                            <input v-model="editLimitForm.txn_limit" type="number" step="0.01" min="0" placeholder="Leave blank for unlimited" class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E8A830] outline-none transition" />
                        </div>
                        <button type="submit" :disabled="editLimitForm.processing" class="w-full bg-[#E8A830] text-[#0B1929] font-bold py-4 rounded-xl hover:bg-[#F2BC5C] transition disabled:opacity-50">
                            Update Limit
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
