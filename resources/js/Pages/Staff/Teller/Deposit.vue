<script setup>
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReceiptModal from '@/Components/ReceiptModal.vue';
import { ref, watch, computed } from 'vue';
import axios from 'axios';

/* ── Props ── */
const props = defineProps({
    history:         { type: Object, default: () => ({}) },
    today_total:     { type: Number, default: 0 },
    filters:         { type: Object, default: () => ({}) },
    receipt:         { type: Object, default: null },
    pending_receipt: { type: Object, default: null },
});

/* ── Helpers ── */
const fmt  = (n) => Number(n).toLocaleString('en-US', { minimumFractionDigits: 2 });
const fmtD = (d) => new Date(d).toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });

/* ── Form ── */
const form = useForm({ account_number: '', amount: '', remarks: '' });

/* ── Limit check ── */
const page     = usePage();
const txnLimit = computed(() => page.props.auth?.user?.role?.txn_limit ?? null);
const overLimit = computed(() =>
    txnLimit.value !== null && Number(form.amount) > 0 && Number(form.amount) > Number(txnLimit.value)
);

/* ── Live account lookup ── */
const lookup  = ref(null);
const looking = ref(false);
let   lookupTimer;
watch(() => form.account_number, (val) => {
    lookup.value = null;
    clearTimeout(lookupTimer);
    if (val.length < 5) return;
    lookupTimer = setTimeout(async () => {
        looking.value = true;
        try {
            const { data } = await axios.get(route('staff.teller.lookup'), { params: { account_number: val } });
            lookup.value = data;
        } catch { lookup.value = { found: false }; }
        finally { looking.value = false; }
    }, 500);
});

const submit      = () => form.post(route('staff.deposit'), { preserveScroll: false });
const showReceipt        = ref(!!props.receipt);
const showPendingReceipt = ref(!!props.pending_receipt);

/* Modal: reactive to prop updates (Inertia may update props without full remount) */
const showModal = ref(false);
watch(
    [() => props.receipt, () => props.pending_receipt],
    ([r, p]) => { if (r || p) showModal.value = true; },
    { immediate: true }
);

/* ── History filters ── */
const dateFrom     = ref(props.filters?.date_from ?? '');
const dateTo       = ref(props.filters?.date_to   ?? '');
const applyFilters = () => router.get(route('staff.teller.deposit'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true, replace: true });
const clearFilters = () => { dateFrom.value = ''; dateTo.value = ''; applyFilters(); };

/* ── Shared print helper ── */
const openPrintWindow = (html) => {
    const w = window.open('', '_blank', 'width=420,height=640,scrollbars=no,menubar=no,toolbar=no');
    if (w) { w.document.write(html); w.document.close(); w.focus(); setTimeout(() => w.print(), 400); }
};

const receiptHtml = (fields, title, amountLabel, amountValue, amountColor) => `
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${title}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Georgia',serif;background:#fff;color:#222;width:320px;margin:0 auto;padding:20px 0}
  .hdr{background:#0B1929;color:#fff;text-align:center;padding:20px 16px 14px}
  .hdr img{height:42px;margin:0 auto 8px;display:block}
  .hdr h1{font-size:17px;letter-spacing:.05em;font-weight:700}
  .hdr p{font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:#7a9ab5;margin-top:3px}
  .body{padding:16px}
  .row{display:flex;justify-content:space-between;align-items:baseline;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:12px}
  .row:last-child{border-bottom:none}
  .lbl{color:#888;font-size:11px;flex-shrink:0;margin-right:8px}
  .val{font-weight:600;text-align:right}
  .mono{font-family:monospace;font-size:11px}
  .big{font-size:20px;font-weight:700;color:${amountColor}}
  .divider{border-top:2px solid #eee;margin:6px 0}
  .ftr{background:#0B1929;text-align:center;padding:10px 16px}
  .ftr p{color:#4a6070;font-size:9px;text-transform:uppercase;letter-spacing:.1em}
  .ftr p+p{margin-top:3px}
  @media print{body{width:100%}}
</style></head><body>
<div class="hdr">
  <img src="/images/MAin Logo.png" alt="Gobaad Bank" onerror="this.style.display='none'">
  <h1>Gobaad Bank</h1><p>${title}</p>
</div>
<div class="body">
  ${fields.map(([l,v,mono]) => `<div class="row"><span class="lbl">${l}</span><span class="val${mono?' mono':''}">${v}</span></div>`).join('')}
  <div class="divider"></div>
  <div class="row"><span class="lbl" style="font-size:13px;font-weight:600">${amountLabel}</span><span class="big">${amountValue}</span></div>
</div>
<div class="ftr"><p>Gobaad Bank — Official Receipt</p><p style="color:#3a5060">Keep this receipt for your records.</p></div>
</body></html>`;

