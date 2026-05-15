<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    trialBalance: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({ debit: 0, credit: 0, is_balanced: true }) }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
  <Head title="Trial Balance Verification" />

  <AuthenticatedLayout>
    <div class="shell max-w-5xl mx-auto p-4 md:p-8">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[rgba(138,99,210,0.1)] flex items-center justify-center text-2xl text-[#8A63D2]">
            <i class="ti ti-scale"></i>
          </div>
          <div>
            <h1 class="font-serif text-2xl text-[#F0EBE1]">Trial <em class="text-[#8A63D2] not-italic">Balance</em></h1>
            <p class="text-[12px] text-[#A9B8C6] tracking-wide uppercase mt-1">Mathematical verification of double-entry ledger</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-lg border flex items-center gap-2"
                 :class="totals.is_balanced ? 'bg-[rgba(76,175,125,0.05)] border-[rgba(76,175,125,0.2)] text-[#4CAF7D]' : 'bg-[rgba(226,99,90,0.05)] border-[rgba(226,99,90,0.2)] text-[#E2635A]'">
                <i :class="totals.is_balanced ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
                <span class="text-sm font-bold uppercase tracking-wider">{{ totals.is_balanced ? 'Balanced' : 'Out of Balance' }}</span>
            </div>
        </div>
      </div>

      <!-- Totals Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
          <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6 relative overflow-hidden">
              <div class="absolute top-0 right-0 p-4 opacity-5 text-4xl"><i class="ti ti-arrow-bar-down"></i></div>
              <span class="text-[10px] uppercase tracking-widest text-[#A9B8C6] font-bold">Total Debits</span>
              <h2 class="text-3xl font-serif text-[#F0EBE1] mt-2">{{ formatCurrency(totals.debit) }}</h2>
          </div>
          <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6 relative overflow-hidden">
              <div class="absolute top-0 right-0 p-4 opacity-5 text-4xl"><i class="ti ti-arrow-bar-up"></i></div>
              <span class="text-[10px] uppercase tracking-widest text-[#A9B8C6] font-bold">Total Credits</span>
              <h2 class="text-3xl font-serif text-[#F0EBE1] mt-2">{{ formatCurrency(totals.credit) }}</h2>
          </div>
      </div>

      <!-- Trial Balance Table -->
      <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[rgba(255,255,255,0.02)] border-b border-[#ffffff14]">
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-center w-16">#</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold">Account Detail</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-right">Debits ($)</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-right">Credits ($)</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in trialBalance" :key="index" class="border-b border-[rgba(255,255,255,0.05)] hover:bg-[rgba(255,255,255,0.01)] transition">
                    <td class="px-6 py-4 text-xs text-[#6B7E8E] text-center">{{ index + 1 }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-[#F0EBE1]">{{ row.account_name }}</div>
                        <div class="text-[11px] text-[#6B7E8E] tracking-wider">{{ row.account_number }}</div>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium" :class="row.total_debit > 0 ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">
                        {{ row.total_debit > 0 ? formatCurrency(row.total_debit) : '-' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium" :class="row.total_credit > 0 ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">
                        {{ row.total_credit > 0 ? formatCurrency(row.total_credit) : '-' }}
                    </td>
                </tr>

                <tr v-if="trialBalance.length === 0">
                    <td colspan="4" class="px-6 py-20 text-center">
                        <i class="ti ti-math-symbols text-4xl text-[#6B7E8E] mb-4"></i>
                        <p class="text-[#A9B8C6]">No financial data available for generation.</p>
                    </td>
                </tr>
            </tbody>
            <tfoot v-if="trialBalance.length > 0" class="bg-[rgba(255,255,255,0.03)] font-bold">
                <tr>
                    <td colspan="2" class="px-6 py-4 text-sm text-[#F0EBE1] uppercase tracking-widest text-right">Grand Totals</td>
                    <td class="px-6 py-4 text-right text-[#F0EBE1] border-t-2 border-[#ffffff14]">{{ formatCurrency(totals.debit) }}</td>
                    <td class="px-6 py-4 text-right text-[#F0EBE1] border-t-2 border-[#ffffff14]">{{ formatCurrency(totals.credit) }}</td>
                </tr>
            </tfoot>
            </table>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
