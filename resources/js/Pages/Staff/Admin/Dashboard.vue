<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    metrics: { type: Object, required: true },
    branches: { type: Array, default: () => [] }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
  <Head title="Global HQ Dashboard" />

  <AuthenticatedLayout>
    <div class="shell max-w-7xl mx-auto p-4 md:p-8">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[rgba(201,168,76,0.1)] flex items-center justify-center text-2xl text-[#C9A84C]">
            <i class="ti ti-chart-dots"></i>
          </div>
          <div>
            <h1 class="font-serif text-2xl text-[#F0EBE1]">Global <em class="text-[#C9A84C] not-italic">Analytics</em></h1>
            <p class="text-[12px] text-[#A9B8C6] tracking-wide uppercase mt-1">Cross-Branch Oversight & Management</p>
          </div>
        </div>
        <div class="flex gap-2">
            <Link :href="route('staff.admin.staff.index')" class="bg-[rgba(255,255,255,0.05)] border border-[#ffffff14] text-[#F0EBE1] px-4 py-2 rounded-lg text-sm font-medium hover:bg-[rgba(255,255,255,0.1)] transition flex items-center gap-2">
                <i class="ti ti-users"></i> Staff Management
            </Link>
        </div>
      </div>

      <!-- Metrics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
          <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6">
              <span class="text-[10px] uppercase text-[#6B7E8E] font-bold">Total Assets</span>
              <h2 class="text-3xl font-serif text-[#C9A84C] mt-2">{{ formatCurrency(metrics.total_assets) }}</h2>
              <div class="mt-4 h-1 bg-[rgba(201,168,76,0.1)] rounded-full overflow-hidden">
                  <div class="h-full bg-[#C9A84C] w-full"></div>
              </div>
          </div>
          <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6">
              <span class="text-[10px] uppercase text-[#6B7E8E] font-bold">Total Staff</span>
              <h2 class="text-3xl font-serif text-[#F0EBE1] mt-2">{{ metrics.staff }}</h2>
              <p class="text-[11px] text-[#A9B8C6] mt-2">Active across all regions</p>
          </div>
          <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6">
              <span class="text-[10px] uppercase text-[#6B7E8E] font-bold">Active Customers</span>
              <h2 class="text-3xl font-serif text-[#F0EBE1] mt-2">{{ metrics.customers }}</h2>
              <p class="text-[11px] text-[#A9B8C6] mt-2">Verified base</p>
          </div>
          <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6">
              <span class="text-[10px] uppercase text-[#6B7E8E] font-bold">Pending Approvals</span>
              <h2 class="text-3xl font-serif text-[#E2635A] mt-2">{{ metrics.pending_approvals }}</h2>
              <p class="text-[11px] text-[#A9B8C6] mt-2">Awaiting branch action</p>
          </div>
      </div>

            <!-- HQ Operations Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <Link :href="route('staff.admin.branches.index')" class="bg-[#112236] border border-[#ffffff14] p-6 rounded-2xl hover:border-[#C9A84C] transition group">
                    <div class="w-12 h-12 rounded-xl bg-[rgba(201,168,76,0.1)] flex items-center justify-center text-2xl text-[#C9A84C] mb-4">
                        <i class="ti ti-building-bank"></i>
                    </div>
                    <h3 class="text-[#F0EBE1] font-medium group-hover:text-[#C9A84C] transition">Manage Branches</h3>
                    <p class="text-[11px] text-[#A9B8C6] mt-1">CRUD & Performance monitoring</p>
                </Link>

                <Link :href="route('staff.admin.roles.index')" class="bg-[#112236] border border-[#ffffff14] p-6 rounded-2xl hover:border-[#E8A830] transition group">
                    <div class="w-12 h-12 rounded-xl bg-[rgba(232,168,48,0.1)] flex items-center justify-center text-2xl text-[#E8A830] mb-4">
                        <i class="ti ti-shield-lock"></i>
                    </div>
                    <h3 class="text-[#F0EBE1] font-medium group-hover:text-[#E8A830] transition">Roles & RBAC</h3>
                    <p class="text-[11px] text-[#A9B8C6] mt-1">Live permission management</p>
                </Link>

                <Link :href="route('staff.admin.audit.index')" class="bg-[#112236] border border-[#ffffff14] p-6 rounded-2xl hover:border-[#6B7E8E] transition group">
                    <div class="w-12 h-12 rounded-xl bg-[rgba(107,126,142,0.1)] flex items-center justify-center text-2xl text-[#6B7E8E] mb-4">
                        <i class="ti ti-history"></i>
                    </div>
                    <h3 class="text-[#F0EBE1] font-medium group-hover:text-[#6B7E8E] transition">Audit Trail</h3>
                    <p class="text-[11px] text-[#A9B8C6] mt-1">Cross-branch staff activity</p>
                </Link>

                <Link :href="route('staff.admin.settings.index')" class="bg-[#112236] border border-[#ffffff14] p-6 rounded-2xl hover:border-[#C9A84C] transition group">
                    <div class="w-12 h-12 rounded-xl bg-[rgba(201,168,76,0.1)] flex items-center justify-center text-2xl text-[#C9A84C] mb-4">
                        <i class="ti ti-settings-2"></i>
                    </div>
                    <h3 class="text-[#F0EBE1] font-medium group-hover:text-[#C9A84C] transition">System Settings</h3>
                    <p class="text-[11px] text-[#A9B8C6] mt-1">Rates, products & limits</p>
                </Link>
            </div>

            <!-- Branch Performance (Consolidated) -->
            <div class="bg-[#112236] border border-[#ffffff14] rounded-3xl overflow-hidden shadow-2xl">
        <div class="absolute top-0 left-0 w-full h-1 bg-[#C9A84C]"></div>
        
        <div class="p-6 border-b border-[#ffffff08]">
            <h2 class="text-sm font-medium text-[#F0EBE1] uppercase tracking-widest">Structural Branch Analysis</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[rgba(255,255,255,0.02)] border-b border-[#ffffff14]">
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold">Branch Name</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold">City</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-center">Staff Count</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-center">Account Count</th>
                    <th class="px-6 py-4 text-[11px] uppercase tracking-widest text-[#A9B8C6] font-semibold text-right">Liquidity Focus</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="branch in branches" :key="branch.id" class="border-b border-[rgba(255,255,255,0.05)] hover:bg-[rgba(255,255,255,0.01)] transition">
                    <td class="px-6 py-4 text-sm font-medium text-[#F0EBE1]">{{ branch.branch_name }}</td>
                    <td class="px-6 py-4 text-sm text-[#A9B8C6]">{{ branch.city }}</td>
                    <td class="px-6 py-4 text-sm text-center text-[#F0EBE1] font-medium">{{ branch.staff_count }}</td>
                    <td class="px-6 py-4 text-sm text-center text-[#F0EBE1] font-medium">{{ branch.accounts_count }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2 text-xs text-[#4CAF7D]">
                            <i class="ti ti-chart-bar"></i> Stable
                        </div>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