/* ── Print per-row receipt ── */
const doPrint = (txn) => {
    const extra = txn.is_reversed ? '<div style="text-align:center;margin-top:12px"><span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:.08em;background:#fde8e8;color:#c0392b;border:1px solid #f5c6c6">REVERSED</span></div>' : '';
    openPrintWindow(receiptHtml([
        ['Reference',    txn.reference,      true],
        ['Type',         'Cash Deposit',     false],
        ['Customer',     txn.customer_name,  false],
        ['Account',      txn.account_number, true],
        ['Date &amp; Time', fmtD(txn.created_at), false],
        ['Balance After','$'+fmt(txn.balance_after), true],
    ], 'Cash Deposit Receipt', 'Amount Deposited', '+$'+fmt(txn.amount), '#2e7d52')
        .replace('</body>', extra + '</body>'));
};

/* ── Reversal ── */
const reversing   = ref(null);
const reverseForm = useForm({});
const confirmReverse = (txn) => { reversing.value = txn; };
const cancelReverse  = ()    => { reversing.value = null; };
const submitReverse  = ()    => {
    reverseForm.post(route('staff.teller.reverse', reversing.value.id), {
        onSuccess: () => { reversing.value = null; },
        onError:   () => { reversing.value = null; },
    });
};

/* ── Cancel pending ── */
const cancelling   = ref(null);
const cancelForm   = useForm({});
const confirmCancel = (txn) => { cancelling.value = txn; };
const dismissCancel = ()    => { cancelling.value = null; };
const submitCancel  = ()    => {
    cancelForm.post(route('staff.teller.cancel', cancelling.value.id), {
        onSuccess: () => { cancelling.value = null; },
        onError:   () => { cancelling.value = null; },
    });
};
</script>

