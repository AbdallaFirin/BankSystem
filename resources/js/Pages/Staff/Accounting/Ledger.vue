<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    entries: { type: Object, default: () => ({ data: [] }) }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
  <Head title="General Ledger Book" />

  <AuthenticatedLayout>
    <div class="shell max-w-6xl mx-auto p-4 md:p-8">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-[#ffffff14] gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[rgba(201,168,76,0.1)] flex items-center justify-center text-2xl text-[#C9A84C]">
            <i class="ti ti-report-money"></i>
          </div>
          <div>
            <h1 class="font-serif text-2xl text-[#F0EBE1]">General <em class="text-[#C9A84C] not-italic">Ledger</em></h1>
            <p class="text-[12px] text-[#A9B8C6] tracking-wide uppercase mt-1">Audit Trail & Transaction Log</p>
          </div>
        </div>
        <div class="flex gap-2">
            <Link :href="route('staff.accounting.trial-balance')" class="bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.2)] text-[#C9A84C] px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#C9A84C] hover:text-[#0B1929] transition">
                Trial Balance
            </Link>
        </div>
      </div>

      <!-- Ledger Table -->
      <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl overflow-hidden shadow-2xl relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#C9A84C] to-transparent"></div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[rgba(255,255,255,0.02)] border-b border-[#ffffff14]">
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold">Date</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold">Account Number</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold">Reference</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold">Type</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-right">Debit</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-right">Credit</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-right">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="entry in entries.data" :key="entry.id" class="border-b border-[rgba(255,255,255,0.05)] hover:bg-[rgba(255,255,255,0.01)] transition">
                    <td class="px-6 py-4 text-xs text-[#A9B8C6]">
                        {{ new Date(entry.created_at).toLocaleString() }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-medium text-[#F0EBE1]">{{ entry.account.account_number }}</span>
                    </td>
                    <td class="px-6 py-4 text-xs text-[#6B7E8E]">
                        {{ entry.transaction.reference }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                              :class="entry.entry_type === 'debit' ? 'bg-[rgba(226,99,90,0.1)] text-[#E2635A]' : 'bg-[rgba(76,175,125,0.1)] text-[#4CAF7D]'">
                           {{ entry.entry_type }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right text-sm" :class="entry.entry_type === 'debit' ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">
                        {{ entry.entry_type === 'debit' ? formatCurrency(entry.amount) : '-' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm" :class="entry.entry_type === 'credit' ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">
                        {{ entry.entry_type === 'credit' ? formatCurrency(entry.amount) : '-' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="text-sm font-serif text-[#C9A84C]">{{ formatCurrency(entry.balance_after) }}</span>
                    </td>
                </tr>

                <tr v-if="entries.data.length === 0">
                    <td colspan="7" class="px-6 py-20 text-center">
                        <i class="ti ti-database-off text-4xl text-[#6B7E8E] mb-4"></i>
                        <p class="text-[#A9B8C6]">No ledger records found for this branch.</p>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
      </div>

      <!-- Pagination Placeholder -->
      <div class="mt-6 flex justify-between items-center text-xs text-[#6B7E8E]">
          <span>Showing {{ entries.from || 0 }} to {{ entries.to || 0 }} of {{ entries.total || 0 }} entries</span>
          <div class="flex gap-2">
              <Link v-if="entries.prev_page_url" :href="entries.prev_page_url" class="px-3 py-1 bg-[rgba(255,255,255,0.05)] rounded hover:bg-[rgba(255,255,255,0.1)]">Prev</Link>
              <Link v-if="entries.next_page_url" :href="entries.next_page_url" class="px-3 py-1 bg-[rgba(255,255,255,0.05)] rounded hover:bg-[rgba(255,255,255,0.1)]">Next</Link>
          </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
