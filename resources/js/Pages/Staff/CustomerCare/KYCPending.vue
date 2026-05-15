<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customers: Array
});
</script>

<template>
    <Head title="Pending KYC Collection" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-serif text-2xl text-[#C9A84C] leading-tight">KYC Document<br><em class="text-[#E5C97E] not-italic">Collection</em></h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-[#112236] overflow-hidden shadow-sm sm:rounded-lg border border-[#ffffff14]">
                    <div class="p-6 text-[#A9B8C6]">
                        <div v-if="customers.length === 0" class="text-center py-10">
                            No customers pending KYC upload.
                        </div>
                        <table v-else class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[11px] uppercase tracking-widest text-[#C9A84C] border-b border-[#ffffff08]">
                                    <th class="py-3 px-4">Customer Name</th>
                                    <th class="py-3 px-4">Phone</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="customer in customers" :key="customer.id" class="border-b border-[#ffffff04] hover:bg-[rgba(255,255,255,0.02)] transition">
                                    <td class="py-4 px-4 text-[#F0EBE1] font-medium">{{ customer.full_name }}</td>
                                    <td class="py-4 px-4">{{ customer.phone }}</td>
                                    <td class="py-4 px-4">
                                        <span v-if="customer.kyc_status === 'under_review'" class="px-2 py-1 rounded-full text-[10px] bg-[rgba(76,175,125,0.1)] text-[#4CAF7D] border border-[rgba(76,175,125,0.2)]">Under Review / Tig</span>
                                        <span v-else class="px-2 py-1 rounded-full text-[10px] bg-[rgba(232,168,48,0.1)] text-[#E8A830] border border-[rgba(232,168,48,0.2)]">Pending</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <Link :href="route('staff.customer-care.kyc', customer.id)" class="text-[#C9A84C] hover:underline text-sm">Upload Documents</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
