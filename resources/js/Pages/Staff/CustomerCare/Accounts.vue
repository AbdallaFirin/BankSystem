<script setup>
import { ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    accounts: Object, // Paginated
});

const page = usePage();

const can = (key) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.role_id === 1 || user.role?.role_name === 'Super Admin') return true;
    return (user.permissions ?? []).includes(key);
};

const statusClass = (status) => {
    const map = {
        active:   'bg-green-500/10 text-green-400 border-green-500/20',
        inactive: 'bg-red-500/10 text-red-400 border-red-500/20',
        dormant:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        frozen:   'bg-blue-500/10 text-blue-400 border-blue-500/20',
    };
    return map[status] ?? 'bg-gray-500/10 text-gray-400 border-gray-500/20';
};

/* ── Statement date-range modal ── */
const showStmtModal  = ref(false)
const stmtAccountId  = ref(null)
const stmtFrom       = ref('')
const stmtTo         = ref('')

function openStmtModal(accountId) {
    stmtAccountId.value = accountId
    // default to current month
    const now   = new Date()
    const y     = now.getFullYear()
    const m     = String(now.getMonth() + 1).padStart(2, '0')
    stmtFrom.value = `${y}-${m}-01`
    stmtTo.value   = now.toISOString().slice(0, 10)
    showStmtModal.value = true
}

function applyPreset(preset) {
    const now = new Date()
    const y   = now.getFullYear()
    const m   = now.getMonth()

    const pad = n => String(n).padStart(2, '0')
    const lastDay = (yr, mo) => new Date(yr, mo + 1, 0).getDate()

    if (preset === 'this_month') {
        stmtFrom.value = `${y}-${pad(m + 1)}-01`
        stmtTo.value   = now.toISOString().slice(0, 10)
    } else if (preset === 'last_month') {
        const lm = m === 0 ? 11 : m - 1
        const ly = m === 0 ? y - 1 : y
        stmtFrom.value = `${ly}-${pad(lm + 1)}-01`
        stmtTo.value   = `${ly}-${pad(lm + 1)}-${lastDay(ly, lm)}`
    } else if (preset === 'last_3') {
        const d = new Date(y, m - 2, 1)
        stmtFrom.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`
        stmtTo.value   = now.toISOString().slice(0, 10)
    } else if (preset === 'last_6') {
        const d = new Date(y, m - 5, 1)
        stmtFrom.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`
        stmtTo.value   = now.toISOString().slice(0, 10)
    } else if (preset === 'this_year') {
        stmtFrom.value = `${y}-01-01`
        stmtTo.value   = now.toISOString().slice(0, 10)
    } else if (preset === 'all') {
        stmtFrom.value = '2000-01-01'
        stmtTo.value   = now.toISOString().slice(0, 10)
    }
}

function downloadStatement() {
    if (!stmtAccountId.value) return
    const url = route('staff.account.statement', {
        id:        stmtAccountId.value,
        date_from: stmtFrom.value,
        date_to:   stmtTo.value,
    })
    window.open(url, '_blank')
    showStmtModal.value = false
}
</script>

