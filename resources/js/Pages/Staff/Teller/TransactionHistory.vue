<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, reactive, computed } from 'vue';

const props = defineProps({
    history:       { type: Object, default: () => ({}) },
    filters:       { type: Object, default: () => ({}) },
    today_summary: { type: Object, default: () => ({}) },
});

/* ── Helpers ── */
const fmt  = n => '$' + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
const fmtD = d => new Date(d).toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
});

/* ── Filters ── */
const f = reactive({
    type:      props.filters.type      || '',
    status:    props.filters.status    || '',
    date_from: props.filters.date_from || '',
    date_to:   props.filters.date_to   || '',
});

function applyFilters() {
    router.get(route('staff.teller.history'), { ...f }, { preserveState: true, replace: true });
}

function clearFilters() {
    Object.assign(f, { type: '', status: '', date_from: '', date_to: '' });
    applyFilters();
}

const hasFilters = computed(() => f.type || f.status || f.date_from || f.date_to);

function setPreset(preset) {
    const now   = new Date();
    const y     = now.getFullYear();
    const mo    = now.getMonth();
    const pad   = n => String(n).padStart(2, '0');
    const today = now.toISOString().slice(0, 10);

    if (preset === 'today') {
        f.date_from = today; f.date_to = today;
    } else if (preset === 'this_week') {
        const day = now.getDay() || 7;
        const mon = new Date(now); mon.setDate(now.getDate() - day + 1);
        f.date_from = mon.toISOString().slice(0, 10); f.date_to = today;
    } else if (preset === 'this_month') {
        f.date_from = `${y}-${pad(mo + 1)}-01`; f.date_to = today;
    } else if (preset === 'last_month') {
        const lm = mo === 0 ? 11 : mo - 1;
        const ly = mo === 0 ? y - 1 : y;
        const lastDay = new Date(ly, lm + 1, 0).getDate();
        f.date_from = `${ly}-${pad(lm + 1)}-01`;
        f.date_to   = `${ly}-${pad(lm + 1)}-${lastDay}`;
    }
    applyFilters();
}

/* ── Type badge ── */
const typeMeta = {
    deposit:    { label: 'Deposit',    icon: 'ti-arrow-down-circle', cls: 'text-emerald-400 bg-emerald-900/20 border-emerald-700/30' },
    withdrawal: { label: 'Withdrawal', icon: 'ti-arrow-up-circle',   cls: 'text-amber-400   bg-amber-900/20   border-amber-700/30'   },
    transfer:   { label: 'Transfer',   icon: 'ti-arrows-right-left', cls: 'text-indigo-400  bg-indigo-900/20  border-indigo-700/30'  },
};

const statusMeta = {
    completed:        { label: 'Completed',        cls: 'text-emerald-400 bg-emerald-900/20 border-emerald-700/30' },
    pending_approval: { label: 'Pending Approval', cls: 'text-amber-400   bg-amber-900/20   border-amber-700/30'   },
    rejected:         { label: 'Rejected',         cls: 'text-red-400     bg-red-900/20     border-red-700/30'     },
    cancelled:        { label: 'Cancelled',        cls: 'text-slate-400   bg-slate-800/50   border-slate-700/40'   },
};

/* ── Reverse / Cancel ── */
const reversing    = ref(null);
const cancelling   = ref(null);
const reverseForm  = useForm({});
const cancelForm   = useForm({});

const submitReverse = () => {
    reverseForm.post(route('staff.teller.reverse', reversing.value.id), {
        onSuccess: () => { reversing.value = null; },
        onError:   () => { reversing.value = null; },
    });
};

const submitCancel = () => {
    cancelForm.post(route('staff.teller.cancel', cancelling.value.id), {
        onSuccess: () => { cancelling.value = null; },
        onError:   () => { cancelling.value = null; },
    });
};

/* ── Today summary totals ── */
const todayDeposits    = computed(() => props.today_summary?.deposit    ?? { count: 0, total: 0 });
const todayWithdrawals = computed(() => props.today_summary?.withdrawal ?? { count: 0, total: 0 });
const todayTransfers   = computed(() => props.today_summary?.transfer   ?? { count: 0, total: 0 });
</script>

