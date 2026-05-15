<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customer: Object
});

const kycColor = {
    pending:      'bg-[rgba(232,168,48,0.1)] text-[#E8A830] border-[rgba(232,168,48,0.2)]',
    under_review: 'bg-[rgba(201,168,76,0.1)] text-[#C9A84C] border-[rgba(201,168,76,0.2)]',
    verified:     'bg-[rgba(76,175,125,0.1)] text-[#4CAF7D] border-[rgba(76,175,125,0.2)]',
    rejected:     'bg-[rgba(226,99,90,0.1)] text-[#E2635A] border-[rgba(226,99,90,0.2)]',
};

const statusColor = {
    active:    'bg-[rgba(76,175,125,0.1)] text-[#4CAF7D] border-[rgba(76,175,125,0.2)]',
    inactive:  'bg-[rgba(107,126,142,0.1)] text-[#6B7E8E] border-[rgba(107,126,142,0.2)]',
    suspended: 'bg-[rgba(232,168,48,0.1)] text-[#E8A830] border-[rgba(232,168,48,0.2)]',
    closed:    'bg-[rgba(226,99,90,0.1)] text-[#E2635A] border-[rgba(226,99,90,0.2)]',
};

const fmt = (v) => v || '—';
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const fmtMoney = (n) => n !== undefined ? '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '$0.00';

/* ── Statement date-range modal ── */
const showStmtModal = ref(false)
const stmtAccountId = ref(null)
const stmtFrom      = ref('')
const stmtTo        = ref('')

function openStmtModal(accountId) {
    stmtAccountId.value = accountId
    const now = new Date()
    const y   = now.getFullYear()
    const m   = String(now.getMonth() + 1).padStart(2, '0')
    stmtFrom.value = `${y}-${m}-01`
    stmtTo.value   = now.toISOString().slice(0, 10)
    showStmtModal.value = true
}

function applyPreset(preset) {
    const now = new Date()
    const y   = now.getMonth()
    const yr  = now.getFullYear()
    const mo  = now.getMonth()
    const pad = n => String(n).padStart(2, '0')
    const lastDay = (yr2, mo2) => new Date(yr2, mo2 + 1, 0).getDate()
    const today = now.toISOString().slice(0, 10)

    if (preset === 'this_month') {
        stmtFrom.value = `${yr}-${pad(mo + 1)}-01`
        stmtTo.value   = today
    } else if (preset === 'last_month') {
        const lm = mo === 0 ? 11 : mo - 1
        const ly = mo === 0 ? yr - 1 : yr
        stmtFrom.value = `${ly}-${pad(lm + 1)}-01`
        stmtTo.value   = `${ly}-${pad(lm + 1)}-${lastDay(ly, lm)}`
    } else if (preset === 'last_3') {
        const d = new Date(yr, mo - 2, 1)
        stmtFrom.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`
        stmtTo.value   = today
    } else if (preset === 'last_6') {
        const d = new Date(yr, mo - 5, 1)
        stmtFrom.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`
        stmtTo.value   = today
    } else if (preset === 'this_year') {
        stmtFrom.value = `${yr}-01-01`
        stmtTo.value   = today
    } else if (preset === 'all') {
        stmtFrom.value = '2000-01-01'
        stmtTo.value   = today
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

/* ══════════════════════════════════════════
   Quick Transaction Modal
   ══════════════════════════════════════════ */
const txModal     = ref(false)
const txType      = ref('')        // 'deposit' | 'withdraw' | 'transfer'
const txAccountId = ref(null)
const txAccountNo = ref('')
const txBalance   = ref(0)

const txForm = useForm({
    amount:     '',
    to_account: '',
    note:       '',
})

// Receiver account live-lookup
const lookupResult  = ref(null)   // { found, customer_name, status }
const lookupLoading = ref(false)

async function lookupReceiver() {
    if (!txForm.to_account || txForm.to_account.length < 4) {
        lookupResult.value = null
        return
    }
    lookupLoading.value = true
    try {
        const res = await fetch(route('staff.customer-care.lookup-account') + '?account_number=' + encodeURIComponent(txForm.to_account))
        lookupResult.value = await res.json()
    } catch {
        lookupResult.value = null
    } finally {
        lookupLoading.value = false
    }
}

function openTxModal(type, acc) {
    txType.value      = type
    txAccountId.value = acc.id
    txAccountNo.value = acc.account_number
    txBalance.value   = acc.balance ?? 0
    txForm.reset()
    lookupResult.value = null
    txModal.value = true
}

function closeTxModal() {
    txModal.value = false
    txForm.reset()
    lookupResult.value = null
}

const txTitle = computed(() => ({
    deposit:  'Cash Deposit',
    withdraw: 'Cash Withdrawal',
    transfer: 'Inter-Account Transfer',
}[txType.value] ?? ''))

const txRouteMap = computed(() => ({
    deposit:  route('staff.customer-care.quick.deposit',  txAccountId.value),
    withdraw: route('staff.customer-care.quick.withdraw', txAccountId.value),
    transfer: route('staff.customer-care.quick.transfer', txAccountId.value),
}))

function submitTx() {
    txForm.post(txRouteMap.value[txType.value], {
        preserveScroll: true,
        onSuccess: () => closeTxModal(),
    })
}

const fmt2 = n => '$' + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })

