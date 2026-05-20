<script setup>
import { computed } from 'vue'

const props = defineProps({
    show:           { type: Boolean, default: false },
    /** 'deposit' | 'withdrawal' | 'transfer' */
    type:           { type: String,  default: 'deposit' },
    /** Session receipt — fully-posted transaction */
    receipt:        { type: Object,  default: null },
    /** Session pending_receipt — over-limit, awaiting supervisor */
    pendingReceipt: { type: Object,  default: null },
})
const emit = defineEmits(['close'])

const fmt = n => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })

/* ─── type config ─── */
const TYPE_CFG = {
    deposit:   {
        label:    'Cash Deposit',
        slipTitle:'Cash Deposit Receipt',
        sign:     '+',
        amtLabel: 'Amount Deposited',
        amtColor: '#2e7d52',
        hdrBg:    'bg-emerald-900/40',
        hdrBorder:'border-emerald-700/25',
        iconCls:  'ti-arrow-down-circle text-emerald-300',
        btnCls:   'bg-emerald-600 hover:bg-emerald-500',
        accentHex:'#2e7d52',
    },
    withdrawal: {
        label:    'Cash Withdrawal',
        slipTitle:'Cash Withdrawal Receipt',
        sign:     '-',
        amtLabel: 'Amount Withdrawn',
        amtColor: '#b8860b',
        hdrBg:    'bg-amber-900/40',
        hdrBorder:'border-amber-700/25',
        iconCls:  'ti-arrow-up-circle text-amber-300',
        btnCls:   'bg-amber-600 hover:bg-amber-500',
        accentHex:'#b8860b',
    },
    transfer:   {
        label:    'Inter-Account Transfer',
        slipTitle:'Transfer Receipt',
        sign:     '',
        amtLabel: 'Amount Transferred',
        amtColor: '#5b4fcf',
        hdrBg:    'bg-indigo-900/40',
        hdrBorder:'border-indigo-700/25',
        iconCls:  'ti-arrows-right-left text-indigo-300',
        btnCls:   'bg-indigo-600 hover:bg-indigo-500',
        accentHex:'#5b4fcf',
    },
}
const cfg = computed(() => TYPE_CFG[props.type] ?? TYPE_CFG.deposit)

/* ─── slip rows (deposit/withdrawal only) ─── */
const slipRows = computed(() => {
    if (!props.receipt || props.type === 'transfer') return []
    const r = props.receipt
    const rows = [
        ['Reference',    r.reference,      true ],
        ['Customer',     r.customer_name,  false],
        ['Account',      r.account_number, true ],
        ['Date',         r.processed_at,   false],
        ['Teller',       r.teller,         false],
        ['Balance After','$' + fmt(r.balance_after), true],
    ]
    if (r.remarks) rows.push(['Remarks', r.remarks, false])
    return rows
})

/* ─── shared popup CSS ─── */
const sharedCss = color => `
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Georgia',serif;background:#fff;color:#222;width:340px;margin:0 auto;padding:0}
.hdr{background:#0B1929;color:#fff;text-align:center;padding:18px 16px 14px}
.hdr img{height:42px;margin:0 auto 7px;display:block}
.hdr h1{font-size:16px;letter-spacing:.04em;font-weight:700}
.hdr sub{font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:#7a9ab5;display:block;margin-top:3px}
.body{padding:14px 14px 6px}
.row{display:flex;justify-content:space-between;align-items:flex-start;padding:6px 0;border-bottom:1px solid #f0f0f0;font-size:12px;gap:8px}
.row:last-child{border-bottom:none}
.lbl{color:#888;font-size:11px;flex-shrink:0;white-space:nowrap}
.val{font-weight:600;text-align:right;word-break:break-word}
.mono{font-family:monospace;font-size:11px}
.remarks-val{font-size:10.5px;font-weight:normal;font-style:italic;color:#555;text-align:right}
.divider{border-top:2px dashed #e0e0e0;margin:8px 0}
.big{font-size:20px;font-weight:700;color:${color}}
.amt-row{display:flex;justify-content:space-between;align-items:baseline;padding:8px 0 4px}
.amt-lbl{font-size:13px;font-weight:600;color:#444}
.sig-section{margin:10px 0 0;padding-top:12px;border-top:1px dashed #d0d0d0}
.sig-title{font-size:8.5px;color:#aaa;text-transform:uppercase;letter-spacing:.1em;text-align:center;margin-bottom:14px}
.sig-block{margin-bottom:20px}
.sig-line{height:32px;border-bottom:1.5px solid #777;margin-bottom:5px}
.sig-label{font-size:9px;color:#888;letter-spacing:.03em}
.sig-label strong{color:#555;font-weight:600}
.ftr{background:#0B1929;text-align:center;padding:9px 14px}
.ftr p{color:#4a6070;font-size:9px;text-transform:uppercase;letter-spacing:.09em}
.ftr p+p{margin-top:2px;color:#3a5060}
@media print{body{width:100%}}`