<template>
    <Head title="Transaction History" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-7 space-y-6">

            <!-- ── Header ── -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[rgba(99,179,237,0.1)] border border-[rgba(99,179,237,0.2)] flex items-center justify-center">
                        <i class="ti ti-history text-[#63B3ED] text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Transaction History</h1>
                        <p class="text-xs text-slate-400 mt-0.5">All transactions you have processed</p>
                    </div>
                </div>
                <span class="text-xs text-slate-500 bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-lg">
                    {{ history.total ?? 0 }} total records
                </span>
            </div>

            <!-- ── Today's Summary Cards ── -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="ti ti-arrow-down-circle text-emerald-400 text-sm"></i>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wide font-bold">Today's Deposits</p>
                    </div>
                    <p class="text-base font-bold text-emerald-400 font-mono">{{ fmt(todayDeposits.total) }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">{{ todayDeposits.count }} transaction{{ todayDeposits.count !== 1 ? 's' : '' }}</p>
                </div>
                <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="ti ti-arrow-up-circle text-amber-400 text-sm"></i>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wide font-bold">Today's Withdrawals</p>
                    </div>
                    <p class="text-base font-bold text-amber-400 font-mono">{{ fmt(todayWithdrawals.total) }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">{{ todayWithdrawals.count }} transaction{{ todayWithdrawals.count !== 1 ? 's' : '' }}</p>
                </div>
                <div class="bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="ti ti-arrows-right-left text-indigo-400 text-sm"></i>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wide font-bold">Today's Transfers</p>
                    </div>
                    <p class="text-base font-bold text-indigo-400 font-mono">{{ fmt(todayTransfers.total) }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">{{ todayTransfers.count }} transaction{{ todayTransfers.count !== 1 ? 's' : '' }}</p>
                </div>
            </div>

            <!-- ── Filters ── -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide flex items-center gap-2">
                        <i class="ti ti-filter text-[#63B3ED]"></i> Filters
                    </p>
                    <button v-if="hasFilters" @click="clearFilters"
                            class="flex items-center gap-1 text-xs text-slate-400 hover:text-white transition">
                        <i class="ti ti-x text-xs"></i> Clear
                    </button>
                </div>

                <!-- Date presets -->
                <div class="flex flex-wrap gap-2">
                    <button v-for="p in [
                        { key: 'today',      label: 'Today'      },
                        { key: 'this_week',  label: 'This Week'  },
                        { key: 'this_month', label: 'This Month' },
                        { key: 'last_month', label: 'Last Month' },
                    ]" :key="p.key" @click="setPreset(p.key)"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 border border-slate-700
                                   text-slate-300 hover:bg-[#63B3ED]/10 hover:border-[#63B3ED]/30 hover:text-[#63B3ED] transition-colors">
                        {{ p.label }}
                    </button>
                </div>

                <!-- Filter row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">Type</label>
                        <select v-model="f.type" @change="applyFilters"
                                class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                       focus:outline-none focus:ring-2 focus:ring-[#63B3ED]/40 focus:border-[#63B3ED]/50 transition-colors">
                            <option value="">All Types</option>
                            <option value="deposit">Deposit</option>
                            <option value="withdrawal">Withdrawal</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">Status</label>
                        <select v-model="f.status" @change="applyFilters"
                                class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                       focus:outline-none focus:ring-2 focus:ring-[#63B3ED]/40 focus:border-[#63B3ED]/50 transition-colors">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">From Date</label>
                        <input v-model="f.date_from" type="date" @change="applyFilters"
                               class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                      focus:outline-none focus:ring-2 focus:ring-[#63B3ED]/40 focus:border-[#63B3ED]/50 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase text-slate-500 font-bold tracking-widest mb-1.5">To Date</label>
                        <input v-model="f.date_to" type="date" @change="applyFilters"
                               class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                      focus:outline-none focus:ring-2 focus:ring-[#63B3ED]/40 focus:border-[#63B3ED]/50 transition-colors" />
                    </div>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[700px]">
                        <thead class="bg-slate-800/60">
                            <tr class="text-[10px] text-slate-400 uppercase tracking-wide">
                                <th class="px-5 py-3">Date & Time</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Customer / Account</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr v-for="txn in history.data" :key="txn.id"
                                class="hover:bg-slate-800/40 transition-colors"
                                :class="{ 'opacity-50': txn.is_reversed }">

                                <!-- Date -->
                                <td class="px-5 py-3.5 text-xs text-slate-400 whitespace-nowrap">
                                    {{ fmtD(txn.created_at) }}
                                </td>

                                <!-- Type badge -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                          :class="typeMeta[txn.type]?.cls ?? 'text-slate-400 bg-slate-800 border-slate-700'">
                                        <i class="ti" :class="typeMeta[txn.type]?.icon"></i>
                                        {{ typeMeta[txn.type]?.label ?? txn.type }}
                                    </span>
                                </td>

                                <!-- Reference -->
                                <td class="px-4 py-3.5 font-mono text-xs text-slate-300 whitespace-nowrap">
                                    {{ txn.reference }}
                                    <span v-if="txn.is_reversed"
                                          class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-900/30 text-red-400 border border-red-700/30">
                                        REVERSED
                                    </span>
                                    <span v-else-if="txn.reversal_of"
                                          class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-900/30 text-indigo-400 border border-indigo-700/30">
                                        REVERSAL
                                    </span>
                                </td>

                                <!-- Customer / Account -->
                                <td class="px-4 py-3.5">
                                    <template v-if="txn.type === 'transfer'">
                                        <p class="text-sm text-white font-medium truncate max-w-[160px]">{{ txn.customer_name }}</p>
                                        <p class="text-[10px] text-slate-500 font-mono mt-0.5">
                                            {{ txn.account_number }}
                                            <span class="text-slate-600 mx-1">→</span>
                                            {{ txn.to_account_number }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 truncate max-w-[160px]">to {{ txn.to_customer }}</p>
                                    </template>
                                    <template v-else>
                                        <p class="text-sm text-white font-medium truncate max-w-[160px]">{{ txn.customer_name }}</p>
                                        <p class="text-[10px] text-slate-500 font-mono mt-0.5">{{ txn.account_number }}</p>
                                    </template>
                                </td>

                                <!-- Amount -->
                                <td class="px-4 py-3.5 text-right font-mono font-semibold text-white whitespace-nowrap">
                                    {{ fmt(txn.amount) }}
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                          :class="statusMeta[txn.status]?.cls ?? 'text-slate-400 bg-slate-800 border-slate-700'">
                                        {{ statusMeta[txn.status]?.label ?? txn.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Receipt PDF -->
                                        <a :href="route('staff.transaction.receipt', txn.id)"
                                           target="_blank" rel="noopener"
                                           title="Print Receipt"
                                           class="w-7 h-7 rounded-lg bg-white/5 border border-white/10 text-slate-400
                                                  hover:text-[#63B3ED] hover:border-[#63B3ED]/30 hover:bg-[#63B3ED]/8
                                                  flex items-center justify-center transition-colors">
                                            <i class="ti ti-receipt text-xs"></i>
                                        </a>

                                        <!-- Reverse -->
                                        <button v-if="txn.can_reverse"
                                                @click="reversing = txn"
                                                title="Reverse Transaction"
                                                class="w-7 h-7 rounded-lg bg-red-900/20 border border-red-700/30 text-red-400
                                                       hover:bg-red-900/40 flex items-center justify-center transition-colors">
                                            <i class="ti ti-rotate text-xs"></i>
                                        </button>

                                        <!-- Cancel pending -->
                                        <button v-if="txn.status === 'pending_approval'"
                                                @click="cancelling = txn"
                                                title="Cancel Request"
                                                class="w-7 h-7 rounded-lg bg-amber-900/20 border border-amber-700/30 text-amber-400
                                                       hover:bg-amber-900/40 flex items-center justify-center transition-colors">
                                            <i class="ti ti-x text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!history.data?.length">
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <i class="ti ti-history text-4xl text-slate-600 block mb-3"></i>
                                    <p class="text-slate-400 font-medium">No transactions found</p>
                                    <p class="text-slate-600 text-sm mt-1">Try adjusting your filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="history.last_page > 1"
                     class="px-5 py-4 border-t border-slate-800 flex items-center justify-between bg-slate-800/20">
                    <p class="text-xs text-slate-500">
                        Showing {{ history.from ?? 0 }}–{{ history.to ?? 0 }} of {{ history.total }}
                    </p>
                    <div class="flex gap-1.5">
                        <Link v-for="link in history.links" :key="link.label"
                              :href="link.url || '#'"
                              v-html="link.label"
                              class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs transition"
                              :class="link.active
                                  ? 'bg-[#63B3ED] border-[#63B3ED] text-[#070F1A] font-bold'
                                  : link.url
                                      ? 'border-slate-700 text-slate-400 hover:bg-slate-800'
                                      : 'border-transparent text-slate-600 cursor-default'" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════ Reverse Confirmation Modal ══════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="reversing"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                    <div class="bg-[#112236] border border-red-700/30 rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-red-900/30 flex items-center justify-center">
                                <i class="ti ti-rotate text-red-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Reverse Transaction</h3>
                                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ reversing.reference }}</p>
                            </div>
                        </div>
                        <div class="bg-red-900/15 border border-red-700/30 rounded-xl px-4 py-3 text-xs text-red-300 leading-relaxed">
                            This will create a counter-entry to undo this {{ reversing.type }} of
                            <strong>{{ fmt(reversing.amount) }}</strong>. The original record will be marked as reversed.
                        </div>
                        <div class="flex gap-3">
                            <button @click="reversing = null"
                                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition">
                                Cancel
                            </button>
                            <button @click="submitReverse" :disabled="reverseForm.processing"
                                    class="flex-1 bg-red-700 hover:bg-red-600 disabled:opacity-40 text-white text-sm font-semibold py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                                <i :class="reverseForm.processing ? 'ti ti-loader animate-spin' : 'ti ti-rotate'"></i>
                                {{ reverseForm.processing ? 'Reversing…' : 'Confirm Reverse' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ══════════ Cancel Confirmation Modal ══════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="cancelling"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                    <div class="bg-[#112236] border border-amber-700/30 rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-900/30 flex items-center justify-center">
                                <i class="ti ti-x text-amber-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Cancel Request</h3>
                                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ cancelling.reference }}</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            This will cancel the pending approval request for
                            <strong>{{ fmt(cancelling.amount) }}</strong>.
                            The supervisor will no longer see it in their queue.
                        </p>
                        <div class="flex gap-3">
                            <button @click="cancelling = null"
                                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition">
                                Keep It
                            </button>
                            <button @click="submitCancel" :disabled="cancelForm.processing"
                                    class="flex-1 bg-amber-600 hover:bg-amber-500 disabled:opacity-40 text-white text-sm font-semibold py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                                <i :class="cancelForm.processing ? 'ti ti-loader animate-spin' : 'ti ti-x'"></i>
                                {{ cancelForm.processing ? 'Cancelling…' : 'Cancel Request' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .18s ease, transform .18s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; transform: scale(.96); }
</style>
