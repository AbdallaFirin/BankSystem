<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    customers: Array,
    accountTypes: Array,
    branches: Array,
    executing_branch_id: Number,
    flash: Object
});

// Auto-populated from the logged-in staff's branch (cannot be changed by CCO)
const executingBranch = computed(() => props.branches?.find(b => b.id === props.executing_branch_id));

const form = useForm({
    customer_id: '',
    account_type_id: '',
    home_branch_id: props.executing_branch_id, // Auto-set to staff's branch
    initial_deposit: ''
});

const submit = () => {
    form.post(route('staff.customer-care.accounts.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Account Opening" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-serif text-2xl text-[#C9A84C] leading-tight">Additional<br><em class="text-[#E5C97E] not-italic">Account Opening</em></h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Alert -->
                <div v-if="$page.props.flash?.success" class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-xl mb-6 flex items-center gap-3 animate-pulse">
                    <i class="ti ti-circle-check text-xl"></i>
                    <span class="text-sm font-bold tracking-wide">{{ $page.props.flash.success }}</span>
                </div>

                <div class="bg-[#112236] border border-[#ffffff14] p-8 rounded-2xl shadow-xl">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[12px] uppercase tracking-widest text-[#C9A84C] font-semibold">Select Customer</label>
                                <select v-model="form.customer_id" class="form-select bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-3 text-[#F0EBE1] outline-none focus:border-[#C9A84C]">
                                    <option value="">Search Customer...</option>
                                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.full_name }} ({{ c.phone }})</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[12px] uppercase tracking-widest text-[#C9A84C] font-semibold">Account Type</label>
                                <select v-model="form.account_type_id" class="form-select bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-3 text-[#F0EBE1] outline-none focus:border-[#C9A84C]">
                                    <option value="">Select Type</option>
                                    <option v-for="t in accountTypes" :key="t.id" :value="t.id">{{ t.type_name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[12px] uppercase tracking-widest text-[#C9A84C] font-semibold">Executing Branch</label>
                                <!-- Auto-populated from staff's branch — read-only -->
                                <div class="bg-[rgba(201,168,76,0.05)] border border-[rgba(201,168,76,0.2)] rounded-lg p-3 flex items-center gap-3">
                                    <i class="ti ti-building-bank text-[#C9A84C]"></i>
                                    <div>
                                        <p class="text-[#F0EBE1] font-medium text-sm">{{ executingBranch?.branch_name || 'Auto-assigned' }}</p>
                                        <p class="text-[10px] text-[#A9B8C6] uppercase tracking-wider">Registered Staff Branch • Auto-assigned</p>
                                    </div>
                                </div>
                                <!-- Hidden input carries the value -->
                                <input type="hidden" :value="form.home_branch_id" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[12px] uppercase tracking-widest text-[#C9A84C] font-semibold">Initial Deposit ($)</label>
                                <input type="number" step="0.01" v-model="form.initial_deposit" placeholder="0.00" class="bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-3 text-[#F0EBE1] outline-none focus:border-[#C9A84C]" />
                            </div>
                        </div>

                        <div class="pt-6 border-t border-[#ffffff08]">
                            <button type="submit" class="w-full bg-[#C9A84C] text-[#0B1929] font-bold py-3 rounded-xl hover:bg-[#E5C97E] transition active:scale-95 shadow-lg">
                                Open Additional Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23C9A84C' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
}
</style>
