<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    logs: Object // Paginated logs
});
</script>

<template>
    <Head title="Staff Audit Logs" />

    <AuthenticatedLayout>
        <div class="shell max-w-7xl mx-auto p-4 md:p-10">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-[rgba(107,126,142,0.1)] border border-[rgba(107,126,142,0.2)] flex items-center justify-center text-3xl text-[#6B7E8E]">
                        <i class="ti ti-history"></i>
                    </div>
                    <div>
                        <h1 class="font-serif text-3xl text-[#F0EBE1]">System <em class="text-[#6B7E8E] not-italic">Audit Trail</em></h1>
                        <p class="text-[12px] text-[#A9B8C6] tracking-[0.2em] uppercase mt-1.5 font-bold">Historical Staff Activity & Data Integrity</p>
                    </div>
                </div>
            </div>

            <!-- Success Alert -->
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
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold">Timestamp</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold">Staff Member</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold">Branch</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold">Action</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold">Description</th>
                                <th class="px-6 py-5 text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#ffffff05]">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-white/[0.02] transition group">
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <p class="text-[13px] text-[#F0EBE1]">{{ new Date(log.created_at).toLocaleString() }}</p>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[rgba(255,255,255,0.05)] flex items-center justify-center text-xs text-[#C9A84C] font-serif uppercase">
                                            {{ log.staff?.full_name?.charAt(0) }}
                                        </div>
                                        <p class="text-[13px] font-medium text-[#F0EBE1] group-hover:text-[#C9A84C] transition">{{ log.staff?.full_name }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-[12px] text-[#A9B8C6]">
                                    {{ log.branch?.branch_name }}
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-widest"
                                          :class="log.action.includes('CREATE') ? 'bg-green-500/10 text-green-500' : 'bg-[#E8A830]/10 text-[#E8A830]'">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-[12px] text-[#A9B8C6] max-w-xs truncate">
                                    {{ log.description }}
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-[12px] text-[#6B7E8E] font-mono">
                                    {{ log.ip_address || 'Internal' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-6 border-t border-[#ffffff08] flex items-center justify-between bg-white/[0.01]">
                    <p class="text-xs text-[#6B7E8E]">Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} audit entries</p>
                    <div class="flex gap-2">
                        <Link v-for="link in logs.links" :key="link.label" 
                              :href="link.url || '#'"
                              v-html="link.label"
                              class="w-9 h-9 flex items-center justify-center rounded-lg border text-sm transition"
                              :class="link.active ? 'bg-[#C9A84C] border-[#C9A84C] text-[#0B1929]' : 'border-[#ffffff08] text-[#A9B8C6] hover:bg-white/5'" />
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
