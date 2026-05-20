<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    transaction:  { type: Object, default: () => ({}) },
    entries:      { type: Array,  default: () => [] },
    is_balanced:  { type: Boolean, default: true },
    total_debit:  { type: Number, default: 0 },
    total_credit: { type: Number, default: 0 },
})

/* ── Helpers ── */
const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const TYPE_CFG = {
    deposit:    { icon: 'ti-arrow-down-circle', color: '#4CAF7D', bg: 'bg-emerald-900/30 border-emerald-700/30 text-emerald-400' },
    withdrawal: { icon: 'ti-arrow-up-circle',   color: '#E8A830', bg: 'bg-amber-900/30 border-amber-700/30 text-amber-400'   },
    transfer:   { icon: 'ti-arrows-right-left', color: '#8A63D2', bg: 'bg-indigo-900/30 border-indigo-700/30 text-indigo-400' },
}
const STATUS_CFG = {
    completed:        { cls: 'bg-emerald-900/40 text-emerald-400 border-emerald-700/30', label: 'Completed' },
    pending_approval: { cls: 'bg-amber-900/40 text-amber-400 border-amber-700/30',       label: 'Pending Approval' },
    rejected:         { cls: 'bg-red-900/40 text-red-400 border-red-700/30',             label: 'Rejected' },
    cancelled:        { cls: 'bg-slate-700/60 text-slate-400 border-slate-600/30',       label: 'Cancelled' },
}
const ACCT_CFG = {
    vault:    { icon: 'ti-building-bank', cls: 'text-amber-400',  bg: 'bg-amber-900/20 border-amber-600/30'  },
    customer: { icon: 'ti-user-circle',   cls: 'text-sky-400',    bg: 'bg-sky-900/20 border-sky-600/30'    },
    internal: { icon: 'ti-database',      cls: 'text-slate-400',  bg: 'bg-slate-800/60 border-slate-600/30' },
}

const typeCfg   = (t) => TYPE_CFG[t]   ?? TYPE_CFG.deposit
const statusCfg = (s) => STATUS_CFG[s] ?? { cls: 'bg-slate-700/60 text-slate-400 border-slate-600/30', label: s }
const acctCfg   = (t) => ACCT_CFG[t]  ?? ACCT_CFG.internal

const debitEntry  = props.entries.find(e => e.entry_type === 'debit')
const creditEntry = props.entries.find(e => e.entry_type === 'credit')

