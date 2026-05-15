<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue';

const page = usePage();
const canWrite = () => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.role_id === 1 || user.role?.role_name === 'Super Admin') return true;
    return (user.permissions ?? []).includes('customer.write');
};

const props = defineProps({
    customers: Object,
    filters: Object,
});

const search     = ref(props.filters?.search ?? '');
const kycStatus  = ref(props.filters?.kyc_status ?? '');

let debounce;
watch([search, kycStatus], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('staff.customer-care.customers'), {
            search:     search.value,
            kyc_status: kycStatus.value,
        }, { preserveState: true, replace: true });
    }, 400);
});

const kycBadge = (status) => {
    const map = {
        pending:      { label: 'Pending',      cls: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' },
        under_review: { label: 'Under Review', cls: 'bg-blue-500/10 text-blue-400 border-blue-500/20' },
        verified:     { label: 'Verified',     cls: 'bg-green-500/10 text-green-400 border-green-500/20' },
        rejected:     { label: 'Rejected',     cls: 'bg-red-500/10 text-red-400 border-red-500/20' },
    };
    return map[status] ?? { label: status, cls: 'bg-gray-500/10 text-gray-400 border-gray-500/20' };
};
</script>

<template>
  <Head title="Customers" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-xl text-[#F0EBE1]">Customer Directory</h1>
          <p class="text-[12px] text-[#A9B8C6] mt-0.5">Search, view profiles, and manage customer accounts</p>
        </div>
        <Link v-if="canWrite()" :href="route('staff.customer-care.register')"
              class="flex items-center gap-2 bg-[#C9A84C] hover:bg-[#E5C97E] text-[#0B1929] text-sm font-semibold px-4 py-2 rounded-lg transition">
          <i class="ti ti-user-plus"></i> New Customer
        </Link>
      </div>
    </template>

    <div class="p-6 md:p-8 max-w-7xl mx-auto">

      <!-- Filters -->
      <div class="flex flex-col md:flex-row gap-3 mb-6">
        <div class="relative flex-1">
          <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-[#6B7E8E]"></i>
          <input v-model="search" type="text" placeholder="Search by name, phone, ID number or email…"
                 class="w-full pl-9 pr-4 py-2.5 text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg outline-none focus:border-[#C9A84C] transition" />
        </div>
        <select v-model="kycStatus" class="select-arrow px-4 py-2.5 text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg outline-none focus:border-[#C9A84C] transition appearance-none min-w-[160px]">
          <option value="">All KYC Status</option>
          <option value="pending">Pending</option>
          <option value="under_review">Under Review</option>
          <option value="verified">Verified</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-[rgba(255,255,255,0.02)] border border-[rgba(255,255,255,0.07)] rounded-xl overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="bg-[#112236] border-b border-[rgba(255,255,255,0.07)]">
              <th class="px-5 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C] font-semibold">Customer</th>
              <th class="px-5 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C] font-semibold hidden md:table-cell">Phone</th>
              <th class="px-5 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C] font-semibold hidden lg:table-cell">ID Number</th>
              <th class="px-5 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C] font-semibold">KYC</th>
              <th class="px-5 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C] font-semibold hidden md:table-cell">Accounts</th>
              <th class="px-5 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C] font-semibold hidden lg:table-cell">Registered</th>
              <th class="px-5 py-3 text-right text-[10px] uppercase tracking-widest text-[#C9A84C] font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="customers.data.length === 0">
              <td colspan="7" class="px-5 py-14 text-center text-[#6B7E8E] text-sm">
                <i class="ti ti-users-off text-3xl block mb-2"></i>
                No customers found
              </td>
            </tr>
            <tr v-for="c in customers.data" :key="c.id"
                class="border-b border-[rgba(255,255,255,0.05)] hover:bg-[rgba(255,255,255,0.02)] transition">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-[rgba(201,168,76,0.12)] flex items-center justify-center text-[#C9A84C] text-sm font-bold shrink-0">
                    {{ c.full_name?.charAt(0) ?? '?' }}
                  </div>
                  <div>
                    <div class="text-sm font-medium text-[#F0EBE1]">{{ c.full_name }}</div>
                    <div class="text-[11px] text-[#6B7E8E]">{{ c.email ?? '—' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-5 py-3.5 text-sm text-[#A9B8C6] hidden md:table-cell">{{ c.phone }}</td>
              <td class="px-5 py-3.5 text-sm font-mono text-[#A9B8C6] hidden lg:table-cell">{{ c.id_number }}</td>
              <td class="px-5 py-3.5">
                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                      :class="kycBadge(c.kyc_status).cls">
                  {{ kycBadge(c.kyc_status).label }}
                </span>
              </td>
              <td class="px-5 py-3.5 hidden md:table-cell">
                <span class="text-sm text-[#A9B8C6]">{{ c.accounts?.length ?? 0 }} account{{ (c.accounts?.length ?? 0) !== 1 ? 's' : '' }}</span>
              </td>
              <td class="px-5 py-3.5 text-[12px] text-[#6B7E8E] hidden lg:table-cell">
                {{ c.created_at ? new Date(c.created_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '—' }}
              </td>
              <td class="px-5 py-3.5">
                <div class="flex items-center justify-end gap-2">
                  <Link :href="route('staff.customer-care.profile', c.id)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium bg-[rgba(201,168,76,0.08)] text-[#C9A84C] border border-[rgba(201,168,76,0.2)] hover:bg-[rgba(201,168,76,0.16)] transition">
                    <i class="ti ti-user"></i> Profile
                  </Link>
                  <Link v-if="c.kyc_status === 'pending' || c.kyc_status === 'under_review'"
                        :href="route('staff.customer-care.kyc', c.id)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium bg-[rgba(255,255,255,0.04)] text-[#A9B8C6] border border-[rgba(255,255,255,0.1)] hover:text-[#F0EBE1] transition">
                    <i class="ti ti-file-upload"></i> KYC
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="customers.last_page > 1" class="flex items-center justify-between mt-5">
        <span class="text-[12px] text-[#6B7E8E]">
          Showing {{ customers.from }}–{{ customers.to }} of {{ customers.total }} customers
        </span>
        <div class="flex gap-1">
          <Link v-for="link in customers.links" :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                class="px-3 py-1.5 rounded text-[12px] transition"
                :class="link.active
                  ? 'bg-[#C9A84C] text-[#0B1929] font-bold'
                  : link.url
                    ? 'text-[#A9B8C6] hover:text-[#F0EBE1] bg-[rgba(255,255,255,0.04)]'
                    : 'text-[#4a5f70] cursor-default'" />
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.select-arrow {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7E8E' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
}
select option {
    background: #112236;
    color: #F0EBE1;
}
</style>