/* ─── popup window helper ─── */
function openWin(html) {
    const w = window.open('', '_blank', 'width=420,height=720,scrollbars=no,menubar=no,toolbar=no')
    if (!w) return
    w.document.write(html)
    w.document.close()
    w.focus()
    setTimeout(() => w.print(), 400)
}

/* ─── Standard (deposit / withdrawal) popup HTML ─── */
function buildStdHtml(r) {
    const c   = cfg.value
    const rows = [
        ['Reference',       r.reference,      true ],
        ['Customer',        r.customer_name,  false],
        ['Account',         r.account_number, true ],
        ['Date &amp; Time', r.processed_at,   false],
        ['Teller',          r.teller,         false],
        ['Balance After',   '$' + fmt(r.balance_after), true],
    ]

    const remarkHtml = r.remarks
        ? `<div class="row"><span class="lbl">Remarks</span><span class="val remarks-val">${r.remarks}</span></div>`
        : ''

    const sigHtml = `
<div class="sig-section">
  <p class="sig-title">Authorized Signatures</p>
  <div class="sig-block">
    <div class="sig-line"></div>
    <p class="sig-label"><strong>Teller:</strong> ${r.teller ?? '—'}</p>
  </div>
  <div class="sig-block">
    <div class="sig-line"></div>
    <p class="sig-label"><strong>Customer:</strong> ${r.customer_name ?? '—'}</p>
  </div>
</div>`

    return `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${c.slipTitle}</title>
<style>${sharedCss(c.amtColor)}</style></head><body>
<div class="hdr">
  <img src="/images/MAin Logo.png" alt="" onerror="this.style.display='none'">
  <h1>Gobaad Bank</h1><sub>${c.slipTitle}</sub>
</div>
<div class="body">
${rows.map(([l,v,m]) => `<div class="row"><span class="lbl">${l}</span><span class="val${m?' mono':''}">${v ?? '—'}</span></div>`).join('')}
${remarkHtml}
<div class="divider"></div>
<div class="amt-row"><span class="amt-lbl">${c.amtLabel}</span><span class="big">${c.sign}$${fmt(r.amount)}</span></div>
${sigHtml}
</div>
<div class="ftr"><p>Gobaad Bank — Official Receipt</p><p>Keep this receipt for your records.</p></div>
</body></html>`
}

/* ─── Transfer popup HTML ─── */
function buildTransferHtml(r) {
    const remarkHtml = r.remarks
        ? `<div class="row"><span class="lbl">Remarks</span><span class="val remarks-val">${r.remarks}</span></div>`
        : ''

    const sigHtml = `
<div class="sig-section">
  <p class="sig-title">Authorized Signatures</p>
  <div class="sig-block">
    <div class="sig-line"></div>
    <p class="sig-label"><strong>Teller:</strong> ${r.teller ?? '—'}</p>
  </div>
  <div class="sig-block">
    <div class="sig-line"></div>
    <p class="sig-label"><strong>Customer:</strong> ${r.from_customer ?? '—'}</p>
  </div>
</div>`

    return `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Transfer Receipt</title>
<style>${sharedCss('#5b4fcf')}
.route{display:flex;border:1px solid #eee;border-radius:6px;overflow:hidden;margin:8px 0}
.acct{flex:1;padding:8px}.acct .nm{font-size:12px;font-weight:700;color:#222}
.acct .no{font-family:monospace;font-size:10px;color:#666;margin-top:1px}
.acct.r{text-align:right;background:#f0eef8}
.arr{width:26px;display:flex;align-items:center;justify-content:center;background:#ece7f6;color:#5b4fcf;font-size:14px;font-weight:700}
</style></head><body>
<div class="hdr">
  <img src="/images/MAin Logo.png" alt="" onerror="this.style.display='none'">
  <h1>Gobaad Bank</h1><sub>Inter-Account Transfer Receipt</sub>
</div>
<div class="body">
<div class="row"><span class="lbl">Reference</span><span class="val mono">${r.reference}</span></div>
<div class="row"><span class="lbl">Date &amp; Time</span><span class="val" style="font-size:11px">${r.processed_at}</span></div>
<div class="row"><span class="lbl">Teller</span><span class="val">${r.teller ?? '—'}</span></div>
${remarkHtml}
<div style="font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:#888;font-weight:700;margin:10px 0 4px">Transfer Route</div>
<div class="route">
  <div class="acct"><div class="nm">${r.from_customer}</div><div class="no">${r.from_account_number}</div></div>
  <div class="arr">→</div>
  <div class="acct r"><div class="nm">${r.to_customer}</div><div class="no">${r.to_account_number}</div></div>
</div>
<div class="divider"></div>
<div class="amt-row"><span class="amt-lbl">Amount Transferred</span><span class="big">$${fmt(r.amount)}</span></div>
${sigHtml}
</div>
<div class="ftr"><p>Gobaad Bank — Official Receipt</p><p>Keep this receipt for your records.</p></div>
</body></html>`
}

