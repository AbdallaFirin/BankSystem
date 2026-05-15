<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage, Link, router } from '@inertiajs/vue3'

const props = defineProps({
    counts:         Object,
    system_balance: Number,
    balance_label:  { type: String, default: 'System Balance' },
    active_alloc:   { type: Object, default: null },
    filters:        Object,
})

const page = usePage()

/* ── Helpers ─────────────────────────────────────── */
const fmt = n =>
    '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const fmtDate = d =>
    d ? new Date(d).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : '—'

/* ── Form ────────────────────────────────────────── */
const form = useForm({
    session_type: 'closing',
    notes: '',
    denominations: [
        { denomination: 100,  label: '$100',  type: 'Bill', quantity: 0 },
        { denomination: 50,   label: '$50',   type: 'Bill', quantity: 0 },
        { denomination: 20,   label: '$20',   type: 'Bill', quantity: 0 },
        { denomination: 10,   label: '$10',   type: 'Bill', quantity: 0 },
        { denomination: 5,    label: '$5',    type: 'Bill', quantity: 0 },
        { denomination: 1,    label: '$1',    type: 'Bill', quantity: 0 },
        { denomination: 0.50, label: '50¢',   type: 'Coin', quantity: 0 },
        { denomination: 0.25, label: '25¢',   type: 'Coin', quantity: 0 },
        { denomination: 0.10, label: '10¢',   type: 'Coin', quantity: 0 },
        { denomination: 0.05, label: '5¢',    type: 'Coin', quantity: 0 },
        { denomination: 0.01, label: '1¢',    type: 'Coin', quantity: 0 },
    ],
})

const physicalTotal = computed(() =>
    form.denominations.reduce((s, d) => s + d.denomination * (Number(d.quantity) || 0), 0)
)

const difference = computed(() => physicalTotal.value - props.system_balance)

const diffStatus = computed(() => {
    const d = difference.value
    if (Math.abs(d) < 0.01) return 'balanced'
    return d > 0 ? 'overage' : 'shortage'
})

const submit = () => {
    form.post(route('staff.teller.cash-count.store'), { preserveScroll: true })
}

const resetForm = () => {
    form.session_type = 'closing'
    form.notes = ''
    form.denominations.forEach(d => { d.quantity = 0 })
    form.clearErrors()
}

/* ── Date filter ─────────────────────────────────── */
const filterForm = ref({ ...props.filters })
const applyFilter = () => {
    router.get(route('staff.teller.cash-count.index'), filterForm.value, { preserveState: true })
}
const clearFilter = () => {
    filterForm.value = { date_from: '', date_to: '' }
    router.get(route('staff.teller.cash-count.index'), {})
}

/* ── Print receipt (standalone window) ──────────── */
const printCount = count => {
    const w = window.open('', '_blank', 'width=540,height=720,toolbar=0,menubar=0,scrollbars=1')
    w.document.write(buildPrintHtml(count))
    w.document.close()
    setTimeout(() => w.print(), 400)
}