/* ── Print ── */
function buildPrintHtml() {
    const txn    = props.transaction
    const debit  = debitEntry
    const credit = creditEntry
    const color  = typeCfg(txn.type).color

    const entryRow = (e, side) => e ? `
      <div class="entry-card ${side}">
        <div class="side-label">${side === 'dr' ? 'DEBIT (DR)' : 'CREDIT (CR)'}</div>
        <div class="acct-type">${e.account_label}</div>
        <div class="acct-num">${e.account_number}</div>
        <div class="acct-name">${e.account_holder}</div>
        <div class="entry-amt ${side}">${side === 'dr' ? '−' : '+'}$${fmt(e.amount)}</div>
        <div class="bal-after">Balance after: $${fmt(e.balance_after)}</div>
      </div>` : '<div class="entry-card empty">—</div>'

    const remarksHtml = txn.description && !['Cash Deposit','Cash Withdrawal','Inter-Account Transfer','Deposit','Withdrawal','Transfer'].includes(txn.description)
        ? `<div class="info-row"><span class="lbl">Remarks</span><span class="val italic">"${txn.description}"</span></div>`
        : ''

    return `<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>GL Journal Entry — ${txn.reference}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Georgia',serif;background:#fff;color:#1a1a1a;max-width:680px;margin:0 auto;padding:24px}
.hdr{background:#0B1929;color:#fff;padding:18px 20px;border-radius:8px 8px 0 0;margin-bottom:0}
.hdr-top{display:flex;justify-content:space-between;align-items:flex-start}
.bank-name{font-size:16px;font-weight:700;letter-spacing:.04em}
.bank-sub{font-size:8px;text-transform:uppercase;letter-spacing:.12em;color:#7a9ab5;margin-top:2px}
.ref{font-family:monospace;font-size:13px;color:${color};font-weight:700}
.type-badge{font-size:9px;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.5);margin-top:4px}
.meta{background:#f8f9fa;border:1px solid #e8e8e8;border-top:none;padding:12px 16px;display:flex;flex-wrap:wrap;gap:8px 24px}
.info-row{display:flex;flex-direction:column;gap:1px}
.lbl{font-size:8px;color:#999;text-transform:uppercase;letter-spacing:.06em}
.val{font-size:11px;color:#333;font-weight:600}
.val.italic{font-style:italic;font-weight:normal;color:#666}
.section-title{font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:#999;font-weight:700;padding:14px 16px 8px;background:#fff;border-top:2px solid #eee}
.entries{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:0 16px 16px;background:#fff}
.entry-card{border:1px solid #e0e0e0;border-radius:6px;padding:12px;background:#fafafa}
.entry-card.dr{border-color:#f5c6c6;background:#fff9f9}
.entry-card.cr{border-color:#c6e8d5;background:#f9fff9}
.side-label{font-size:10px;font-weight:900;letter-spacing:.12em;margin-bottom:8px;padding:3px 8px;border-radius:3px;display:inline-block}
.dr .side-label{background:#fde8e8;color:#c0392b}
.cr .side-label{background:#e8f5ec;color:#1a7a45}
.acct-type{font-size:8px;color:#888;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.acct-num{font-family:monospace;font-size:11px;color:#444;font-weight:600}
.acct-name{font-size:12px;color:#1a1a1a;font-weight:700;margin-top:3px}
.entry-amt{font-size:18px;font-weight:700;font-family:monospace;margin-top:10px}
.entry-amt.dr{color:#c0392b}.entry-amt.cr{color:#1a7a45}
.bal-after{font-size:9px;color:#888;margin-top:3px}
.totals{background:#0B1929;color:#fff;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;border-radius:0 0 8px 8px}
.total-col{text-align:center}
.total-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.1em;color:#7a9ab5}
.total-val{font-size:15px;font-weight:700;font-family:monospace;margin-top:2px}
.total-val.dr{color:#f08080}.total-val.cr{color:#80d4a8}
.balanced{font-size:9px;font-weight:700;letter-spacing:.08em;padding:4px 12px;border-radius:12px;background:rgba(76,175,125,.2);color:#4CAF7D}
@media print{body{max-width:100%}}
</style></head><body>
<div class="hdr">
  <div class="hdr-top">
    <div>
      <div class="bank-name">Gobaad Bank</div>
      <div class="bank-sub">GL Journal Entry Voucher</div>
    </div>
    <div style="text-align:right">
      <div class="ref">${txn.reference}</div>
      <div class="type-badge">${txn.type_label}</div>
    </div>
  </div>
</div>
<div class="meta">
  <div class="info-row"><span class="lbl">Date &amp; Time</span><span class="val">${txn.created_at}</span></div>
  <div class="info-row"><span class="lbl">Status</span><span class="val">${txn.status?.replace('_',' ').toUpperCase()}</span></div>
  <div class="info-row"><span class="lbl">Teller</span><span class="val">${txn.teller}</span></div>
  <div class="info-row"><span class="lbl">Role</span><span class="val">${txn.teller_role}</span></div>
  <div class="info-row"><span class="lbl">Branch</span><span class="val">${txn.branch}</span></div>
  ${remarksHtml}
</div>
<div class="section-title">Double-Entry GL Posting</div>
<div class="entries">
  ${entryRow(debit,  'dr')}
  ${entryRow(credit, 'cr')}
</div>
<div class="totals">
  <div class="total-col">
    <div class="total-lbl">Total Debits</div>
    <div class="total-val dr">$${fmt(props.total_debit)}</div>
  </div>
  <div class="balanced">${props.is_balanced ? '✓ BALANCED' : '⚠ OUT OF BALANCE'}</div>
  <div class="total-col">
    <div class="total-lbl">Total Credits</div>
    <div class="total-val cr">$${fmt(props.total_credit)}</div>
  </div>
</div>
</body></html>`
}

function printEntry() {
    const w = window.open('', '_blank', 'width=740,height=640,scrollbars=no,menubar=no,toolbar=no')
    if (!w) return
    w.document.write(buildPrintHtml())
    w.document.close()
    w.focus()
    setTimeout(() => w.print(), 400)
}
</script>