function printNow() {
    if (!props.receipt) return
    openWin(props.type === 'transfer' ? buildTransferHtml(props.receipt) : buildStdHtml(props.receipt))
}

/* ─── slip row style helper (avoids escaped quotes in template) ─── */
function rowStyle(label, isMono) {
    const base = 'font-weight:600;text-align:right;word-break:break-word;'
    if (isMono)          return base + 'font-family:monospace;font-size:10px;'
    if (label === 'Remarks') return 'font-style:italic;color:#555;font-size:10px;text-align:right;word-break:break-word;font-weight:normal;'
    return base + 'font-size:11px;'
}
</script>

<template>
    <Teleport to="body">
        <Transition name="rcpt-modal">
            <div v-if="show && (receipt || pendingReceipt)"
                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
                 @click.self="emit('close')">

                <!-- ══════════ SUCCESS RECEIPT ══════════ -->
                <div v-if="receipt"
                     class="w-full max-w-sm bg-[#0d1f30] rounded-2xl shadow-2xl overflow-hidden border border-white/10">

                    <!-- Header -->
                    <div class="px-5 py-4 border-b flex items-center justify-between"
                         :class="[cfg.hdrBg, cfg.hdrBorder]">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center shrink-0">
                                <i class="ti ti-check text-white text-sm font-bold"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-white text-sm">Transaction Successful</p>
                                <p class="font-mono text-[10px] text-white/45 mt-0.5">{{ receipt.reference }}</p>
                            </div>
                        </div>
                        <button @click="emit('close')"
                                class="text-white/35 hover:text-white transition p-1.5 rounded-lg hover:bg-white/10 ml-2 shrink-0">
                            <i class="ti ti-x text-base"></i>
                        </button>
                    </div>

                    <!-- Receipt slip preview -->
                    <div class="px-5 pt-4 pb-3 max-h-[70vh] overflow-y-auto">
                        <div class="bg-white rounded-xl overflow-hidden shadow-inner"
                             style="font-family: Georgia, 'Times New Roman', serif; font-size: 11px; color: #1a1a1a;">

                            <!-- Slip header -->
                            <div style="background:#0B1929;color:#fff;text-align:center;padding:14px 16px 10px">
                                <img src="/images/MAin Logo.png" style="height:32px;margin:0 auto 6px;display:block" alt=""
                                     @error="e => e.target.style.display='none'">
                                <p style="font-size:14px;font-weight:700;letter-spacing:.04em">Gobaad Bank</p>
                                <p style="font-size:8px;text-transform:uppercase;letter-spacing:.12em;color:#7a9ab5;margin-top:2px">{{ cfg.label }}</p>
                            </div>

                            <!-- Standard rows (deposit / withdrawal) -->
                            <div v-if="type !== 'transfer'" style="padding:10px 14px 2px">
                                <div v-for="([l, v, m]) in slipRows" :key="l"
                                     style="display:flex;justify-content:space-between;align-items:flex-start;padding:5px 0;border-bottom:1px solid #f2f2f2;gap:8px">
                                    <span style="color:#888;font-size:10px;flex-shrink:0;white-space:nowrap">{{ l }}</span>
                                    <span :style="rowStyle(l, m)">{{ v }}</span>
                                </div>
                            </div>

                            <!-- Transfer route rows -->
                            <div v-else style="padding:10px 14px 2px">
                                <div style="display:flex;justify-content:space-between;align-items:baseline;padding:5px 0;border-bottom:1px solid #f2f2f2">
                                    <span style="color:#888;font-size:10px">Reference</span>
                                    <span style="font-family:monospace;font-size:10px;font-weight:600">{{ receipt.reference }}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:baseline;padding:5px 0;border-bottom:1px solid #f2f2f2">
                                    <span style="color:#888;font-size:10px">Date</span>
                                    <span style="font-size:10px;font-weight:600">{{ receipt.processed_at }}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:baseline;padding:5px 0;border-bottom:1px solid #f2f2f2">
                                    <span style="color:#888;font-size:10px">Teller</span>
                                    <span style="font-size:10px;font-weight:600">{{ receipt.teller }}</span>
                                </div>
                                <!-- Remarks (transfer) -->
                                <div v-if="receipt.remarks" style="display:flex;justify-content:space-between;align-items:flex-start;padding:5px 0;border-bottom:1px solid #f2f2f2;gap:8px">
                                    <span style="color:#888;font-size:10px;flex-shrink:0">Remarks</span>
                                    <span style="font-size:10px;font-style:italic;color:#555;text-align:right;word-break:break-word">{{ receipt.remarks }}</span>
                                </div>
                                <!-- Route visual -->
                                <p style="font-size:9px;text-transform:uppercase;letter-spacing:.07em;color:#888;font-weight:700;margin:8px 0 3px">Transfer Route</p>
                                <div style="display:flex;border:1px solid #eee;border-radius:6px;overflow:hidden;margin-bottom:4px">
                                    <div style="flex:1;padding:7px 8px;background:#f9f9f9">
                                        <div style="font-size:11px;font-weight:700;color:#222">{{ receipt.from_customer }}</div>
                                        <div style="font-family:monospace;font-size:9px;color:#666;margin-top:1px">{{ receipt.from_account_number }}</div>
                                    </div>
                                    <div style="width:24px;display:flex;align-items:center;justify-content:center;background:#ece7f6;color:#5b4fcf;font-size:13px;font-weight:700">→</div>
                                    <div style="flex:1;padding:7px 8px;background:#f0eef8;text-align:right">
                                        <div style="font-size:11px;font-weight:700;color:#222">{{ receipt.to_customer }}</div>
                                        <div style="font-family:monospace;font-size:9px;color:#666;margin-top:1px">{{ receipt.to_account_number }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount row -->
                            <div style="margin:0 14px;border-top:2px dashed #e8e8e8;padding:8px 0 6px;display:flex;align-items:baseline;justify-content:space-between">
                                <span style="font-size:11px;font-weight:700;color:#555">{{ cfg.amtLabel }}</span>
                                <span style="font-size:19px;font-weight:700" :style="{ color: cfg.amtColor }">
                                    {{ cfg.sign }}${{ fmt(receipt.amount) }}
                                </span>
                            </div>

                            <!-- Signature section -->
                            <div style="margin:0 14px 14px;padding-top:10px;border-top:1px dashed #d0d0d0">
                                <p style="font-size:8.5px;color:#bbb;text-transform:uppercase;letter-spacing:.1em;text-align:center;margin-bottom:12px">Authorized Signatures</p>
                                <!-- Teller -->
                                <div style="margin-bottom:16px">
                                    <div style="height:28px;border-bottom:1.5px solid #ccc;margin-bottom:5px"></div>
                                    <p style="font-size:9px;color:#999">
                                        <span style="font-weight:600;color:#777">Teller:</span>
                                        {{ receipt.teller ?? '—' }}
                                    </p>
                                </div>
                                <!-- Customer -->
                                <div style="margin-bottom:4px">
                                    <div style="height:28px;border-bottom:1.5px solid #ccc;margin-bottom:5px"></div>
                                    <p style="font-size:9px;color:#999">
                                        <span style="font-weight:600;color:#777">Customer:</span>
                                        {{ type === 'transfer' ? receipt.from_customer : receipt.customer_name ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Slip footer -->
                            <div style="background:#0B1929;text-align:center;padding:8px 14px">
                                <p style="font-size:7px;color:#4a6070;text-transform:uppercase;letter-spacing:.09em">Gobaad Bank — Official Receipt</p>
                                <p style="font-size:7px;color:#3a5060;margin-top:2px">Keep this receipt for your records.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-5 pb-5 pt-1 space-y-2">
                        <!-- Row 1: quick popup print + full PDF -->
                        <div class="flex gap-2.5">
                            <button @click="printNow"
                                    class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-white text-sm font-semibold transition-colors"
                                    :class="cfg.btnCls">
                                <i class="ti ti-printer"></i> Quick Print
                            </button>
                            <a v-if="receipt.transaction_id"
                               :href="route('staff.transaction.receipt', receipt.transaction_id)"
                               target="_blank"
                               class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-slate-600 hover:bg-slate-500 text-white text-sm font-semibold transition-colors">
                                <i class="ti ti-file-type-pdf"></i> Full PDF Receipt
                            </a>
                        </div>
                        <!-- Row 2: done -->
                        <button @click="emit('close')"
                                class="w-full py-2.5 rounded-xl bg-slate-700/80 hover:bg-slate-700 text-slate-200 text-sm font-medium transition-colors">
                            Done
                        </button>
                    </div>
                </div>

                <!-- ══════════ PENDING RECEIPT ══════════ -->
                <div v-else-if="pendingReceipt"
                     class="w-full max-w-sm bg-[#1a1505] rounded-2xl shadow-2xl overflow-hidden border border-amber-600/25">

                    <!-- Header -->
                    <div class="px-5 py-4 border-b border-amber-700/25 flex items-center justify-between bg-amber-900/35">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-500/25 flex items-center justify-center shrink-0">
                                <i class="ti ti-clock-hour-4 text-amber-400 text-base"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-amber-200 text-sm">Sent for Approval</p>
                                <p class="text-[10px] text-amber-400/55 mt-0.5">Awaiting supervisor action</p>
                            </div>
                        </div>
                        <button @click="emit('close')"
                                class="text-amber-400/35 hover:text-amber-300 transition p-1.5 rounded-lg hover:bg-amber-500/10 ml-2 shrink-0">
                            <i class="ti ti-x text-base"></i>
                        </button>
                    </div>

                    <!-- Details -->
                    <div class="px-5 py-5 space-y-3.5">

                        <div class="bg-amber-900/25 border border-amber-700/25 rounded-xl px-4 py-3.5 space-y-2.5">
                            <div class="flex justify-between items-center">
                                <span class="text-amber-400/55 text-xs">Reference</span>
                                <span class="font-mono font-bold text-amber-200 text-xs">{{ pendingReceipt.reference }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-amber-400/55 text-xs">Type</span>
                                <span class="text-amber-100 text-xs">{{ pendingReceipt.type }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-amber-400/55 text-xs">Customer</span>
                                <span class="text-white font-medium text-xs">{{ pendingReceipt.customer }}</span>
                            </div>
                            <div v-if="pendingReceipt.to_customer" class="flex justify-between items-center">
                                <span class="text-amber-400/55 text-xs">To</span>
                                <span class="text-white font-medium text-xs">{{ pendingReceipt.to_customer }}</span>
                            </div>
                            <div v-if="pendingReceipt.remarks" class="flex justify-between items-start gap-4">
                                <span class="text-amber-400/55 text-xs shrink-0">Remarks</span>
                                <span class="text-amber-200/70 text-xs italic text-right">{{ pendingReceipt.remarks }}</span>
                            </div>
                            <div class="pt-2 border-t border-amber-700/20 flex justify-between items-baseline">
                                <span class="text-amber-400/55 text-xs">Amount Requested</span>
                                <span class="font-mono font-bold text-amber-300 text-xl">${{ fmt(pendingReceipt.amount) }}</span>
                            </div>
                        </div>

                        <div class="bg-amber-900/15 border border-amber-700/20 rounded-xl px-4 py-3 text-xs text-amber-300/65 leading-relaxed">
                            <i class="ti ti-info-circle mr-1.5 text-amber-400/50"></i>
                            Amount exceeds your
                            <strong class="text-amber-200">${{ fmt(pendingReceipt.limit) }}</strong> limit.
                            No funds have been moved. The supervisor will approve or reject this request.
                        </div>

                        <!-- Animated waiting indicator -->
                        <div class="flex items-center justify-center gap-3 py-1">
                            <div class="flex gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400/70 animate-bounce" style="animation-delay:0ms"></span>
                                <span class="w-2 h-2 rounded-full bg-amber-400/70 animate-bounce" style="animation-delay:120ms"></span>
                                <span class="w-2 h-2 rounded-full bg-amber-400/70 animate-bounce" style="animation-delay:240ms"></span>
                            </div>
                            <span class="text-xs text-amber-400/60">Waiting for supervisor…</span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="px-5 pb-5">
                        <button @click="emit('close')"
                                class="w-full py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                            <i class="ti ti-check"></i> Got it — Continue
                        </button>
                    </div>
                </div>

            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.rcpt-modal-enter-active, .rcpt-modal-leave-active {
    transition: opacity .22s ease, transform .22s ease;
}
.rcpt-modal-enter-from, .rcpt-modal-leave-to {
    opacity: 0;
    transform: scale(.93) translateY(8px);
}
</style>