<template>
    <Head title="Bank Accounts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.2)] flex items-center justify-center text-xl text-[#C9A84C]">
                        <i class="ti ti-building-bank"></i>
                    </div>
                    <div>
                        <h1 class="font-serif text-xl text-[#F0EBE1]">Bank <em class="text-[#C9A84C] not-italic">Accounts</em></h1>
                        <p class="text-[11px] text-[#A9B8C6] tracking-widest uppercase mt-0.5">Customer Account Registry</p>
                    </div>
                </div>
                <!-- Only visible to roles with account.write -->
                <Link v-if="can('account.write')"
                      :href="route('staff.customer-care.accounts.create')"
                      class="flex items-center gap-2 bg-[#C9A84C] hover:bg-[#E5C97E] text-[#0B1929] text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <i class="ti ti-plus"></i> Open New Account
                </Link>
            </div>
        </template>

        <div class="p-6 md:p-8 max-w-7xl mx-auto">

            <!-- Table -->
            <div class="bg-[#112236] border border-[#ffffff14] rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-[#ffffff08]">
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold">Account Number</th>
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold">Customer</th>
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold hidden md:table-cell">Product</th>
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold hidden lg:table-cell">Opening Branch</th>
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold text-right">Balance</th>
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold hidden md:table-cell">Opened</th>
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold text-center">Status</th>
                                <th class="px-5 py-4 text-[10px] uppercase tracking-widest text-[#C9A84C] font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#ffffff05]">
                            <tr v-if="accounts.data.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center text-[#6B7E8E]">
                                    <i class="ti ti-wallet text-4xl block mb-3 opacity-30"></i>
                                    <p class="text-sm">No accounts found.</p>
                                </td>
                            </tr>
                            <tr v-for="account in accounts.data" :key="account.id"
                                class="hover:bg-white/[0.02] transition">

                                <!-- Account Number -->
                                <td class="px-5 py-4">
                                    <span class="font-mono text-[#E8A830] font-bold tracking-wider text-sm">
                                        {{ account.account_number }}
                                    </span>
                                </td>

                                <!-- Customer -->
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-[#F0EBE1]">{{ account.customer?.full_name }}</p>
                                    <p class="text-[11px] text-[#6B7E8E]">{{ account.customer?.phone }}</p>
                                </td>

                                <!-- Product -->
                                <td class="px-5 py-4 text-sm text-[#A9B8C6] hidden md:table-cell">
                                    {{ account.account_type?.type_name ?? '—' }}
                                </td>

                                <!-- Branch -->
                                <td class="px-5 py-4 text-sm text-[#A9B8C6] hidden lg:table-cell">
                                    {{ account.home_branch?.branch_name ?? '—' }}
                                </td>

                                <!-- Balance -->
                                <td class="px-5 py-4 text-right">
                                    <span class="font-mono text-sm font-bold text-[#F0EBE1]">
                                        ${{ Number(account.balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </span>
                                </td>

                                <!-- Opened -->
                                <td class="px-5 py-4 text-[12px] text-[#6B7E8E] hidden md:table-cell">
                                    {{ account.opened_at
                                        ? new Date(account.opened_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })
                                        : '—' }}
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border"
                                          :class="statusClass(account.status)">
                                        {{ account.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Transaction History -->
                                        <Link :href="route('staff.account.transactions', account.id)"
                                              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium bg-[rgba(201,168,76,0.08)] text-[#C9A84C] border border-[rgba(201,168,76,0.2)] hover:bg-[rgba(201,168,76,0.16)] transition"
                                              title="View transaction history">
                                            <i class="ti ti-history"></i>
                                            <span class="hidden sm:inline">History</span>
                                        </Link>
                                        <!-- Statement PDF -->
                                        <button @click="openStmtModal(account.id)"
                                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium bg-[rgba(255,255,255,0.04)] text-[#A9B8C6] border border-[rgba(255,255,255,0.1)] hover:text-[#F0EBE1] hover:bg-[rgba(255,255,255,0.08)] transition"
                                                title="Download PDF statement">
                                            <i class="ti ti-file-type-pdf"></i>
                                            <span class="hidden sm:inline">Statement</span>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="accounts.last_page > 1"
                     class="px-5 py-4 border-t border-[#ffffff08] flex items-center justify-between bg-white/[0.01]">
                    <p class="text-xs text-[#6B7E8E]">
                        Showing {{ accounts.from }}–{{ accounts.to }} of {{ accounts.total }} accounts
                    </p>
                    <div class="flex gap-1.5">
                        <Link v-for="link in accounts.links" :key="link.label"
                              :href="link.url || '#'"
                              v-html="link.label"
                              class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs transition"
                              :class="link.active
                                  ? 'bg-[#C9A84C] border-[#C9A84C] text-[#0B1929] font-bold'
                                  : link.url
                                      ? 'border-[#ffffff10] text-[#A9B8C6] hover:bg-white/5'
                                      : 'border-transparent text-[#4a5f70] cursor-default'" />
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>

    <!-- ══════════ Statement Date-Range Modal ══════════ -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showStmtModal"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="bg-[#112236] border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md">

                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-700/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#C9A84C]/10 flex items-center justify-center">
                                <i class="ti ti-file-type-pdf text-[#C9A84C] text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white text-sm">Generate Statement</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Select the period for this statement</p>
                            </div>
                        </div>
                        <button @click="showStmtModal = false" class="text-slate-400 hover:text-white transition-colors">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <!-- Quick presets -->
                        <div>
                            <p class="text-xs font-medium text-slate-400 mb-2 uppercase tracking-wide">Quick Select</p>
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="p in [
                                    { key:'this_month', label:'This Month' },
                                    { key:'last_month', label:'Last Month' },
                                    { key:'last_3',     label:'Last 3 Months' },
                                    { key:'last_6',     label:'Last 6 Months' },
                                    { key:'this_year',  label:'This Year' },
                                    { key:'all',        label:'All Time' },
                                ]" :key="p.key"
                                        @click="applyPreset(p.key)"
                                        class="px-2 py-2 rounded-lg text-xs font-medium bg-slate-800 border border-slate-700 text-slate-300
                                               hover:bg-[#C9A84C]/10 hover:border-[#C9A84C]/40 hover:text-[#C9A84C] transition-colors">
                                    {{ p.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Custom range -->
                        <div>
                            <p class="text-xs font-medium text-slate-400 mb-2 uppercase tracking-wide">Custom Range</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">From</label>
                                    <input v-model="stmtFrom" type="date"
                                           class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                                  focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">To</label>
                                    <input v-model="stmtTo" type="date"
                                           class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                                  focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors" />
                                </div>
                            </div>
                        </div>

                        <!-- Selected range preview -->
                        <div v-if="stmtFrom && stmtTo"
                             class="bg-slate-800/60 border border-slate-700/40 rounded-xl px-4 py-3 flex items-center gap-3">
                            <i class="ti ti-calendar-event text-[#C9A84C]"></i>
                            <p class="text-xs text-slate-300">
                                <span class="font-semibold text-white">{{ new Date(stmtFrom + 'T00:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) }}</span>
                                <span class="text-slate-500 mx-2">→</span>
                                <span class="font-semibold text-white">{{ new Date(stmtTo + 'T00:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) }}</span>
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-1">
                            <button @click="showStmtModal = false"
                                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button @click="downloadStatement"
                                    :disabled="!stmtFrom || !stmtTo"
                                    class="flex-1 bg-[#C9A84C] hover:bg-[#b8973e] disabled:opacity-40 text-[#070F1A] text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                                <i class="ti ti-download"></i> Download PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s, transform .2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(.95); }
</style>