<template>
  <Head :title="`GL — ${transaction.reference}`" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-5xl mx-auto px-5 py-8 space-y-6">

        <!-- ── Back + header ── -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <Link :href="route('staff.accounting.ledger')"
                  class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition">
              <i class="ti ti-arrow-left text-base"></i>
            </Link>
            <div class="w-10 h-10 rounded-xl border flex items-center justify-center"
                 :class="typeCfg(transaction.type).bg">
              <i class="ti text-xl" :class="typeCfg(transaction.type).icon"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">GL Journal Entry</h1>
              <p class="text-xs text-slate-400">Double-entry posting breakdown</p>
            </div>
          </div>

          <button @click="printEntry"
                  class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.25)] text-[#C9A84C] text-sm font-semibold hover:bg-[rgba(201,168,76,0.2)] transition">
            <i class="ti ti-printer text-base"></i> Print Voucher
          </button>
        </div>

        <!-- ── Transaction meta card ── -->
        <div class="bg-[#0d1f35] border border-white/5 rounded-2xl overflow-hidden">

          <!-- Header bar -->
          <div class="bg-slate-900/60 px-6 py-4 border-b border-white/5 flex flex-wrap items-center gap-3">
            <span class="font-mono text-[#C9A84C] font-black text-lg tracking-wider">{{ transaction.reference }}</span>
            <span class="text-[10px] px-2.5 py-1 rounded-full border font-bold uppercase"
                  :class="typeCfg(transaction.type).bg">
              {{ transaction.type_label }}
            </span>
            <span class="text-[10px] px-2.5 py-1 rounded-full border font-semibold uppercase"
                  :class="statusCfg(transaction.status).cls">
              {{ statusCfg(transaction.status).label }}
            </span>
            <span v-if="transaction.is_reversed"
                  class="text-[10px] px-2.5 py-1 rounded-full bg-red-900/50 border border-red-700/40 text-red-400 font-bold uppercase">
              REVERSED
            </span>
            <span v-if="transaction.is_flagged"
                  class="text-[10px] px-2.5 py-1 rounded-full bg-orange-900/50 border border-orange-700/40 text-orange-400 font-bold uppercase">
              AML FLAGGED
            </span>
          </div>

          <!-- Info grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-px bg-white/5">
            <div class="bg-[#0d1f35] px-5 py-4">
              <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">Date &amp; Time</p>
              <p class="text-sm text-slate-200 font-medium">{{ transaction.created_at }}</p>
            </div>
            <div class="bg-[#0d1f35] px-5 py-4">
              <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">Teller</p>
              <p class="text-sm text-slate-200 font-medium">{{ transaction.teller }}</p>
              <p class="text-[10px] text-slate-500 mt-0.5">{{ transaction.teller_role }}</p>
            </div>
            <div class="bg-[#0d1f35] px-5 py-4">
              <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">Branch</p>
              <p class="text-sm text-slate-200 font-medium">{{ transaction.branch }}</p>
            </div>
            <div class="bg-[#0d1f35] px-5 py-4">
              <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">Transaction Amount</p>
              <p class="text-sm font-bold font-mono text-white">${{ fmt(transaction.amount) }}</p>
            </div>
            <div class="bg-[#0d1f35] px-5 py-4">
              <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">Remarks</p>
              <p v-if="transaction.description && !['Cash Deposit','Cash Withdrawal','Inter-Account Transfer','Deposit','Withdrawal','Transfer'].includes(transaction.description)"
                 class="text-sm text-slate-300 italic">{{ transaction.description }}</p>
              <p v-else class="text-sm text-slate-600">—</p>
            </div>
          </div>

          <!-- AML flag reason -->
          <div v-if="transaction.is_flagged && transaction.flag_reason"
               class="px-6 py-3 bg-orange-900/20 border-t border-orange-700/20 flex items-start gap-2 text-sm text-orange-300">
            <i class="ti ti-alert-triangle text-orange-400 mt-0.5 shrink-0"></i>
            <span><strong class="text-orange-200">AML Flag:</strong> {{ transaction.flag_reason }}</span>
          </div>
        </div>

        <!-- ── GL Journal Entry panel ── -->
        <div class="bg-[#0d1f35] border border-white/5 rounded-2xl overflow-hidden">

          <!-- Section header -->
          <div class="px-6 py-4 border-b border-white/5 bg-slate-900/40 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-[rgba(201,168,76,0.1)] flex items-center justify-center">
              <i class="ti ti-bookmarks text-[#C9A84C] text-base"></i>
            </div>
            <div>
              <p class="text-sm font-bold text-white">Double-Entry GL Posting</p>
              <p class="text-[11px] text-slate-400">Every transaction creates exactly one debit and one credit entry</p>
            </div>
          </div>

          <!-- No entries yet -->
          <div v-if="!entries.length" class="px-6 py-12 text-center text-slate-500">
            <i class="ti ti-clock-hour-4 text-3xl block mb-2 opacity-40"></i>
            <p class="text-sm">GL entries have not been posted yet.</p>
            <p class="text-xs mt-1">This transaction is pending supervisor approval.</p>
          </div>

          <!-- T-Account layout (DR | CR) -->
          <div v-else>

            <!-- Column headers -->
            <div class="grid grid-cols-2 border-b border-white/5">
              <div class="px-6 py-3 flex items-center gap-2 bg-red-900/10 border-r border-white/5">
                <span class="text-[10px] font-black uppercase tracking-widest text-red-400 px-2.5 py-1 bg-red-900/40 rounded">DR · Debit</span>
                <span class="text-[10px] text-slate-500">Funds leaving account</span>
              </div>
              <div class="px-6 py-3 flex items-center gap-2 bg-emerald-900/10">
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400 px-2.5 py-1 bg-emerald-900/40 rounded">CR · Credit</span>
                <span class="text-[10px] text-slate-500">Funds entering account</span>
              </div>
            </div>

            <!-- Entry cards -->
            <div class="grid grid-cols-1 md:grid-cols-2">

              <div v-for="entry in entries" :key="entry.id"
                   class="p-6 border-b md:border-b-0 border-white/5"
                   :class="[
                     entry.entry_type === 'debit'
                       ? 'bg-red-950/10 md:border-r border-white/5'
                       : 'bg-emerald-950/10'
                   ]">

                <!-- Account type badge -->
                <div class="flex items-center gap-2 mb-4">
                  <div class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0"
                       :class="acctCfg(entry.account_type).bg">
                    <i class="ti text-base" :class="[acctCfg(entry.account_type).icon, acctCfg(entry.account_type).cls]"></i>
                  </div>
                  <div>
                    <p class="text-[10px] text-slate-400">{{ entry.account_label }}</p>
                    <p class="font-mono text-[11px] text-slate-300 font-semibold">{{ entry.account_number }}</p>
                  </div>
                </div>

                <!-- Account holder name -->
                <p class="text-base font-bold text-white mb-1">{{ entry.account_holder }}</p>

                <!-- Divider -->
                <div class="border-t border-white/8 my-4"></div>

                <!-- Amount -->
                <div class="flex items-baseline gap-2">
                  <span class="text-[11px] font-semibold uppercase tracking-wide"
                        :class="entry.entry_type === 'debit' ? 'text-red-400' : 'text-emerald-400'">
                    {{ entry.entry_type === 'debit' ? 'Debit' : 'Credit' }}
                  </span>
                  <span class="text-2xl font-black font-mono"
                        :class="entry.entry_type === 'debit' ? 'text-red-300' : 'text-emerald-300'">
                    {{ entry.entry_type === 'debit' ? '−' : '+' }}${{ fmt(entry.amount) }}
                  </span>
                </div>

                <!-- Balance after -->
                <div class="mt-3 flex items-center gap-2">
                  <i class="ti ti-trending-up text-slate-500 text-xs"></i>
                  <span class="text-[11px] text-slate-500">Account balance after posting:</span>
                  <span class="font-mono text-sm text-slate-300 font-semibold">${{ fmt(entry.balance_after) }}</span>
                </div>

              </div>
            </div>

            <!-- Balance verification bar -->
            <div class="px-6 py-4 border-t border-white/5 bg-slate-900/50 flex flex-wrap items-center justify-between gap-4">

              <!-- Debit total -->
              <div class="text-center">
                <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">Total Debits</p>
                <p class="font-mono font-bold text-red-300 text-lg">${{ fmt(total_debit) }}</p>
              </div>

              <!-- Balance status -->
              <div class="flex items-center gap-2 px-5 py-2.5 rounded-xl border"
                   :class="is_balanced
                     ? 'bg-emerald-900/30 border-emerald-700/30 text-emerald-300'
                     : 'bg-red-900/30 border-red-700/30 text-red-300'">
                <i class="ti text-lg" :class="is_balanced ? 'ti-circle-check' : 'ti-alert-triangle'"></i>
                <div>
                  <p class="text-xs font-black uppercase tracking-widest">
                    {{ is_balanced ? 'Balanced' : 'Out of Balance' }}
                  </p>
                  <p class="text-[10px] opacity-70">
                    {{ is_balanced ? 'Debits = Credits ✓' : 'Posting error detected' }}
                  </p>
                </div>
              </div>

              <!-- Credit total -->
              <div class="text-center">
                <p class="text-[10px] text-slate-500 uppercase tracking-wide mb-1">Total Credits</p>
                <p class="font-mono font-bold text-emerald-300 text-lg">${{ fmt(total_credit) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Accounting principle note ── -->
        <div class="bg-slate-900/40 border border-slate-800 rounded-xl px-5 py-4 flex items-start gap-3 text-xs text-slate-500">
          <i class="ti ti-info-circle text-slate-600 text-base shrink-0 mt-0.5"></i>
          <div class="space-y-0.5">
            <p>
              <span class="text-slate-400 font-semibold">Double-Entry Principle:</span>
              Every financial transaction affects at least two accounts. The total debits must always equal the total credits.
            </p>
            <p>
              <span class="text-slate-400 font-semibold">Vault (Cash Asset):</span> Debited when cash leaves the vault (deposit), credited when cash returns (withdrawal).
              <span class="text-slate-400 font-semibold ml-2">Customer Account (Liability):</span> Credited when balance increases (deposit), debited when balance decreases (withdrawal).
            </p>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
