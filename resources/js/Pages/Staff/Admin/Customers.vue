<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customers: Object,
    branches:  { type: Array, default: () => [] },
    filters:   { type: Object, default: () => ({}) },
    flash:     Object,
});

const filters = reactive({
    search:     props.filters.search     ?? '',
    kyc_status: props.filters.kyc_status ?? '',
    branch_id:  props.filters.branch_id  ?? '',
});

let debounce = null;
watch(filters, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('staff.admin.customers.index'), { ...filters }, { preserveState: true, replace: true });
    }, 300);
});

const statusColors = {
    active:    'bg-[rgba(76,175,125,0.1)] text-[#4CAF7D] border-[rgba(76,175,125,0.2)]',
    suspended: 'bg-[rgba(232,168,48,0.1)] text-[#E8A830] border-[rgba(232,168,48,0.2)]',
    closed:    'bg-[rgba(226,99,90,0.1)] text-[#E2635A] border-[rgba(226,99,90,0.2)]',
};

const kycColors = {
    pending:  'text-[#E8A830]',
    verified: 'text-[#4CAF7D]',
    rejected: 'text-[#E2635A]',
};

const setStatus = (customer, status) => {
    const label = status === 'active' ? 'activate' : status === 'suspended' ? 'suspend' : 'close';
    if (!confirm(`Are you sure you want to ${label} ${customer.full_name}'s account?`)) return;
    useForm({ status }).post(route('staff.admin.customers.status', customer.id));
};
</script>

<template>
    <Head title="Customer Management" />

    <AuthenticatedLayout>
        <div class="shell max-w-7xl mx-auto p-4 md:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-[rgba(76,175,125,0.1)] border border-[rgba(76,175,125,0.2)] flex items-center justify-center text-3xl text-[#4CAF7D]">
                        <i class="ti ti-users"></i>
                    </div>
                    <div>
                        <h1 class="font-serif text-3xl text-[#F0EBE1]">Customer <em class="text-[#4CAF7D] not-italic">Management</em></h1>
                        <p class="text-[12px] text-[#A9B8C6] tracking-[0.2em] uppercase mt-1.5 font-bold">Global Customer Account Controls</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <div class="relative flex-1 min-w-48">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-[#6B7E8E]"></i>
                    <input v-model="filters.search" type="text" placeholder="Name, ID number, phone…"
                           class="w-full bg-[#112236] border border-[#ffffff14] rounded-xl pl-9 pr-3 py-2.5 text-sm text-[#F0EBE1]
                                  placeholder-[#6B7E8E] focus:outline-none focus:border-[#4CAF7D]/50 transition-colors" />
                </div>
                <select v-model="filters.kyc_status"
                        class="bg-[#112236] border border-[#ffffff14] rounded-xl px-3 py-2.5 text-sm text-[#F0EBE1] focus:outline-none focus:border-[#4CAF7D]/50 transition-colors">
                    <option value="">All KYC</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select v-model="filters.branch_id"
                        class="bg-[#112236] border border-[#ffffff14] rounded-xl px-3 py-2.5 text-sm text-[#F0EBE1] focus:outline-none focus:border-[#4CAF7D]/50 transition-colors">
                    <option value="">All Branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
                </select>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-xl mb-10 flex items-center gap-3">
                <i class="ti ti-circle-check text-xl"></i>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Table -->
            <div class="bg-[#112236] border border-[#ffffff14] rounded-3xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/[0.02] border-b border-[#ffffff08]">
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#4CAF7D] font-bold">Customer</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#4CAF7D] font-bold">Branch</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#4CAF7D] font-bold">KYC</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#4CAF7D] font-bold">Status</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#4CAF7D] font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#ffffff05]">
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-[#F0EBE1]">{{ customer.full_name }}</p>
                                    <p class="text-[11px] text-[#A9B8C6] mt-0.5 font-mono">{{ customer.phone }}</p>
                                </td>
                                <td class="px-6 py-4 text-[12px] text-[#A9B8C6]">{{ customer.branch?.branch_name }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-[11px] font-bold uppercase" :class="kycColors[customer.kyc_status] || 'text-[#6B7E8E]'">
                                        {{ customer.kyc_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                                          :class="statusColors[customer.status] || statusColors.active">
                                        {{ customer.status || 'active' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('staff.customer-care.profile', customer.id)"
                                              class="text-[11px] text-[#C9A84C] hover:underline font-bold mr-1">Profile</Link>
                                        <button v-if="customer.status !== 'active'"
                                                @click="setStatus(customer, 'active')"
                                                class="text-[10px] px-3 py-1 rounded border border-[#4CAF7D] text-[#4CAF7D] hover:bg-[#4CAF7D] hover:text-white transition font-bold uppercase">
                                            Activate
                                        </button>
                                        <button v-if="customer.status !== 'suspended'"
                                                @click="setStatus(customer, 'suspended')"
                                                class="text-[10px] px-3 py-1 rounded border border-[#E8A830] text-[#E8A830] hover:bg-[#E8A830] hover:text-[#0B1929] transition font-bold uppercase">
                                            Suspend
                                        </button>
                                        <button v-if="customer.status !== 'closed'"
                                                @click="setStatus(customer, 'closed')"
                                                class="text-[10px] px-3 py-1 rounded border border-[#E2635A] text-[#E2635A] hover:bg-[#E2635A] hover:text-white transition font-bold uppercase">
                                            Close
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-6 border-t border-[#ffffff08] flex items-center justify-between bg-white/[0.01]">
                    <p class="text-xs text-[#6B7E8E]">Showing {{ customers.from }} – {{ customers.to }} of {{ customers.total }} customers</p>
                    <div class="flex gap-2">
                        <Link v-for="link in customers.links" :key="link.label"
                              :href="link.url || '#'"
                              v-html="link.label"
                              class="w-9 h-9 flex items-center justify-center rounded-lg border text-sm transition"
                              :class="link.active ? 'bg-[#4CAF7D] border-[#4CAF7D] text-[#0B1929]' : 'border-[#ffffff08] text-[#A9B8C6] hover:bg-white/5'" />
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