const buildPrintHtml = count => {
    const statusColor = { balanced: '#1a7a45', overage: '#b8760a', shortage: '#b32020' }[count.status] || '#333'
    const statusLabel = { balanced: 'BALANCED ✓', overage: 'OVERAGE', shortage: 'SHORTAGE' }[count.status] || count.status

    const denomRows = (count.denominations ?? []).map(d => {
        const label = d.denomination >= 1
            ? `$${d.denomination % 1 === 0 ? parseInt(d.denomination) : d.denomination} Bill`
            : `${Math.round(d.denomination * 100)}¢ Coin`
        return `<tr>
          <td>${label}</td>
          <td style="text-align:right">${d.quantity}</td>
          <td style="text-align:right">$${Number(d.subtotal).toFixed(2)}</td>
        </tr>`
    }).join('')

    const diffSign  = count.difference >= 0 ? '+' : ''
    const diffColor = { balanced: '#1a7a45', overage: '#b8760a', shortage: '#b32020' }[count.status] || '#333'

    return `<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Arial,sans-serif;font-size:12px;color:#1a1a1a;padding:24px}
  .hd{text-align:center;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #0F1C2E}
  .hd h2{font-size:16px;color:#0F1C2E;margin-bottom:3px}
  .hd .sub{font-size:11px;color:#555}
  .badge{display:inline-block;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:bold;
         background:${statusColor}22;color:${statusColor};border:1px solid ${statusColor}44}
  table{width:100%;border-collapse:collapse;margin:12px 0}
  .meta td{padding:4px 0;font-size:11px}
  .meta td:first-child{color:#555;width:110px}
  .denom thead th{background:#0F1C2E;color:#E6F1FB;padding:6px 8px;font-size:10px;text-align:left}
  .denom thead th:not(:first-child){text-align:right}
  .denom tbody td{padding:5px 8px;border-bottom:1px solid #eee;font-size:11px}
  .denom tbody td:not(:first-child){text-align:right}
  .denom tbody tr:nth-child(even) td{background:#fafafa}
  .denom tfoot td{padding:6px 8px;font-weight:bold;border-top:2px solid #0F1C2E;font-size:11px}
  .denom tfoot td:last-child{text-align:right}
  .recon td{padding:5px 8px;font-size:11px;border-bottom:1px solid #eee}
  .recon td:last-child{text-align:right;font-family:monospace}
  .recon .diff-row td{font-weight:bold;color:${diffColor}}
  .notes{margin-top:12px;font-size:10px;color:#555;background:#f5f5f5;padding:8px 10px;border-left:3px solid #1D6FA4}
  .footer{margin-top:24px;padding-top:10px;border-top:1px solid #ddd;font-size:9px;color:#888;text-align:center}
</style>
</head><body>
<div class="hd">
  <h2>Gobaad Bank — Cash Count Sheet</h2>
  <div class="sub">Count #${count.id} &nbsp;&bull;&nbsp; ${(count.session_type || '').charAt(0).toUpperCase() + (count.session_type || '').slice(1)} Count</div>
</div>

<table class="meta">
  <tr><td>Date &amp; Time</td><td><strong>${new Date(count.counted_at).toLocaleString()}</strong></td></tr>
  <tr><td>Teller</td><td>${count.staff?.full_name ?? '—'}</td></tr>
  <tr><td>Branch</td><td>${count.branch?.branch_name ?? '—'}</td></tr>
  <tr><td>Status</td><td><span class="badge">${statusLabel}</span></td></tr>
</table>

<table class="denom">
  <thead><tr><th>Denomination</th><th>Qty</th><th>Subtotal</th></tr></thead>
  <tbody>${denomRows || '<tr><td colspan="3" style="text-align:center;color:#888;padding:10px">—</td></tr>'}</tbody>
  <tfoot><tr><td colspan="2">Physical Total</td><td>$${Number(count.physical_total).toFixed(2)}</td></tr></tfoot>
</table>

<table class="recon">
  <tr><td>Physical Count</td><td>$${Number(count.physical_total).toFixed(2)}</td></tr>
  <tr><td>System / Vault Balance</td><td>$${Number(count.system_balance).toFixed(2)}</td></tr>
  <tr class="diff-row"><td><strong>Difference</strong></td><td>${diffSign}$${Math.abs(count.difference).toFixed(2)}</td></tr>
</table>

${count.notes ? `<div class="notes"><strong>Notes:</strong> ${count.notes}</div>` : ''}

<div class="footer">
  Gobaad Bank &bull; Official Cash Count Record &bull; ${new Date(count.counted_at).toLocaleDateString()}
</div>
</body></html>`
}
</script>