/* ══════════════════════════════════════════
   Account Status Modal
   ══════════════════════════════════════════ */
const statusModal    = ref(false)
const statusAccount  = ref(null)  // full acc object

const statusForm = useForm({
    status: '',
    reason: '',
})

// Allowed transitions from each status
const allowedTransitions = {
    inactive:  [
        { value: 'active',    label: 'Activate',  icon: 'ti-circle-check',  color: 'text-emerald-400', bg: 'bg-emerald-900/20 border-emerald-700/30 hover:bg-emerald-900/40' },
        { value: 'closed',    label: 'Close',     icon: 'ti-circle-x',      color: 'text-red-400',     bg: 'bg-red-900/20 border-red-700/30 hover:bg-red-900/40' },
    ],
    active:    [
        { value: 'suspended', label: 'Suspend',   icon: 'ti-lock',          color: 'text-amber-400',   bg: 'bg-amber-900/20 border-amber-700/30 hover:bg-amber-900/40' },
        { value: 'closed',    label: 'Close',     icon: 'ti-circle-x',      color: 'text-red-400',     bg: 'bg-red-900/20 border-red-700/30 hover:bg-red-900/40' },
    ],
    suspended: [
        { value: 'active',    label: 'Reactivate', icon: 'ti-lock-open',    color: 'text-emerald-400', bg: 'bg-emerald-900/20 border-emerald-700/30 hover:bg-emerald-900/40' },
        { value: 'closed',    label: 'Close',      icon: 'ti-circle-x',     color: 'text-red-400',     bg: 'bg-red-900/20 border-red-700/30 hover:bg-red-900/40' },
    ],
    closed:    [],  // terminal
}

function openStatusModal(acc) {
    statusAccount.value = acc
    statusForm.reset()
    statusForm.status = ''
    statusModal.value  = true
}

function closeStatusModal() {
    statusModal.value = false
    statusForm.reset()
    statusAccount.value = null
}

function selectStatus(value) {
    statusForm.status = statusForm.status === value ? '' : value
}

function submitStatus() {
    if (!statusForm.status) return
    statusForm.post(route('staff.customer-care.accounts.status', statusAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => closeStatusModal(),
    })
}

/* ══════════════════════════════════════════
   Freeze / Unfreeze Account
   ══════════════════════════════════════════ */
const freezeModal   = ref(false)
const freezeAccount = ref(null)
const freezeForm    = useForm({ reason: '' })

function openFreezeModal(acc) {
    freezeAccount.value = acc
    freezeForm.reset()
    freezeModal.value   = true
}

function closeFreezeModal() {
    freezeModal.value = false
    freezeAccount.value = null
    freezeForm.reset()
}

function submitFreeze() {
    freezeForm.post(route('staff.customer-care.accounts.freeze', freezeAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => closeFreezeModal(),
    })
}