<template>
  <Head title="Cash Deposit" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-[rgba(76,175,125,0.12)] flex items-center justify-center text-[#4CAF7D] text-lg">
            <i class="ti ti-arrow-down-circle"></i>
          </div>
          <div>
            <h1 class="font-serif text-xl text-[#F0EBE1]">Cash <em class="text-[#4CAF7D] not-italic">Deposit</em></h1>
            <p class="text-[11px] text-[#A9B8C6] mt-0.5">Credit a customer account with cash</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-[10px] text-[#6B7E8E] uppercase tracking-widest">Today's Deposits</p>
          <p class="font-serif text-xl text-[#4CAF7D]">${{ fmt(today_total) }}</p>
        </div>
      </div>
    </template>

    <div class="p-6 md:p-8 max-w-7xl mx-auto space-y-8">

      <!-- ── Flash success ── -->
      <transition name="slide-down">
        <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D]/30 text-[#4CAF7D] text-sm">
          <i class="ti ti-circle-check text-lg shrink-0"></i>{{ $page.props.flash.success }}
        </div>
      </transition>

      <!-- ── Flash error ── -->
      <transition name="slide-down">
        <div v-if="$page.props.flash?.error" class="flex items-center gap-3 p-4 rounded-xl bg-[rgba(226,99,90,0.1)] border border-[#E2635A]/30 text-[#E2635A] text-sm">
          <i class="ti ti-alert-circle text-lg shrink-0"></i>{{ $page.props.flash.error }}
        </div>
      </transition>

      <!-- ── Main grid: Form + History ── -->
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Form (2 cols) -->
        <div class="lg:col-span-2 bg-[rgba(255,255,255,0.02)] border border-[rgba(255,255,255,0.08)] rounded-2xl overflow-hidden">
          <div class="h-1 bg-[#4CAF7D]"></div>
          <div class="p-6">
            <h2 class="font-serif text-lg text-[#F0EBE1] mb-6">New Deposit</h2>
            <form @submit.prevent="submit" class="space-y-5">

              <div class="space-y-1.5">
                <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Account Number <span class="text-[#C9A84C]">*</span></label>
                <div class="relative">
                  <input type="text" v-model="form.account_number" required placeholder="e.g. GAR002000001"
                         class="w-full font-mono text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg px-4 py-2.5 outline-none focus:border-[#4CAF7D] transition uppercase"
                         :class="{ 'border-[#4CAF7D]': lookup?.found, 'border-[#E2635A]': lookup && !lookup.found }" />
                  <i v-if="looking"            class="ti ti-loader-2 animate-spin absolute right-3 top-1/2 -translate-y-1/2 text-[#6B7E8E]"></i>
                  <i v-else-if="lookup?.found" class="ti ti-circle-check absolute right-3 top-1/2 -translate-y-1/2 text-[#4CAF7D]"></i>
                  <i v-else-if="lookup && !lookup.found" class="ti ti-circle-x absolute right-3 top-1/2 -translate-y-1/2 text-[#E2635A]"></i>
                </div>
                <div v-if="lookup?.found" class="mt-2 p-3 rounded-lg bg-[rgba(76,175,125,0.06)] border border-[#4CAF7D]/20 text-sm space-y-1">
                  <div class="flex justify-between"><span class="text-[#6B7E8E] text-[11px]">Customer</span><span class="text-[#F0EBE1] font-medium">{{ lookup.customer_name }}</span></div>
                  <div class="flex justify-between"><span class="text-[#6B7E8E] text-[11px]">Product</span><span class="text-[#A9B8C6]">{{ lookup.account_type }}</span></div>
                  <div class="flex justify-between"><span class="text-[#6B7E8E] text-[11px]">Current Balance</span><span class="text-[#4CAF7D] font-mono font-bold">${{ fmt(lookup.balance) }}</span></div>
                  <div class="flex justify-between"><span class="text-[#6B7E8E] text-[11px]">Status</span><span class="capitalize" :class="lookup.status === 'active' ? 'text-[#4CAF7D]' : 'text-[#E2635A]'">{{ lookup.status }}</span></div>
                </div>
                <p v-if="lookup && !lookup.found"   class="text-[11px] text-[#E2635A]">Account not found</p>
                <p v-if="form.errors.account_number" class="text-[11px] text-[#E2635A]">{{ form.errors.account_number }}</p>
              </div>

              <div class="space-y-1.5">
                <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Amount ($) <span class="text-[#C9A84C]">*</span></label>
                <input type="number" step="0.01" min="0.01" v-model="form.amount" required placeholder="0.00"
                       class="w-full text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border rounded-lg px-4 py-2.5 outline-none transition"
                       :class="overLimit
                           ? 'border-amber-500/60 focus:border-amber-400'
                           : 'border-[rgba(255,255,255,0.1)] focus:border-[#4CAF7D]'" />
                <!-- Over-limit inline warning -->
                <div v-if="overLimit"
                     class="flex items-start gap-2 p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px]">
                  <i class="ti ti-clock-exclamation text-sm shrink-0 mt-0.5"></i>
                  <span>
                    Amount exceeds your <strong class="text-amber-200">${{ fmt(txnLimit) }}</strong> transaction limit.
                    This deposit will be sent to your supervisor for approval before funds are posted.
                  </span>
                </div>
                <p v-if="form.errors.amount" class="text-[11px] text-[#E2635A]">{{ form.errors.amount }}</p>
              </div>

              <!-- Remarks -->
              <div class="space-y-1.5">
                <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Remarks <span class="text-[#6B7E8E] font-normal">(optional)</span></label>
                <textarea v-model="form.remarks" rows="2" placeholder="Additional notes or purpose of this deposit…"
                          class="w-full text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg px-4 py-2.5 outline-none focus:border-[#4CAF7D] transition resize-none placeholder-[#3a5060]"></textarea>
                <p v-if="form.errors.remarks" class="text-[11px] text-[#E2635A]">{{ form.errors.remarks }}</p>
              </div>

              <div v-if="form.errors.error" class="p-3 rounded-lg bg-[rgba(226,99,90,0.08)] border border-[#E2635A]/30 text-[#E2635A] text-sm flex gap-2">
                <i class="ti ti-alert-circle shrink-0 mt-0.5"></i>{{ form.errors.error }}
              </div>

              <button type="submit" :disabled="form.processing || (lookup !== null && !lookup?.found)"
                      class="w-full disabled:opacity-50 disabled:cursor-not-allowed text-white py-3 rounded-xl font-semibold text-sm transition flex items-center justify-center gap-2"
                      :class="overLimit
                          ? 'bg-amber-500 hover:bg-amber-400'
                          : 'bg-[#4CAF7D] hover:bg-[#3d9e6e]'">
                <i :class="overLimit ? 'ti ti-send text-lg' : 'ti ti-arrow-down-circle text-lg'"></i>
                {{ form.processing ? 'Processing…' : overLimit ? 'Submit for Approval' : 'Process Deposit' }}
              </button>
            </form>
          </div>
        </div>

        <!-- History (3 cols) -->
        <div class="lg:col-span-3 bg-[rgba(255,255,255,0.02)] border border-[rgba(255,255,255,0.08)] rounded-2xl overflow-hidden">
          <div class="h-1 bg-[#4CAF7D]"></div>
          <div class="p-5">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-serif text-lg text-[#F0EBE1]">My Deposit History</h2>
              <span class="text-[11px] text-[#6B7E8E]">{{ history.total ?? 0 }} transactions</span>
            </div>

            <div class="flex gap-2 mb-4">
              <input type="date" v-model="dateFrom" class="flex-1 text-xs text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.08)] rounded-lg px-3 py-2 outline-none focus:border-[#4CAF7D]" />
              <input type="date" v-model="dateTo"   class="flex-1 text-xs text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.08)] rounded-lg px-3 py-2 outline-none focus:border-[#4CAF7D]" />
              <button @click="applyFilters" class="px-3 py-2 rounded-lg bg-[#4CAF7D]/10 text-[#4CAF7D] border border-[#4CAF7D]/20 text-xs hover:bg-[#4CAF7D]/20 transition">Filter</button>
              <button @click="clearFilters" class="px-3 py-2 rounded-lg bg-[rgba(255,255,255,0.04)] text-[#6B7E8E] border border-[rgba(255,255,255,0.08)] text-xs hover:text-[#A9B8C6] transition">Clear</button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[rgba(255,255,255,0.06)]">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-[#112236] border-b border-[rgba(255,255,255,0.06)]">
                    <th class="px-3 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C]">Reference</th>
                    <th class="px-3 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C]">Account / Customer</th>
                    <th class="px-3 py-3 text-center text-[10px] uppercase tracking-widest text-[#C9A84C]">Status</th>
                    <th class="px-3 py-3 text-right text-[10px] uppercase tracking-widest text-[#C9A84C]">Amount</th>
                    <th class="px-3 py-3 text-right text-[10px] uppercase tracking-widest text-[#C9A84C] hidden md:table-cell">Bal. After</th>
                    <th class="px-3 py-3 text-left text-[10px] uppercase tracking-widest text-[#C9A84C] hidden lg:table-cell">Date</th>
                    <th class="px-3 py-3 text-center text-[10px] uppercase tracking-widest text-[#C9A84C]">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.04)]">
                  <tr v-if="!history.data?.length">
                    <td colspan="6" class="px-4 py-10 text-center text-[#6B7E8E] text-xs">
                      <i class="ti ti-receipt-off text-2xl block mb-2 opacity-40"></i>No deposits yet
                    </td>
                  </tr>
                  <tr v-for="txn in history.data" :key="txn.id"
                      class="transition"
                      :class="{
                        'opacity-50 bg-[rgba(226,99,90,0.03)]'   : txn.is_reversed,
                        'bg-amber-500/5 border-l-2 border-amber-500/40' : txn.status === 'pending_approval',
                        'bg-red-500/5'                            : txn.status === 'rejected',
                        'hover:bg-[rgba(255,255,255,0.02)]'       : txn.status === 'completed' && !txn.is_reversed,
                      }">

                    <!-- Reference -->
                    <td class="px-3 py-3 font-mono text-xs">
                      <span :class="txn.reversal_of ? 'text-[#6B7E8E]' : 'text-[#C9A84C]'">{{ txn.reference }}</span>
                      <span v-if="txn.is_reversed"  class="block mt-0.5 px-1.5 py-0.5 rounded text-[9px] bg-red-500/10 text-red-400 border border-red-500/20 w-fit">REVERSED</span>
                      <span v-if="txn.reversal_of"  class="block mt-0.5 px-1.5 py-0.5 rounded text-[9px] bg-blue-500/10 text-blue-400 border border-blue-500/20 w-fit">REVERSAL</span>
                    </td>

                    <!-- Account / Customer -->
                    <td class="px-3 py-3">
                      <p class="text-[#F0EBE1] text-xs font-medium">{{ txn.customer_name }}</p>
                      <p class="text-[#6B7E8E] font-mono text-[10px]">{{ txn.account_number }}</p>
                    </td>

                    <!-- Status badge -->
                    <td class="px-3 py-3 text-center">
                      <span v-if="txn.status === 'pending_approval'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/25 whitespace-nowrap">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                        Awaiting Approval
                      </span>
                      <span v-else-if="txn.status === 'rejected'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-500/15 text-red-400 border border-red-500/25 whitespace-nowrap">
                        <i class="ti ti-x text-[10px]"></i> Rejected
                      </span>
                      <span v-else-if="txn.status === 'cancelled'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/15 text-slate-400 border border-slate-500/25 whitespace-nowrap">
                        <i class="ti ti-ban text-[10px]"></i> Cancelled
                      </span>
                      <span v-else-if="txn.is_reversed"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/15 text-slate-400 border border-slate-500/25 whitespace-nowrap">
                        <i class="ti ti-arrow-back-up text-[10px]"></i> Reversed
                      </span>
                      <span v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 whitespace-nowrap">
                        <i class="ti ti-check text-[10px]"></i> Completed
                      </span>
                    </td>

                    <!-- Amount -->
                    <td class="px-3 py-3 text-right font-mono font-bold text-sm"
                        :class="{
                          'text-[#6B7E8E] line-through' : txn.is_reversed,
                          'text-amber-400'              : txn.status === 'pending_approval',
                          'text-red-400 line-through'   : txn.status === 'rejected',
                          'text-[#4CAF7D]'              : txn.status === 'completed' && !txn.is_reversed,
                        }">
                      +${{ fmt(txn.amount) }}
                    </td>

                    <!-- Balance After (blank for pending/rejected) -->
                    <td class="px-3 py-3 text-right font-mono text-[#A9B8C6] text-xs hidden md:table-cell">
                      <span v-if="txn.status === 'completed' && !txn.is_reversed">${{ fmt(txn.balance_after) }}</span>
                      <span v-else class="text-[#4a5f70]">—</span>
                    </td>

                    <!-- Date -->
                    <td class="px-3 py-3 text-[#6B7E8E] text-[11px] hidden lg:table-cell whitespace-nowrap">
                      {{ new Date(txn.created_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) }}
                      <span class="text-[10px] block">{{ new Date(txn.created_at).toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' }) }}</span>
                    </td>

                    <!-- Actions (no print/reverse for pending or rejected) -->
                    <td class="px-3 py-3">
                      <div class="flex items-center justify-center gap-1.5">
                        <a v-if="txn.status === 'completed'"
                           :href="route('staff.transaction.receipt', txn.id)"
                           target="_blank" title="Open PDF receipt"
                           class="w-7 h-7 rounded-lg flex items-center justify-center bg-[rgba(201,168,76,0.08)] text-[#C9A84C] border border-[rgba(201,168,76,0.2)] hover:bg-[rgba(201,168,76,0.18)] transition text-xs">
                          <i class="ti ti-receipt"></i>
                        </a>
                        <button v-if="txn.can_reverse && txn.status === 'completed'" @click="confirmReverse(txn)" title="Reverse transaction"
                                class="w-7 h-7 rounded-lg flex items-center justify-center bg-[rgba(226,99,90,0.08)] text-[#E2635A] border border-[rgba(226,99,90,0.2)] hover:bg-[rgba(226,99,90,0.18)] transition text-xs">
                          <i class="ti ti-arrow-back-up"></i>
                        </button>
                        <button v-if="txn.status === 'pending_approval'"
                                @click="confirmCancel(txn)" title="Cancel this request"
                                class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold
                                       bg-slate-600/40 text-slate-300 border border-slate-500/30
                                       hover:bg-red-500/15 hover:text-red-400 hover:border-red-500/30 transition">
                          <i class="ti ti-x text-[10px]"></i> Cancel
                        </button>
                        <span v-if="txn.status === 'rejected'" class="text-[10px] text-red-400/60 italic" :title="txn.rejection_reason">denied</span>
                        <span v-if="txn.status === 'cancelled'" class="text-[10px] text-slate-500 italic">cancelled</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="history.last_page > 1" class="flex justify-end gap-1 mt-4">
              <Link v-for="link in history.links" :key="link.label"
                    :href="link.url ?? '#'" v-html="link.label"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-xs border transition"
                    :class="link.active ? 'bg-[#4CAF7D] text-white border-[#4CAF7D] font-bold'
                                       : link.url ? 'text-[#A9B8C6] border-[rgba(255,255,255,0.08)] hover:bg-white/5'
                                                  : 'text-[#4a5f70] border-transparent cursor-default'" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Cancel Pending Modal ── -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="cancelling" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="dismissCancel">
          <div class="bg-[#112236] border border-slate-600/40 rounded-2xl p-6 max-w-md w-full shadow-2xl">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-full bg-slate-600/30 flex items-center justify-center text-slate-300 text-xl">
                <i class="ti ti-ban"></i>
              </div>
              <div>
                <h3 class="font-semibold text-[#F0EBE1]">Cancel Approval Request?</h3>
                <p class="text-[11px] text-[#6B7E8E]">No funds have been moved — this is safe to cancel</p>
              </div>
            </div>
            <div class="bg-[rgba(255,255,255,0.03)] rounded-xl p-4 mb-5 space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-[#6B7E8E]">Reference</span><span class="font-mono text-[#C9A84C]">{{ cancelling.reference }}</span></div>
              <div class="flex justify-between"><span class="text-[#6B7E8E]">Customer</span><span class="text-[#F0EBE1]">{{ cancelling.customer_name }}</span></div>
              <div class="flex justify-between"><span class="text-[#6B7E8E]">Amount</span><span class="font-bold text-amber-400">${{ fmt(cancelling.amount) }}</span></div>
            </div>
            <p class="text-[12px] text-[#A9B8C6] mb-5">
              The supervisor will no longer see this request. You can submit a new transaction anytime.
            </p>
            <div class="flex gap-3">
              <button @click="dismissCancel" class="flex-1 py-2.5 rounded-xl border border-[rgba(255,255,255,0.1)] text-[#A9B8C6] text-sm hover:bg-white/5 transition">Keep Pending</button>
              <button @click="submitCancel" :disabled="cancelForm.processing"
                      class="flex-1 py-2.5 rounded-xl bg-slate-600 hover:bg-slate-500 text-white text-sm font-semibold transition disabled:opacity-50">
                {{ cancelForm.processing ? 'Cancelling…' : 'Yes, Cancel Request' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- ── Reversal Confirmation Modal ── -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="reversing" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="cancelReverse">
          <div class="bg-[#112236] border border-[rgba(226,99,90,0.3)] rounded-2xl p-6 max-w-md w-full shadow-2xl">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-full bg-[#E2635A]/10 flex items-center justify-center text-[#E2635A] text-xl">
                <i class="ti ti-arrow-back-up"></i>
              </div>
              <div>
                <h3 class="font-semibold text-[#F0EBE1]">Reverse Deposit?</h3>
                <p class="text-[11px] text-[#6B7E8E]">This action cannot be undone</p>
              </div>
            </div>
            <div class="bg-[rgba(255,255,255,0.03)] rounded-xl p-4 mb-5 space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-[#6B7E8E]">Reference</span><span class="font-mono text-[#C9A84C]">{{ reversing.reference }}</span></div>
              <div class="flex justify-between"><span class="text-[#6B7E8E]">Customer</span><span class="text-[#F0EBE1]">{{ reversing.customer_name }}</span></div>
              <div class="flex justify-between"><span class="text-[#6B7E8E]">Account</span><span class="font-mono text-[#A9B8C6]">{{ reversing.account_number }}</span></div>
              <div class="flex justify-between"><span class="text-[#6B7E8E]">Amount</span><span class="font-bold text-[#E2635A]">${{ fmt(reversing.amount) }}</span></div>
            </div>
            <p class="text-[12px] text-[#A9B8C6] mb-5">
              Reversing this deposit will <strong class="text-[#E2635A]">withdraw ${{ fmt(reversing.amount) }}</strong> from the customer's account and return it to the vault.
            </p>
            <div class="flex gap-3">
              <button @click="cancelReverse" class="flex-1 py-2.5 rounded-xl border border-[rgba(255,255,255,0.1)] text-[#A9B8C6] text-sm hover:bg-white/5 transition">Cancel</button>
              <button @click="submitReverse" :disabled="reverseForm.processing"
                      class="flex-1 py-2.5 rounded-xl bg-[#E2635A] hover:bg-[#c9544c] text-white text-sm font-semibold transition disabled:opacity-50">
                {{ reverseForm.processing ? 'Reversing…' : 'Confirm Reversal' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

  </AuthenticatedLayout>

  <!-- ── Receipt / Pending Modal ── -->
  <ReceiptModal
    :show="showModal"
    type="deposit"
    :receipt="receipt"
    :pending-receipt="pending_receipt"
    @close="showModal = false"
  />
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.35s ease; }
.slide-down-enter-from, .slide-down-leave-to       { opacity: 0; transform: translateY(-12px); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }
/* All receipts print in their own window — no page-level print CSS needed */
</style>