<template>
  <div class="min-h-screen bg-[#070F1A] text-white">
    <div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

      <!-- ── Page header ── -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white">Cash Count</h1>
          <p class="text-sm text-slate-400 mt-1">Record denomination counts and reconcile against the vault balance</p>
        </div>
        <div class="flex items-center gap-3">
          <Link :href="route('staff.teller.my-till')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
                       text-sm hover:bg-slate-800 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            My Till
          </Link>
          <Link :href="route('staff.teller.cash-count.approvals')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
                       text-sm hover:bg-slate-800 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Approvals
          </Link>
          <div class="bg-slate-800 border border-slate-700 rounded-xl px-5 py-3 text-right">
            <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">{{ balance_label }}</div>
            <div class="text-xl font-bold text-emerald-400 font-mono">{{ fmt(system_balance) }}</div>
          </div>
        </div>
      </div>

      <!-- ── Flash ── -->
      <div v-if="page.props.flash?.success"
           class="bg-emerald-900/40 border border-emerald-500/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.flash?.error"
           class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-xl px-5 py-3 text-sm">
        {{ page.props.flash.error }}
      </div>

      <!-- ── Till notice ── -->
      <div v-if="props.active_alloc"
           class="flex items-start gap-3 bg-blue-950/50 border border-blue-700/40 rounded-xl px-5 py-3 text-sm text-blue-200">
        <svg class="w-4 h-4 mt-0.5 shrink-0 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <span>
          <strong class="text-blue-100">Till is open</strong> — Your cash count compares against your
          <strong>personal till balance</strong> (not the shared vault).
          Opening float: <strong>${{ Number(props.active_alloc.amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</strong>.
          <Link :href="route('staff.teller.my-till')" class="underline hover:text-blue-100 ml-1">View My Till →</Link>
        </span>
      </div>
      <div v-else
           class="flex items-start gap-3 bg-slate-800/50 border border-slate-700/40 rounded-xl px-5 py-3 text-sm text-slate-400">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>No active till allocation. Cash count compares against the <strong class="text-slate-300">branch vault</strong>.</span>
      </div>

      <!-- ══════════════════════════════════════════════════════
           MAIN GRID: form (left) + summary panel (right)
           ══════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ── Denomination table ── -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
            <h2 class="font-semibold text-white">Denomination Entry</h2>
            <span class="text-xs text-slate-500">Enter the quantity of each note/coin you physically count</span>
          </div>

          <form @submit.prevent="submit">
            <table class="w-full">
              <thead>
                <tr class="bg-slate-800">
                  <th class="px-5 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Denomination</th>
                  <th class="px-4 py-3 text-center text-xs text-slate-400 font-medium uppercase tracking-wide">Type</th>
                  <th class="px-4 py-3 text-center text-xs text-slate-400 font-medium uppercase tracking-wide w-32">Quantity</th>
                  <th class="px-5 py-3 text-right text-xs text-slate-400 font-medium uppercase tracking-wide">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <!-- Bills separator -->
                <tr class="bg-slate-800/30">
                  <td colspan="4" class="px-5 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-widest">
                    Bills
                  </td>
                </tr>
                <template v-for="(row, i) in form.denominations" :key="i">
                  <!-- Coins separator -->
                  <tr v-if="i === 6" class="bg-slate-800/30">
                    <td colspan="4" class="px-5 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-widest">
                      Coins
                    </td>
                  </tr>
                  <tr class="hover:bg-slate-800/40 transition-colors">
                    <!-- Denomination label -->
                    <td class="px-5 py-3">
                      <span class="inline-flex items-center gap-2">
                        <span class="w-14 text-center bg-slate-800 border border-slate-600 rounded-lg py-1 text-sm font-bold font-mono text-white">
                          {{ row.label }}
                        </span>
                        <span class="text-slate-400 text-sm">note</span>
                      </span>
                    </td>
                    <!-- Type -->
                    <td class="px-4 py-3 text-center">
                      <span class="text-xs px-2 py-0.5 rounded-full"
                            :class="row.type === 'Bill'
                              ? 'bg-blue-900/40 text-blue-400 border border-blue-700/30'
                              : 'bg-amber-900/30 text-amber-400 border border-amber-700/30'">
                        {{ row.type }}
                      </span>
                    </td>
                    <!-- Quantity input -->
                    <td class="px-4 py-3">
                      <input
                        v-model.number="row.quantity"
                        type="number" min="0" step="1"
                        class="w-full text-center bg-slate-800 border border-slate-600 text-white rounded-lg
                               py-1.5 px-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500
                               focus:border-transparent transition"
                        placeholder="0"
                        @wheel.prevent
                      />
                    </td>
                    <!-- Subtotal -->
                    <td class="px-5 py-3 text-right font-mono text-sm"
                        :class="row.quantity > 0 ? 'text-emerald-400' : 'text-slate-600'">
                      {{ fmt(row.denomination * (row.quantity || 0)) }}
                    </td>
                  </tr>
                </template>
              </tbody>
              <tfoot>
                <tr class="bg-slate-800 border-t-2 border-slate-600">
                  <td colspan="3" class="px-5 py-3 font-semibold text-white text-sm">Physical Total</td>
                  <td class="px-5 py-3 text-right font-bold font-mono text-lg text-white">
                    {{ fmt(physicalTotal) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </form>
        </div>

        <!-- ── Right panel: summary + controls ── -->
        <div class="space-y-4">

          <!-- Reconciliation summary -->
          <div class="bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-700">
              <h2 class="font-semibold text-white">Reconciliation</h2>
            </div>
            <div class="p-5 space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400">Physical Count</span>
                <span class="font-mono font-semibold text-white">{{ fmt(physicalTotal) }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400">System / Vault</span>
                <span class="font-mono font-semibold text-white">{{ fmt(system_balance) }}</span>
              </div>
              <div class="border-t border-slate-700 pt-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-300">Difference</span>
                <span class="font-mono font-bold text-base"
                      :class="{
                        'text-emerald-400': diffStatus === 'balanced',
                        'text-amber-400':   diffStatus === 'overage',
                        'text-red-400':     diffStatus === 'shortage',
                      }">
                  {{ difference >= 0 ? '+' : '' }}{{ fmt(difference) }}
                </span>
              </div>
              <!-- Status badge -->
              <div class="rounded-xl py-2.5 text-center text-sm font-bold tracking-wide"
                   :class="{
                     'bg-emerald-900/40 text-emerald-400 border border-emerald-700/30': diffStatus === 'balanced',
                     'bg-amber-900/30   text-amber-400   border border-amber-700/30':   diffStatus === 'overage',
                     'bg-red-900/30     text-red-400     border border-red-700/30':      diffStatus === 'shortage',
                   }">
                <span v-if="diffStatus === 'balanced'">✓ Balanced</span>
                <span v-else-if="diffStatus === 'overage'">▲ Overage</span>
                <span v-else>▼ Shortage</span>
              </div>
            </div>
          </div>

          <!-- Session type + notes + submit -->
          <div class="bg-slate-900 border border-slate-700 rounded-2xl p-5 space-y-4">
            <div>
              <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Session Type</label>
              <select v-model="form.session_type"
                      class="w-full bg-slate-800 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm
                             focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                <option value="opening">Opening Count</option>
                <option value="midday">Midday Count</option>
                <option value="closing">Closing Count</option>
              </select>
              <p v-if="form.errors.session_type" class="text-red-400 text-xs mt-1">{{ form.errors.session_type }}</p>
            </div>
            <div>
              <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Notes <span class="normal-case">(optional)</span></label>
              <textarea v-model="form.notes" rows="3"
                        class="w-full bg-slate-800 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm
                               resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="Any remarks about discrepancies…"></textarea>
              <p v-if="form.errors.notes" class="text-red-400 text-xs mt-1">{{ form.errors.notes }}</p>
            </div>
            <div class="flex gap-3">
              <button type="button" @click="resetForm"
                      class="flex-1 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300 text-sm
                             hover:bg-slate-800 transition">
                Clear
              </button>
              <button @click="submit" :disabled="form.processing"
                      class="flex-[2] px-4 py-2.5 rounded-xl text-sm font-semibold transition
                             disabled:opacity-50 disabled:cursor-not-allowed"
                      :class="diffStatus === 'balanced'
                        ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                        : 'bg-blue-600 hover:bg-blue-700 text-white'">
                <span v-if="form.processing">Saving…</span>
                <span v-else>Record Count</span>
              </button>
            </div>
          </div>

        </div><!-- /right panel -->
      </div>

      <!-- ══════════════════════════════════════════════════════
           HISTORY TABLE
           ══════════════════════════════════════════════════════ -->
      <div class="bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-700 flex flex-wrap items-center justify-between gap-3">
          <h2 class="font-semibold text-white">Cash Count History</h2>

          <!-- Date filter -->
          <div class="flex items-center gap-2 flex-wrap">
            <input v-model="filterForm.date_from" type="date"
                   class="bg-slate-800 border border-slate-700 text-slate-300 text-xs rounded-lg px-3 py-1.5
                          focus:outline-none focus:ring-1 focus:ring-blue-500" />
            <span class="text-slate-600 text-xs">to</span>
            <input v-model="filterForm.date_to" type="date"
                   class="bg-slate-800 border border-slate-700 text-slate-300 text-xs rounded-lg px-3 py-1.5
                          focus:outline-none focus:ring-1 focus:ring-blue-500" />
            <button @click="applyFilter"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition">
              Filter
            </button>
            <button v-if="filters?.date_from || filters?.date_to" @click="clearFilter"
                    class="px-3 py-1.5 border border-slate-600 text-slate-400 text-xs rounded-lg hover:bg-slate-800 transition">
              Clear
            </button>
          </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full min-w-[700px]">
            <thead>
              <tr class="bg-slate-800">
                <th class="px-5 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">#</th>
                <th class="px-4 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Date & Time</th>
                <th class="px-4 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Session</th>
                <th class="px-4 py-3 text-left text-xs text-slate-400 font-medium uppercase tracking-wide">Teller</th>
                <th class="px-4 py-3 text-right text-xs text-slate-400 font-medium uppercase tracking-wide">Physical</th>
                <th class="px-4 py-3 text-right text-xs text-slate-400 font-medium uppercase tracking-wide">System</th>
                <th class="px-4 py-3 text-right text-xs text-slate-400 font-medium uppercase tracking-wide">Difference</th>
                <th class="px-4 py-3 text-center text-xs text-slate-400 font-medium uppercase tracking-wide">Result</th>
                <th class="px-4 py-3 text-center text-xs text-slate-400 font-medium uppercase tracking-wide">Approval</th>
                <th class="px-5 py-3 text-center text-xs text-slate-400 font-medium uppercase tracking-wide">Print</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-if="!counts?.data?.length">
                <td colspan="10" class="px-5 py-10 text-center text-slate-500 text-sm">No cash counts recorded yet.</td>
              </tr>
              <tr v-for="c in counts?.data" :key="c.id"
                  class="hover:bg-slate-800/50 transition-colors">
                <td class="px-5 py-3 text-slate-500 text-xs font-mono">{{ c.id }}</td>
                <td class="px-4 py-3 text-sm text-slate-300">{{ fmtDate(c.counted_at) }}</td>
                <td class="px-4 py-3">
                  <span class="capitalize text-xs px-2 py-0.5 rounded-full bg-slate-800 border border-slate-600 text-slate-300">
                    {{ c.session_type }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-slate-300">{{ c.staff?.full_name ?? '—' }}</td>
                <td class="px-4 py-3 text-right font-mono text-sm text-white">{{ fmt(c.physical_total) }}</td>
                <td class="px-4 py-3 text-right font-mono text-sm text-slate-400">{{ fmt(c.system_balance) }}</td>
                <td class="px-4 py-3 text-right font-mono text-sm font-semibold"
                    :class="{
                      'text-emerald-400': c.status === 'balanced',
                      'text-amber-400':   c.status === 'overage',
                      'text-red-400':     c.status === 'shortage',
                    }">
                  {{ c.difference >= 0 ? '+' : '' }}{{ fmt(c.difference) }}
                </td>
                <!-- Result badge -->
                <td class="px-4 py-3 text-center">
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                        :class="{
                          'bg-emerald-900/40 text-emerald-400 border border-emerald-700/30': c.status === 'balanced',
                          'bg-amber-900/30   text-amber-400   border border-amber-700/30':   c.status === 'overage',
                          'bg-red-900/30     text-red-400     border border-red-700/30':      c.status === 'shortage',
                        }">
                    {{ c.status.charAt(0).toUpperCase() + c.status.slice(1) }}
                  </span>
                </td>
                <!-- Approval badge -->
                <td class="px-4 py-3 text-center">
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                        :class="{
                          'bg-yellow-900/30 text-yellow-400 border border-yellow-700/30': c.approval_status === 'pending',
                          'bg-emerald-900/40 text-emerald-400 border border-emerald-700/30': c.approval_status === 'verified',
                          'bg-red-900/30 text-red-400 border border-red-700/30': c.approval_status === 'flagged',
                        }">
                    {{ c.approval_status === 'pending' ? '⏳ Pending' : c.approval_status === 'verified' ? '✓ Verified' : '⚑ Flagged' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <button @click="printCount(c)"
                          class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition"
                          title="Print receipt">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden divide-y divide-slate-800">
          <div v-if="!counts?.data?.length" class="px-5 py-10 text-center text-slate-500 text-sm">
            No cash counts recorded yet.
          </div>
          <div v-for="c in counts?.data" :key="c.id" class="px-5 py-4 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-500 font-mono">#{{ c.id }} &bull; {{ fmtDate(c.counted_at) }}</span>
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize"
                    :class="{
                      'bg-emerald-900/40 text-emerald-400': c.status === 'balanced',
                      'bg-amber-900/30   text-amber-400':   c.status === 'overage',
                      'bg-red-900/30     text-red-400':     c.status === 'shortage',
                    }">
                {{ c.status }}
              </span>
            </div>
            <div class="text-sm text-white font-semibold capitalize">{{ c.session_type }} Count</div>
            <div class="text-xs text-slate-400">{{ c.staff?.full_name ?? '—' }}</div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-slate-400">Physical: <span class="font-mono text-white">{{ fmt(c.physical_total) }}</span></span>
              <span class="font-mono font-bold"
                    :class="{
                      'text-emerald-400': c.status === 'balanced',
                      'text-amber-400':   c.status === 'overage',
                      'text-red-400':     c.status === 'shortage',
                    }">
                {{ c.difference >= 0 ? '+' : '' }}{{ fmt(c.difference) }}
              </span>
            </div>
            <button @click="printCount(c)"
                    class="flex items-center gap-1.5 text-xs text-blue-400 hover:text-blue-300 transition">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
              </svg>
              Print Receipt
            </button>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="counts?.last_page > 1"
             class="px-6 py-4 border-t border-slate-700 flex items-center justify-between text-sm">
          <span class="text-slate-400 text-xs">
            Showing {{ counts.from }}–{{ counts.to }} of {{ counts.total }}
          </span>
          <div class="flex gap-2">
            <Link v-if="counts.prev_page_url" :href="counts.prev_page_url"
                  class="px-3 py-1.5 rounded-lg border border-slate-600 text-slate-300 text-xs hover:bg-slate-800 transition">
              ← Prev
            </Link>
            <Link v-if="counts.next_page_url" :href="counts.next_page_url"
                  class="px-3 py-1.5 rounded-lg border border-slate-600 text-slate-300 text-xs hover:bg-slate-800 transition">
              Next →
            </Link>
          </div>
        </div>

      </div><!-- /history -->
    </div>
  </div>
</template>