const unfreezeForm = useForm({})
function submitUnfreeze(acc) {
    if (!confirm(`Unfreeze account ${acc.account_number}? All transactions will be re-enabled.`)) return
    unfreezeForm.post(route('staff.customer-care.accounts.unfreeze', acc.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="`${customer.full_name} — Profile`" />

    <AuthenticatedLayout>
        <div class="shell max-w-7xl mx-auto p-4 md:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-start justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.2)] flex items-center justify-center text-3xl font-serif text-[#C9A84C]">
                        {{ customer.full_name.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="font-serif text-3xl text-[#F0EBE1]">{{ customer.full_name }}</h1>
                        <div class="flex flex-wrap gap-3 mt-2 items-center">
                            <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase border"
                                  :class="kycColor[customer.kyc_status] || 'bg-white/5 text-[#A9B8C6] border-white/10'">
                                KYC: {{ customer.kyc_status?.replace('_', ' ') }}
                            </span>
                            <span class="text-[11px] text-[#A9B8C6]"><i class="ti ti-building mr-1"></i>{{ customer.branch?.branch_name }}</span>
                            <span class="text-[11px] text-[#A9B8C6]"><i class="ti ti-calendar mr-1"></i>Registered {{ fmtDate(customer.created_at) }}</span>
                        </div>
                        <div v-if="customer.rejection_reason" class="mt-3 p-3 rounded-xl bg-[rgba(226,99,90,0.07)] border border-[rgba(226,99,90,0.2)] text-xs text-[#E2635A]">
                            <i class="ti ti-alert-circle mr-1"></i><strong>Rejection Reason:</strong> {{ customer.rejection_reason }}
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Link v-if="customer.kyc_status === 'pending' || customer.kyc_status === 'under_review'"
                          :href="route('staff.customer-care.kyc', customer.id)"
                          class="px-5 py-2.5 rounded-xl bg-[rgba(201,168,76,0.1)] text-[#C9A84C] border border-[rgba(201,168,76,0.2)] text-sm font-bold hover:bg-[rgba(201,168,76,0.15)] transition">
                        <i class="ti ti-file-upload mr-2"></i>KYC Documents
                    </Link>
                    <Link v-if="customer.kyc_status === 'verified'"
                          :href="route('staff.customer-care.accounts.create')"
                          class="px-5 py-2.5 rounded-xl bg-[#C9A84C] text-[#0B1929] text-sm font-bold hover:bg-[#E5C97E] transition">
                        <i class="ti ti-circle-plus mr-2"></i>Open Account
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

                <!-- Left: Personal Information -->
                <div class="xl:col-span-2 space-y-8">

                    <!-- Personal Info -->
                    <section class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-8">
                        <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold mb-6 flex items-center gap-2">
                            <i class="ti ti-user"></i> Personal Information
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Date of Birth</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmtDate(customer.dob) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Gender</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.gender) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Marital Status</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.marital_status) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Nationality</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.nationality) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Religion</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.religion) }}</p></div>
                        </div>
                    </section>

                    <!-- Identity -->
                    <section class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-8">
                        <h2 class="text-[11px] uppercase tracking-widest text-[#E8A830] font-bold mb-6 flex items-center gap-2">
                            <i class="ti ti-id"></i> Identity Document
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">ID Type</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.id_type) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">ID Number</p><p class="text-sm text-[#E8A830] mt-1 font-mono">{{ fmt(customer.id_number) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Issue Date</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmtDate(customer.id_issue_date) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Expiry Date</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmtDate(customer.id_expiry_date) }}</p></div>
                        </div>
                    </section>

                    <!-- Contact & Address -->
                    <section class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-8">
                        <h2 class="text-[11px] uppercase tracking-widest text-[#4CAF7D] font-bold mb-6 flex items-center gap-2">
                            <i class="ti ti-phone"></i> Contact & Address
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Phone</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.phone) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Secondary Phone</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.secondary_phone) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Email</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.email) }}</p></div>
                            <div class="col-span-2"><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Region / City</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.region_city) }}, {{ fmt(customer.district) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Street</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.street_neighbourhood) }}</p></div>
                        </div>
                    </section>

                    <!-- Employment -->
                    <section class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-8">
                        <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-6 flex items-center gap-2">
                            <i class="ti ti-briefcase"></i> Employment & Financials
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Status</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.employment_status) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Employer</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.employer_name) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Monthly Income</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.monthly_income_range) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Source of Funds</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.source_of_funds) }}</p></div>
                            <div><p class="text-[10px] text-[#6B7E8E] uppercase font-bold">Expected Monthly Txns</p><p class="text-sm text-[#F0EBE1] mt-1">{{ fmt(customer.expected_monthly_transactions) }}</p></div>
                        </div>
                    </section>

                    <!-- Accounts Matrix -->
                    <section class="bg-[#112236] border border-[#ffffff14] rounded-3xl overflow-hidden">
                        <div class="px-5 md:px-8 pt-6 pb-3">
                            <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold flex items-center gap-2">
                                <i class="ti ti-credit-card"></i> Accounts ({{ customer.accounts.length }})
                            </h2>
                        </div>

                        <!-- Mobile card view (< md) -->
                        <div class="md:hidden divide-y divide-[#ffffff06]">
                            <div v-if="!customer.accounts.length" class="px-5 py-8 text-center text-sm text-[#6B7E8E] italic">
                                No accounts opened yet.
                            </div>
                            <div v-for="acc in customer.accounts" :key="acc.id"
                                 class="px-5 py-4 space-y-2.5 hover:bg-white/[0.015]">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-mono text-sm font-bold text-[#F0EBE1]">{{ acc.account_number }}</p>
                                        <p class="text-xs text-[#A9B8C6] mt-0.5">{{ acc.account_type?.type_name }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border"
                                              :class="statusColor[acc.status] || 'bg-white/5 text-[#6B7E8E] border-white/10'">
                                            {{ acc.status }}
                                        </span>
                                        <span v-if="acc.is_frozen"
                                              class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border bg-blue-900/30 text-blue-300 border-blue-700/40">
                                            <i class="ti ti-snowflake mr-0.5"></i>Frozen
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] text-[#6B7E8E] uppercase tracking-wide">Balance</p>
                                        <p class="font-mono text-base font-bold text-[#C9A84C] mt-0.5">{{ fmtMoney(acc.balance) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] text-[#6B7E8E] uppercase tracking-wide">Opened</p>
                                        <p class="text-xs text-[#A9B8C6] mt-0.5">{{ fmtDate(acc.opened_at) }}</p>
                                    </div>
                                </div>
                                <!-- Row 1: History + Statement -->
                                <div class="flex gap-2 pt-1">
                                    <Link :href="route('staff.account.transactions', acc.id)"
                                          class="flex-1 text-center py-2 rounded-lg text-xs font-bold text-[#C9A84C] bg-[rgba(201,168,76,0.08)] border border-[rgba(201,168,76,0.2)] hover:bg-[rgba(201,168,76,0.15)] transition">
                                        <i class="ti ti-list mr-1"></i>History
                                    </Link>
                                    <button @click="openStmtModal(acc.id)"
                                            class="flex-1 text-center py-2 rounded-lg text-xs font-bold text-[#4CAF7D] bg-[rgba(76,175,125,0.08)] border border-[rgba(76,175,125,0.2)] hover:bg-[rgba(76,175,125,0.15)] transition">
                                        <i class="ti ti-file-text mr-1"></i>Statement
                                    </button>
                                </div>
                                <!-- Row 2: Quick transactions -->
                                <div class="flex gap-2 pt-1.5">
                                    <button @click="openTxModal('deposit', acc)"
                                            class="flex-1 text-center py-2 rounded-lg text-xs font-bold text-emerald-400 bg-emerald-900/20 border border-emerald-700/30 hover:bg-emerald-900/40 transition">
                                        <i class="ti ti-arrow-down-right mr-1"></i>Deposit
                                    </button>
                                    <button @click="openTxModal('withdraw', acc)"
                                            class="flex-1 text-center py-2 rounded-lg text-xs font-bold text-amber-400 bg-amber-900/20 border border-amber-700/30 hover:bg-amber-900/40 transition">
                                        <i class="ti ti-arrow-up-right mr-1"></i>Withdraw
                                    </button>
                                    <button @click="openTxModal('transfer', acc)"
                                            class="flex-1 text-center py-2 rounded-lg text-xs font-bold text-indigo-400 bg-indigo-900/20 border border-indigo-700/30 hover:bg-indigo-900/40 transition">
                                        <i class="ti ti-arrows-right-left mr-1"></i>Transfer
                                    </button>
                                </div>
                                <!-- Row 3: Account management + Freeze -->
                                <div v-if="acc.is_frozen" class="px-3 py-2 rounded-lg bg-blue-900/20 border border-blue-700/30 text-xs text-blue-300 flex items-start gap-2">
                                    <i class="ti ti-snowflake shrink-0 mt-0.5"></i>
                                    <span><strong>Frozen:</strong> {{ acc.frozen_reason }}</span>
                                </div>
                                <div class="flex gap-2 pt-1.5">
                                    <button @click="openStatusModal(acc)"
                                            :disabled="acc.status === 'closed'"
                                            class="flex-1 py-2 rounded-lg text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed"
                                            :class="acc.status === 'suspended'
                                                ? 'text-amber-400 bg-amber-900/20 border border-amber-700/30 hover:bg-amber-900/40'
                                                : 'text-slate-400 bg-slate-800/50 border border-slate-700/40 hover:bg-slate-700/60'">
                                        <i :class="acc.status === 'suspended' ? 'ti ti-lock mr-1' : 'ti ti-settings mr-1'"></i>
                                        {{ acc.status === 'suspended' ? 'Suspended' : 'Status' }}
                                    </button>
                                    <button v-if="!acc.is_frozen && acc.status !== 'closed'"
                                            @click="openFreezeModal(acc)"
                                            class="flex-1 py-2 rounded-lg text-xs font-bold text-blue-300 bg-blue-900/20 border border-blue-700/30 hover:bg-blue-900/40 transition">
                                        <i class="ti ti-snowflake mr-1"></i>Freeze
                                    </button>
                                    <button v-if="acc.is_frozen"
                                            @click="submitUnfreeze(acc)"
                                            class="flex-1 py-2 rounded-lg text-xs font-bold text-emerald-400 bg-emerald-900/20 border border-emerald-700/30 hover:bg-emerald-900/40 transition">
                                        <i class="ti ti-lock-open mr-1"></i>Unfreeze
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop table view (>= md) -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left min-w-[560px]">
                                <thead>
                                    <tr class="bg-white/[0.02] border-y border-[#ffffff08]">
                                        <th class="px-5 py-3 text-[10px] uppercase text-[#C9A84C] tracking-widest">Account Number</th>
                                        <th class="px-5 py-3 text-[10px] uppercase text-[#C9A84C] tracking-widest">Type</th>
                                        <th class="px-5 py-3 text-[10px] uppercase text-[#C9A84C] tracking-widest">Balance</th>
                                        <th class="px-5 py-3 text-[10px] uppercase text-[#C9A84C] tracking-widest">Status</th>
                                        <th class="px-5 py-3 text-[10px] uppercase text-[#C9A84C] tracking-widest">Opened</th>
                                        <th class="px-5 py-3 text-[10px] uppercase text-[#A9B8C6] tracking-widest text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#ffffff05]">
                                    <tr v-for="acc in customer.accounts" :key="acc.id" class="hover:bg-white/[0.02]">
                                        <td class="px-5 py-3.5 font-mono text-sm text-[#F0EBE1] whitespace-nowrap">{{ acc.account_number }}</td>
                                        <td class="px-5 py-3.5 text-sm text-[#A9B8C6] whitespace-nowrap">{{ acc.account_type?.type_name }}</td>
                                        <td class="px-5 py-3.5 text-sm font-mono font-bold text-[#C9A84C] whitespace-nowrap">{{ fmtMoney(acc.balance) }}</td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex flex-col gap-1">
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border w-fit"
                                                      :class="statusColor[acc.status] || 'bg-white/5 text-[#6B7E8E] border-white/10'">
                                                    {{ acc.status }}
                                                </span>
                                                <span v-if="acc.is_frozen"
                                                      class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border w-fit bg-blue-900/30 text-blue-300 border-blue-700/40">
                                                    <i class="ti ti-snowflake mr-0.5"></i>Frozen
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-xs text-[#A9B8C6] whitespace-nowrap">{{ fmtDate(acc.opened_at) }}</td>
                                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                            <div class="inline-flex flex-wrap gap-1.5 justify-end">
                                                <Link :href="route('staff.account.transactions', acc.id)"
                                                      class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-[#C9A84C] bg-[rgba(201,168,76,0.08)] border border-[rgba(201,168,76,0.2)] hover:bg-[rgba(201,168,76,0.15)] transition">
                                                    <i class="ti ti-list"></i> History
                                                </Link>
                                                <button @click="openStmtModal(acc.id)"
                                                        class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-[#4CAF7D] bg-[rgba(76,175,125,0.08)] border border-[rgba(76,175,125,0.2)] hover:bg-[rgba(76,175,125,0.15)] transition">
                                                    <i class="ti ti-file-text"></i> Statement
                                                </button>
                                                <button @click="openTxModal('deposit', acc)"
                                                        class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-emerald-400 bg-emerald-900/20 border border-emerald-700/30 hover:bg-emerald-900/40 transition">
                                                    <i class="ti ti-arrow-down-right"></i> Deposit
                                                </button>
                                                <button @click="openTxModal('withdraw', acc)"
                                                        class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-amber-400 bg-amber-900/20 border border-amber-700/30 hover:bg-amber-900/40 transition">
                                                    <i class="ti ti-arrow-up-right"></i> Withdraw
                                                </button>
                                                <button @click="openTxModal('transfer', acc)"
                                                        class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-indigo-400 bg-indigo-900/20 border border-indigo-700/30 hover:bg-indigo-900/40 transition">
                                                    <i class="ti ti-arrows-right-left"></i> Transfer
                                                </button>
                                                <button @click="openStatusModal(acc)"
                                                        :disabled="acc.status === 'closed'"
                                                        class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold transition disabled:opacity-40 disabled:cursor-not-allowed"
                                                        :class="acc.status === 'suspended'
                                                            ? 'text-amber-400 bg-amber-900/20 border border-amber-700/30 hover:bg-amber-900/40'
                                                            : 'text-slate-400 bg-slate-800/50 border border-slate-700/40 hover:bg-slate-700/60'">
                                                    <i :class="acc.status === 'suspended' ? 'ti ti-lock' : 'ti ti-settings'"></i>
                                                    {{ acc.status === 'suspended' ? 'Suspended' : 'Manage' }}
                                                </button>
                                                <button v-if="!acc.is_frozen && acc.status !== 'closed'"
                                                        @click="openFreezeModal(acc)"
                                                        class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-blue-300 bg-blue-900/20 border border-blue-700/30 hover:bg-blue-900/40 transition">
                                                    <i class="ti ti-snowflake"></i> Freeze
                                                </button>
                                                <button v-if="acc.is_frozen"
                                                        @click="submitUnfreeze(acc)"
                                                        class="text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-emerald-400 bg-emerald-900/20 border border-emerald-700/30 hover:bg-emerald-900/40 transition">
                                                    <i class="ti ti-lock-open"></i> Unfreeze
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!customer.accounts.length">
                                        <td colspan="6" class="px-5 py-8 text-center text-sm text-[#6B7E8E] italic">No accounts opened yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Right: Quick Info + KYC Docs -->
                <div class="space-y-6">
                    <!-- Next of Kin -->
                    <div class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-6">
                        <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-4">Next of Kin</h2>
                        <p class="text-sm font-medium text-[#F0EBE1]">{{ fmt(customer.next_of_kin_name) }}</p>
                        <p class="text-xs text-[#A9B8C6] mt-1">{{ fmt(customer.next_of_kin_relation) }}</p>
                        <p class="text-xs text-[#A9B8C6] mt-1"><i class="ti ti-phone mr-1"></i>{{ fmt(customer.next_of_kin_phone) }}</p>
                    </div>

                    <!-- KYC Docs -->
                    <div class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-6">
                        <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-4">KYC Documents ({{ customer.kyc_documents.length }})</h2>
                        <div class="space-y-3">
                            <div v-for="doc in customer.kyc_documents" :key="doc.id"
                                 class="flex items-center justify-between p-3 rounded-xl bg-white/[0.02] border border-[#ffffff08]">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <i class="ti ti-file text-[#C9A84C] text-sm shrink-0"></i>
                                    <span class="text-xs text-[#F0EBE1] truncate">{{ doc.doc_type }}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded border"
                                          :class="doc.status === 'verified' ? 'text-[#4CAF7D] border-[rgba(76,175,125,0.3)]' : 'text-[#E8A830] border-[rgba(232,168,48,0.3)]'">
                                        {{ doc.status }}
                                    </span>
                                    <a :href="'/storage/' + doc.file_path" target="_blank"
                                       class="text-[#6B7E8E] hover:text-[#C9A84C] transition">
                                        <i class="ti ti-external-link text-sm"></i>
                                    </a>
                                </div>
                            </div>
                            <p v-if="!customer.kyc_documents.length" class="text-xs text-[#6B7E8E] italic">No documents on file.</p>
                        </div>
                    </div>

                    <!-- Registration Info -->
                    <div class="bg-[#112236] border border-[#ffffff14] rounded-3xl p-6">
                        <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-4">Registration</h2>
                        <p class="text-xs text-[#A9B8C6]">Registered by</p>
                        <p class="text-sm text-[#F0EBE1] mt-1">{{ customer.registered_by?.full_name || '—' }}</p>
                        <p class="text-xs text-[#A9B8C6] mt-4">Home Branch</p>
                        <p class="text-sm text-[#F0EBE1] mt-1">{{ customer.branch?.branch_name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- ══════════ Quick Transaction Modal ══════════ -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="txModal"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="bg-[#112236] border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md">

                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-700/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div :class="txType === 'deposit'  ? 'bg-emerald-900/30 text-emerald-400'
                                        : txType === 'withdraw' ? 'bg-amber-900/30 text-amber-400'
                                        :                         'bg-indigo-900/30 text-indigo-400'"
                                 class="w-9 h-9 rounded-xl flex items-center justify-center">
                                <i :class="txType === 'deposit'  ? 'ti ti-arrow-down-right'
                                          : txType === 'withdraw' ? 'ti ti-arrow-up-right'
                                          :                         'ti ti-arrows-right-left'"
                                   class="text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white text-sm">{{ txTitle }}</h3>
                                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ txAccountNo }}</p>
                            </div>
                        </div>
                        <button @click="closeTxModal" class="text-slate-400 hover:text-white transition-colors">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitTx" class="px-6 py-5 space-y-4">

                        <!-- Account balance info -->
                        <div class="bg-slate-800/60 border border-slate-700/40 rounded-xl px-4 py-3 flex items-center justify-between">
                            <span class="text-xs text-slate-400">Current Balance</span>
                            <span class="font-mono font-bold text-[#C9A84C] text-sm">{{ fmt2(txBalance) }}</span>
                        </div>

                        <!-- Transfer: receiver account with live lookup -->
                        <div v-if="txType === 'transfer'">
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Receiver Account Number *</label>
                            <input v-model="txForm.to_account"
                                   @blur="lookupReceiver"
                                   @input="lookupResult = null"
                                   type="text"
                                   class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white font-mono
                                          placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/60 transition-colors"
                                   placeholder="e.g. ACC-XXXXXXXX" required />
                            <!-- Lookup result -->
                            <div v-if="lookupLoading" class="mt-2 text-xs text-slate-400 flex items-center gap-1.5">
                                <i class="ti ti-loader animate-spin"></i> Looking up account…
                            </div>
                            <div v-else-if="lookupResult?.found"
                                 class="mt-2 flex items-center gap-2 bg-emerald-900/20 border border-emerald-700/30 rounded-lg px-3 py-2">
                                <i class="ti ti-user-check text-emerald-400 text-sm"></i>
                                <div>
                                    <p class="text-xs font-semibold text-emerald-300">{{ lookupResult.customer_name }}</p>
                                    <p class="text-[10px] text-slate-400 capitalize">{{ lookupResult.status }}</p>
                                </div>
                            </div>
                            <div v-else-if="lookupResult && !lookupResult.found"
                                 class="mt-2 flex items-center gap-2 bg-red-900/20 border border-red-700/30 rounded-lg px-3 py-2">
                                <i class="ti ti-alert-circle text-red-400 text-sm"></i>
                                <p class="text-xs text-red-300">Account not found</p>
                            </div>
                            <p v-if="txForm.errors.to_account" class="text-red-400 text-xs mt-1">{{ txForm.errors.to_account }}</p>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Amount (USD) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-mono">$</span>
                                <input v-model="txForm.amount"
                                       type="number" step="0.01" min="0.01"
                                       class="w-full bg-slate-800 border border-slate-600 rounded-xl pl-7 pr-3 py-2.5 text-sm text-white font-mono
                                              placeholder-slate-500 focus:outline-none focus:ring-2 focus:border-[#C9A84C]/60 transition-colors"
                                       :class="txType === 'deposit'  ? 'focus:ring-emerald-500/50'
                                              : txType === 'withdraw' ? 'focus:ring-amber-500/50'
                                              :                         'focus:ring-indigo-500/50'"
                                       placeholder="0.00" required />
                            </div>
                            <p v-if="txForm.errors.amount" class="text-red-400 text-xs mt-1">{{ txForm.errors.amount }}</p>
                        </div>

                        <!-- Optional note -->
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Note <span class="text-slate-600">(optional)</span></label>
                            <input v-model="txForm.note" type="text"
                                   class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                          placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors"
                                   placeholder="e.g. Rent payment, salary, etc." />
                        </div>

                        <!-- Error -->
                        <p v-if="txForm.errors.error" class="text-red-400 text-xs bg-red-900/20 border border-red-700/30 rounded-lg px-3 py-2">
                            <i class="ti ti-alert-circle mr-1"></i>{{ txForm.errors.error }}
                        </p>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="closeTxModal"
                                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="txForm.processing"
                                    :class="txType === 'deposit'  ? 'bg-emerald-600 hover:bg-emerald-500'
                                           : txType === 'withdraw' ? 'bg-amber-600 hover:bg-amber-500'
                                           :                         'bg-indigo-600 hover:bg-indigo-500'"
                                    class="flex-1 disabled:opacity-50 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                                <i :class="txType === 'deposit'  ? 'ti ti-arrow-down-right'
                                          : txType === 'withdraw' ? 'ti ti-arrow-up-right'
                                          :                         'ti ti-arrows-right-left'"></i>
                                {{ txForm.processing ? 'Processing…' : 'Confirm ' + txTitle }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ══════════ Statement Date-Range Modal ══════════ -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showStmtModal"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="bg-[#112236] border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md">

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

                        <div v-if="stmtFrom && stmtTo"
                             class="bg-slate-800/60 border border-slate-700/40 rounded-xl px-4 py-3 flex items-center gap-3">
                            <i class="ti ti-calendar-event text-[#C9A84C]"></i>
                            <p class="text-xs text-slate-300">
                                <span class="font-semibold text-white">{{ new Date(stmtFrom + 'T00:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) }}</span>
                                <span class="text-slate-500 mx-2">→</span>
                                <span class="font-semibold text-white">{{ new Date(stmtTo + 'T00:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) }}</span>
                            </p>
                        </div>

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

    <!-- ══════════ Account Status Modal ══════════ -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="statusModal"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="bg-[#112236] border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md">

                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-700/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-700/50 flex items-center justify-center">
                                <i class="ti ti-adjustments text-slate-300 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white text-sm">Manage Account Status</h3>
                                <p class="font-mono text-xs text-slate-400 mt-0.5">{{ statusAccount?.account_number }}</p>
                            </div>
                        </div>
                        <button @click="closeStatusModal" class="text-slate-400 hover:text-white transition-colors">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5">

                        <!-- Current status -->
                        <div class="flex items-center justify-between bg-slate-800/60 border border-slate-700/40 rounded-xl px-4 py-3">
                            <span class="text-xs text-slate-400">Current Status</span>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase border"
                                  :class="statusColor[statusAccount?.status] || 'bg-white/5 text-[#6B7E8E] border-white/10'">
                                {{ statusAccount?.status }}
                            </span>
                        </div>

                        <!-- Transition options -->
                        <div v-if="allowedTransitions[statusAccount?.status]?.length">
                            <p class="text-xs font-medium text-slate-400 mb-3 uppercase tracking-wide">Change Status To</p>
                            <div class="grid gap-2.5">
                                <button v-for="opt in allowedTransitions[statusAccount?.status]" :key="opt.value"
                                        @click="selectStatus(opt.value)"
                                        class="flex items-center gap-3 px-4 py-3.5 rounded-xl border text-sm font-semibold transition-all"
                                        :class="[opt.bg, opt.color,
                                                 statusForm.status === opt.value ? 'ring-2 ring-offset-1 ring-offset-[#112236] ring-current' : '']">
                                    <i :class="[opt.icon, 'text-base']"></i>
                                    <span>{{ opt.label }} Account</span>
                                    <i v-if="statusForm.status === opt.value" class="ti ti-check ml-auto text-base"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Terminal state notice -->
                        <div v-else class="flex items-center gap-3 bg-red-900/10 border border-red-700/25 rounded-xl px-4 py-3">
                            <i class="ti ti-alert-circle text-red-400 text-base shrink-0"></i>
                            <p class="text-xs text-red-300">This account is <strong>permanently closed</strong> and cannot be changed.</p>
                        </div>

                        <!-- Optional reason -->
                        <div v-if="statusForm.status">
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">
                                Reason <span class="text-slate-600">(optional)</span>
                            </label>
                            <textarea v-model="statusForm.reason" rows="2"
                                      class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                             placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/50 focus:border-[#C9A84C]/60 transition-colors resize-none"
                                      placeholder="e.g. Customer request, suspicious activity, compliance hold…">
                            </textarea>
                        </div>

                        <!-- Closure warning -->
                        <div v-if="statusForm.status === 'closed'"
                             class="flex items-start gap-3 bg-red-900/15 border border-red-700/30 rounded-xl px-4 py-3">
                            <i class="ti ti-alert-triangle text-red-400 text-base shrink-0 mt-0.5"></i>
                            <p class="text-xs text-red-300 leading-relaxed">
                                <strong>Closing is permanent.</strong> This account cannot be reopened. Ensure any remaining balance has been settled before proceeding.
                            </p>
                        </div>

                        <!-- Error -->
                        <p v-if="statusForm.errors.error"
                           class="text-red-400 text-xs bg-red-900/20 border border-red-700/30 rounded-lg px-3 py-2">
                            <i class="ti ti-alert-circle mr-1"></i>{{ statusForm.errors.error }}
                        </p>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-1">
                            <button @click="closeStatusModal"
                                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button @click="submitStatus"
                                    :disabled="!statusForm.status || statusForm.processing"
                                    class="flex-1 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2"
                                    :class="statusForm.status === 'closed'    ? 'bg-red-700 hover:bg-red-600'
                                           : statusForm.status === 'suspended' ? 'bg-amber-600 hover:bg-amber-500'
                                           :                                     'bg-emerald-600 hover:bg-emerald-500'">
                                <i :class="statusForm.processing ? 'ti ti-loader animate-spin'
                                          : statusForm.status === 'closed'    ? 'ti ti-circle-x'
                                          : statusForm.status === 'suspended' ? 'ti ti-lock'
                                          :                                     'ti ti-circle-check'"></i>
                                {{ statusForm.processing ? 'Updating…'
                                   : statusForm.status   ? 'Confirm ' + allowedTransitions[statusAccount?.status]?.find(o => o.value === statusForm.status)?.label
                                   :                       'Select a Status' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ══════════ Freeze Account Modal ══════════ -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="freezeModal"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="bg-[#112236] border border-blue-700/30 rounded-2xl shadow-2xl w-full max-w-md">

                    <div class="px-6 py-5 border-b border-slate-700/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-900/40 flex items-center justify-center">
                                <i class="ti ti-snowflake text-blue-300 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white text-sm">Freeze Account</h3>
                                <p class="font-mono text-xs text-slate-400 mt-0.5">{{ freezeAccount?.account_number }}</p>
                            </div>
                        </div>
                        <button @click="closeFreezeModal" class="text-slate-400 hover:text-white transition-colors">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <div class="flex items-start gap-3 bg-blue-900/15 border border-blue-700/30 rounded-xl px-4 py-3">
                            <i class="ti ti-alert-circle text-blue-300 text-base shrink-0 mt-0.5"></i>
                            <p class="text-xs text-blue-200 leading-relaxed">
                                Freezing this account will <strong>block all deposits, withdrawals and transfers</strong> immediately.
                                A reason is required and will be recorded in the audit log.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                Freeze Reason <span class="text-blue-400">*</span>
                            </label>
                            <textarea v-model="freezeForm.reason" required rows="3"
                                      class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                             placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-colors resize-none"
                                      placeholder="e.g. Suspected fraud, AML review, court order, customer dispute…">
                            </textarea>
                            <p v-if="freezeForm.errors.reason" class="text-red-400 text-xs mt-1">{{ freezeForm.errors.reason }}</p>
                        </div>

                        <p v-if="freezeForm.errors.error"
                           class="text-red-400 text-xs bg-red-900/20 border border-red-700/30 rounded-lg px-3 py-2">
                            <i class="ti ti-alert-circle mr-1"></i>{{ freezeForm.errors.error }}
                        </p>

                        <div class="flex gap-3 pt-1">
                            <button @click="closeFreezeModal"
                                    class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium py-2.5 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button @click="submitFreeze"
                                    :disabled="!freezeForm.reason.trim() || freezeForm.processing"
                                    class="flex-1 bg-blue-700 hover:bg-blue-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                                <i :class="freezeForm.processing ? 'ti ti-loader animate-spin' : 'ti ti-snowflake'"></i>
                                {{ freezeForm.processing ? 'Freezing…' : 'Confirm Freeze' }}
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
