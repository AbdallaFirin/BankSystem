<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    history:      { type: Array, default: () => [] },
});

const page = usePage();

/* ── Active tab ── */
const activeTab = ref('queue');

/* ── Selection (bulk actions) ── */
const selectedIds = ref(new Set());

const allSelected = computed(() =>
    props.transactions.length > 0 && selectedIds.value.size === props.transactions.length
);

function toggleSelect(id) {
    const s = new Set(selectedIds.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selectedIds.value = s;
}

function toggleSelectAll() {
    if (allSelected.value) {
        selectedIds.value = new Set();
    } else {
        selectedIds.value = new Set(props.transactions.map(t => t.id));
    }
}

function clearSelection() { selectedIds.value = new Set(); }

/* ── Single Approve modal ── */
const approveModal = ref(false);
const approveTx    = ref(null);
const approveNote  = ref('');

function openApprove(tx) {
    approveTx.value  = tx;
    approveNote.value = '';
    approveModal.value = true;
}
function closeApprove() { approveModal.value = false; }

function submitApprove() {
    router.post(route('staff.approvals.approve', approveTx.value.id),
        { note: approveNote.value },
        { preserveScroll: true, onSuccess: closeApprove }
    );
}

/* ── Single Reject modal ── */
const rejectModal  = ref(false);
const rejectTx     = ref(null);
const rejectReason = ref('');

function openReject(tx) {
    rejectTx.value    = tx;
    rejectReason.value = '';
    rejectModal.value  = true;
}
function closeReject() { rejectModal.value = false; }

function submitReject() {
    router.post(route('staff.approvals.reject', rejectTx.value.id),
        { reason: rejectReason.value || 'Rejected by supervisor.' },
        { preserveScroll: true, onSuccess: closeReject }
    );
}

/* ── Bulk Approve modal ── */
const bulkApproveModal = ref(false);
const bulkApproveNote  = ref('');

function openBulkApprove() { bulkApproveNote.value = ''; bulkApproveModal.value = true; }
function closeBulkApprove() { bulkApproveModal.value = false; }

function submitBulkApprove() {
    router.post(route('staff.approvals.bulk-approve'),
        { ids: [...selectedIds.value], note: bulkApproveNote.value },
        { preserveScroll: true, onSuccess: () => { closeBulkApprove(); clearSelection(); } }
    );
}

/* ── Bulk Reject modal ── */
const bulkRejectModal  = ref(false);
const bulkRejectReason = ref('');

function openBulkReject() { bulkRejectReason.value = ''; bulkRejectModal.value = true; }
function closeBulkReject() { bulkRejectModal.value = false; }

function submitBulkReject() {
    router.post(route('staff.approvals.bulk-reject'),
        { ids: [...selectedIds.value], reason: bulkRejectReason.value },
        { preserveScroll: true, onSuccess: () => { closeBulkReject(); clearSelection(); } }
    );
}

/* ── History note expand ── */
const expandedNote = ref(null);
function toggleNote(id) { expandedNote.value = expandedNote.value === id ? null : id; }

/* ── Helpers ── */
const fmt     = n => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtDate = d => d ? new Date(d).toLocaleString('en-US', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—';

const typeLabel = { deposit: 'Deposit', withdrawal: 'Withdrawal', withdraw: 'Withdrawal', transfer: 'Transfer' };
const typeColor = {
    deposit:    'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    withdrawal: 'bg-amber-500/10  text-amber-400  border-amber-500/20',
    withdraw:   'bg-amber-500/10  text-amber-400  border-amber-500/20',
    transfer:   'bg-violet-500/10 text-violet-400 border-violet-500/20',
};
const typeIcon = {
    deposit:    'ti-arrow-down-circle',
    withdrawal: 'ti-arrow-up-circle',
    withdraw:   'ti-arrow-up-circle',
    transfer:   'ti-arrows-right-left',
};
</script>

<template>
  <Head title="Supervisor Approval Queue" />
  <AuthenticatedLayout>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8 space-y-6">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
            <i class="ti ti-checklist text-2xl text-amber-400"></i>
          </div>
          <div>
            <h1 class="text-xl font-bold text-white">Supervisor Approval Queue</h1>
            <p class="text-xs text-slate-400 mt-0.5">Transactions exceeding teller limits waiting for your decision</p>
          </div>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 border border-slate-700 text-sm">
          <span class="w-2 h-2 rounded-full shrink-0"
                :class="transactions.length ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400'"></span>
          <span class="text-slate-300 font-medium">{{ transactions.length }} pending</span>
        </div>
      </div>

      <!-- Flash -->
      <div v-if="page.props.flash?.success"
           class="flex items-center gap-3 bg-emerald-900/30 border border-emerald-600/30 text-emerald-300 rounded-xl px-5 py-3 text-sm">
        <i class="ti ti-circle-check text-lg shrink-0"></i>{{ page.props.flash.success }}
      </div>
      <div v-if="page.props.flash?.error"
           class="flex items-center gap-3 bg-red-900/30 border border-red-600/30 text-red-300 rounded-xl px-5 py-3 text-sm">
        <i class="ti ti-alert-circle text-lg shrink-0"></i>{{ page.props.flash.error }}
      </div>

      <!-- ── Tab switcher ── -->
      <div class="flex gap-1 p-1 bg-slate-800/60 border border-slate-700/60 rounded-xl w-fit">
        <button @click="activeTab = 'queue'; clearSelection()"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition"
                :class="activeTab === 'queue'
                    ? 'bg-amber-500/15 text-amber-300 border border-amber-500/30'
                    : 'text-slate-400 hover:text-slate-200'">
          <i class="ti ti-clock-hour-4"></i>
          Pending Queue
          <span v-if="transactions.length"
                class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white leading-none">
            {{ transactions.length }}
          </span>
        </button>
        <button @click="activeTab = 'history'; clearSelection()"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition"
                :class="activeTab === 'history'
                    ? 'bg-slate-700 text-white border border-slate-600'
                    : 'text-slate-400 hover:text-slate-200'">
          <i class="ti ti-history"></i>
          Decision History
          <span class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-700 text-slate-300 leading-none border border-slate-600">
            {{ history.length }}
          </span>
        </button>
      </div>

      <!-- ══════════════════════════════
           TAB: PENDING QUEUE
      ══════════════════════════════ -->
      <div v-if="activeTab === 'queue'">

        <!-- Empty state -->
        <div v-if="!transactions.length"
             class="bg-slate-900 border border-slate-700/60 rounded-2xl px-6 py-16 text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/10 flex items-center justify-center mb-4">
            <i class="ti ti-circle-check text-3xl text-emerald-400"></i>
          </div>
          <h3 class="text-base font-semibold text-white mb-1">All clear</h3>
          <p class="text-sm text-slate-400">No transactions are waiting for your approval.</p>
        </div>

        <div v-else class="space-y-4">

          <!-- ── Bulk action toolbar ── -->
          <div class="flex items-center justify-between gap-3 bg-slate-900 border border-slate-700/60 rounded-xl px-4 py-3">
            <!-- Select-all toggle -->
            <button @click="toggleSelectAll"
                    class="flex items-center gap-2.5 text-sm font-medium transition"
                    :class="allSelected ? 'text-amber-300' : 'text-slate-400 hover:text-white'">
              <div class="w-4 h-4 rounded border-2 flex items-center justify-center transition"
                   :class="allSelected
                       ? 'bg-amber-500 border-amber-500'
                       : selectedIds.size > 0
                           ? 'border-amber-500/60 bg-amber-500/20'
                           : 'border-slate-600'">
                <i v-if="allSelected" class="ti ti-check text-[10px] text-white"></i>
                <i v-else-if="selectedIds.size > 0" class="ti ti-minus text-[10px] text-amber-400"></i>
              </div>
              {{ allSelected ? 'Deselect all' : `Select all (${transactions.length})` }}
            </button>

            <!-- Bulk action buttons — visible when something is selected -->
            <Transition name="slide-right">
              <div v-if="selectedIds.size > 0" class="flex items-center gap-2">
                <span class="text-xs text-slate-400 mr-1">{{ selectedIds.size }} selected</span>
                <button @click="openBulkApprove"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                               bg-emerald-500/10 border border-emerald-500/25 text-emerald-400
                               hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition">
                  <i class="ti ti-checks"></i> Approve Selected
                </button>
                <button @click="openBulkReject"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                               bg-red-500/10 border border-red-500/25 text-red-400
                               hover:bg-red-500 hover:text-white hover:border-red-500 transition">
                  <i class="ti ti-ban"></i> Reject Selected
                </button>
                <button @click="clearSelection"
                        class="p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-slate-700 transition">
                  <i class="ti ti-x text-sm"></i>
                </button>
              </div>
            </Transition>
          </div>

          <!-- Queue cards -->
          <div v-for="tx in transactions" :key="tx.id"
               class="bg-slate-900 border rounded-2xl overflow-hidden transition"
               :class="selectedIds.has(tx.id)
                   ? 'border-amber-500/50 ring-1 ring-amber-500/20'
                   : 'border-slate-700/60'">

            <!-- Card top bar -->
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800 bg-slate-800/40">
              <div class="flex items-center gap-3">
                <!-- Checkbox -->
                <button @click="toggleSelect(tx.id)"
                        class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition"
                        :class="selectedIds.has(tx.id)
                            ? 'bg-amber-500 border-amber-500'
                            : 'border-slate-600 hover:border-amber-500/60'">
                  <i v-if="selectedIds.has(tx.id)" class="ti ti-check text-[9px] text-white"></i>
                </button>
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border', typeColor[tx.type]]">
                  <i :class="'ti ' + typeIcon[tx.type]" class="text-sm"></i>
                  {{ typeLabel[tx.type] ?? tx.type }}
                </span>
                <span class="font-mono text-xs text-slate-400">{{ tx.reference }}</span>
              </div>
              <span class="text-xs text-slate-500">{{ fmtDate(tx.created_at) }}</span>
            </div>

            <!-- Card body -->
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">

              <!-- Amount -->
              <div class="flex flex-col justify-center">
                <p class="text-[10px] uppercase tracking-widest text-slate-500 mb-1">Amount</p>
                <p class="text-3xl font-bold text-amber-400">${{ fmt(tx.amount) }}</p>
              </div>

              <!-- Account info -->
              <div class="space-y-2">
                <div>
                  <p class="text-[10px] uppercase tracking-widest text-slate-500 mb-0.5">
                    {{ tx.type === 'transfer' ? 'From Account' : 'Customer Account' }}
                  </p>
                  <p class="text-sm font-semibold text-white">{{ tx.customer_name }}</p>
                  <p class="text-xs font-mono text-slate-400">{{ tx.account_number }}</p>
                  <p class="text-xs text-slate-500 mt-0.5">Balance: ${{ fmt(tx.current_balance) }}</p>
                </div>
                <div v-if="tx.type === 'transfer'">
                  <p class="text-[10px] uppercase tracking-widest text-slate-500 mb-0.5">To Account</p>
                  <p class="text-sm font-semibold text-white">{{ tx.to_customer_name }}</p>
                  <p class="text-xs font-mono text-slate-400">{{ tx.to_account_number }}</p>
                </div>
              </div>

              <!-- Teller & actions -->
              <div class="flex flex-col justify-between gap-3">
                <div>
                  <p class="text-[10px] uppercase tracking-widest text-slate-500 mb-0.5">Requested by</p>
                  <p class="text-sm text-white font-medium">{{ tx.initiated_by }}</p>
                  <p class="text-xs text-slate-500">{{ tx.initiator_role }}</p>
                </div>
                <div class="flex gap-2">
                  <button @click="openApprove(tx)"
                          class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl
                                 bg-emerald-500/10 border border-emerald-500/25 text-emerald-400
                                 hover:bg-emerald-500 hover:text-white hover:border-emerald-500
                                 text-sm font-semibold transition">
                    <i class="ti ti-check"></i> Approve
                  </button>
                  <button @click="openReject(tx)"
                          class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl
                                 bg-red-500/10 border border-red-500/25 text-red-400
                                 hover:bg-red-500 hover:text-white hover:border-red-500
                                 text-sm font-semibold transition">
                    <i class="ti ti-x"></i> Reject
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div><!-- end queue tab -->

      <!-- ══════════════════════════════
           TAB: DECISION HISTORY
      ══════════════════════════════ -->
      <div v-if="activeTab === 'history'">

        <div v-if="!history.length"
             class="bg-slate-900 border border-slate-700/60 rounded-2xl px-6 py-16 text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-slate-700/40 flex items-center justify-center mb-4">
            <i class="ti ti-history text-3xl text-slate-500"></i>
          </div>
          <h3 class="text-base font-semibold text-white mb-1">No decisions yet</h3>
          <p class="text-sm text-slate-400">Your approval and rejection history will appear here.</p>
        </div>

        <div v-else class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="font-semibold text-white text-sm">Your Recent Decisions</h2>
            <span class="text-xs text-slate-500">Last {{ history.length }} decisions</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-800/50">
                  <th class="px-4 py-3 text-left text-[10px] uppercase tracking-widest text-amber-500/70">Reference</th>
                  <th class="px-4 py-3 text-left text-[10px] uppercase tracking-widest text-amber-500/70">Type</th>
                  <th class="px-4 py-3 text-left text-[10px] uppercase tracking-widest text-amber-500/70">Customer</th>
                  <th class="px-4 py-3 text-right text-[10px] uppercase tracking-widest text-amber-500/70">Amount</th>
                  <th class="px-4 py-3 text-left text-[10px] uppercase tracking-widest text-amber-500/70">Teller</th>
                  <th class="px-4 py-3 text-center text-[10px] uppercase tracking-widest text-amber-500/70">Decision</th>
                  <th class="px-4 py-3 text-left text-[10px] uppercase tracking-widest text-amber-500/70 hidden lg:table-cell">Decided At</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60">
                <template v-for="h in history" :key="h.id">
                  <tr class="transition hover:bg-slate-800/30"
                      :class="h.decision === 'rejected' ? 'bg-red-500/[0.02]' : ''">

                    <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ h.reference }}</td>

                    <td class="px-4 py-3">
                      <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-semibold border', typeColor[h.type] ?? 'bg-slate-700/30 text-slate-400 border-slate-600/30']">
                        <i :class="'ti ' + (typeIcon[h.type] ?? 'ti-arrows-exchange')" class="text-[10px]"></i>
                        {{ typeLabel[h.type] ?? h.type }}
                      </span>
                    </td>

                    <td class="px-4 py-3">
                      <p class="text-white text-xs font-medium">{{ h.customer_name }}</p>
                      <p class="text-slate-500 font-mono text-[10px]">{{ h.account_number }}</p>
                      <template v-if="h.type === 'transfer'">
                        <p class="text-slate-400 text-[10px] mt-0.5">
                          <i class="ti ti-arrow-right text-[9px]"></i>
                          {{ h.to_customer_name }}
                          <span class="font-mono text-slate-500 ml-1">{{ h.to_account_number }}</span>
                        </p>
                      </template>
                    </td>

                    <td class="px-4 py-3 text-right font-mono font-bold text-sm"
                        :class="h.decision === 'approved' ? 'text-emerald-400' : 'text-red-400 line-through'">
                      ${{ fmt(h.amount) }}
                    </td>

                    <td class="px-4 py-3">
                      <p class="text-slate-300 text-xs">{{ h.teller_name }}</p>
                      <p class="text-slate-500 text-[10px]">{{ h.teller_role }}</p>
                    </td>

                    <!-- Decision badge + note toggle -->
                    <td class="px-4 py-3 text-center">
                      <div class="flex flex-col items-center gap-1">
                        <span v-if="h.decision === 'approved'"
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/25">
                          <i class="ti ti-check"></i> Approved
                        </span>
                        <span v-else
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/15 text-red-400 border border-red-500/25">
                          <i class="ti ti-x"></i> Rejected
                        </span>
                        <!-- Note indicator -->
                        <button v-if="h.decision_note || h.rejection_reason"
                                @click="toggleNote(h.id)"
                                class="text-[9px] text-slate-500 hover:text-slate-300 transition flex items-center gap-0.5">
                          <i class="ti ti-message text-[9px]"></i>
                          {{ expandedNote === h.id ? 'hide note' : 'view note' }}
                        </button>
                      </div>
                    </td>

                    <td class="px-4 py-3 text-slate-500 text-[11px] hidden lg:table-cell whitespace-nowrap">
                      {{ fmtDate(h.decided_at) }}
                      <span class="block text-[10px] text-slate-600 mt-0.5">Submitted: {{ fmtDate(h.submitted_at) }}</span>
                    </td>
                  </tr>

                  <!-- Expanded note row -->
                  <tr v-if="expandedNote === h.id && (h.decision_note || h.rejection_reason)"
                      class="bg-slate-800/40">
                    <td colspan="7" class="px-6 py-3">
                      <div class="flex items-start gap-2">
                        <i class="ti ti-message-circle text-slate-400 text-sm shrink-0 mt-0.5"></i>
                        <div>
                          <p class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold mb-1">
                            {{ h.decision === 'approved' ? 'Approval Note' : 'Rejection Reason' }}
                          </p>
                          <p class="text-sm text-slate-300 leading-relaxed">
                            {{ h.decision_note || h.rejection_reason }}
                          </p>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- end history tab -->

    </div>

    <!-- ════════════════════════════════════════
         MODALS
    ════════════════════════════════════════ -->
    <Teleport to="body">

      <!-- ── Single Approve modal ── -->
      <Transition name="modal">
        <div v-if="approveModal"
             class="fixed inset-0 z-[9995] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click.self="closeApprove">
          <div class="bg-slate-900 border border-emerald-500/25 rounded-2xl w-full max-w-md shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/15 flex items-center justify-center">
                  <i class="ti ti-check text-emerald-400 text-sm"></i>
                </div>
                <h3 class="font-semibold text-white">Approve Transaction</h3>
              </div>
              <button @click="closeApprove" class="text-slate-400 hover:text-white transition">
                <i class="ti ti-x text-lg"></i>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <!-- Summary -->
              <div class="flex items-center justify-between bg-slate-800/60 rounded-xl px-4 py-3">
                <div>
                  <p class="text-xs text-slate-400 mb-0.5 font-mono">{{ approveTx?.reference }}</p>
                  <p class="text-sm text-white font-medium capitalize">{{ approveTx?.type }} — {{ approveTx?.customer_name }}</p>
                </div>
                <p class="text-xl font-bold text-emerald-400">${{ fmt(approveTx?.amount) }}</p>
              </div>
              <p class="text-sm text-slate-400">
                Approving will immediately post the ledger entries and notify the teller.
              </p>
              <!-- Optional note -->
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">
                  Approval note <span class="text-slate-600">(optional)</span>
                </label>
                <textarea v-model="approveNote" rows="2"
                          placeholder="Add a note for the record…"
                          class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40
                                 focus:border-emerald-500/60 transition resize-none"></textarea>
              </div>
              <div class="flex gap-3 pt-1">
                <button @click="closeApprove"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
                               hover:bg-slate-800 text-sm font-medium transition">
                  Cancel
                </button>
                <button @click="submitApprove"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500
                               text-white text-sm font-semibold transition flex items-center justify-center gap-2">
                  <i class="ti ti-check"></i> Confirm Approval
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ── Single Reject modal ── -->
      <Transition name="modal">
        <div v-if="rejectModal"
             class="fixed inset-0 z-[9995] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click.self="closeReject">
          <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-red-500/15 flex items-center justify-center">
                  <i class="ti ti-x text-red-400 text-sm"></i>
                </div>
                <h3 class="font-semibold text-white">Reject Transaction</h3>
              </div>
              <button @click="closeReject" class="text-slate-400 hover:text-white transition">
                <i class="ti ti-x text-lg"></i>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <p class="text-sm text-slate-400">
                Rejecting <span class="font-mono text-amber-300">{{ rejectTx?.reference }}</span>.
                No funds will be moved.
              </p>
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">
                  Reason <span class="text-slate-600">(optional — defaults to "Rejected by supervisor.")</span>
                </label>
                <textarea v-model="rejectReason" rows="3"
                          placeholder="e.g. Customer verification required, exceeds daily limit…"
                          class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500/40
                                 focus:border-red-500/60 transition resize-none"></textarea>
              </div>
              <div class="flex gap-3 pt-1">
                <button @click="closeReject"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
                               hover:bg-slate-800 text-sm font-medium transition">
                  Cancel
                </button>
                <button @click="submitReject"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500
                               text-white text-sm font-semibold transition">
                  Confirm Reject
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ── Bulk Approve modal ── -->
      <Transition name="modal">
        <div v-if="bulkApproveModal"
             class="fixed inset-0 z-[9995] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click.self="closeBulkApprove">
          <div class="bg-slate-900 border border-emerald-500/25 rounded-2xl w-full max-w-md shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/15 flex items-center justify-center">
                  <i class="ti ti-checks text-emerald-400 text-sm"></i>
                </div>
                <h3 class="font-semibold text-white">Bulk Approve</h3>
              </div>
              <button @click="closeBulkApprove" class="text-slate-400 hover:text-white transition">
                <i class="ti ti-x text-lg"></i>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3">
                <i class="ti ti-info-circle text-emerald-400 text-lg shrink-0"></i>
                <p class="text-sm text-emerald-300">
                  You are about to approve <strong>{{ selectedIds.size }} transaction(s)</strong>.
                  Ledger entries will be posted immediately for each.
                </p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">
                  Batch note <span class="text-slate-600">(optional — applies to all)</span>
                </label>
                <textarea v-model="bulkApproveNote" rows="2"
                          placeholder="e.g. Approved — end-of-day batch…"
                          class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40
                                 focus:border-emerald-500/60 transition resize-none"></textarea>
              </div>
              <div class="flex gap-3 pt-1">
                <button @click="closeBulkApprove"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
                               hover:bg-slate-800 text-sm font-medium transition">
                  Cancel
                </button>
                <button @click="submitBulkApprove"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500
                               text-white text-sm font-semibold transition flex items-center justify-center gap-2">
                  <i class="ti ti-checks"></i> Approve {{ selectedIds.size }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ── Bulk Reject modal ── -->
      <Transition name="modal">
        <div v-if="bulkRejectModal"
             class="fixed inset-0 z-[9995] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click.self="closeBulkReject">
          <div class="bg-slate-900 border border-red-500/25 rounded-2xl w-full max-w-md shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-red-500/15 flex items-center justify-center">
                  <i class="ti ti-ban text-red-400 text-sm"></i>
                </div>
                <h3 class="font-semibold text-white">Bulk Reject</h3>
              </div>
              <button @click="closeBulkReject" class="text-slate-400 hover:text-white transition">
                <i class="ti ti-x text-lg"></i>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3">
                <i class="ti ti-alert-triangle text-red-400 text-lg shrink-0"></i>
                <p class="text-sm text-red-300">
                  You are about to reject <strong>{{ selectedIds.size }} transaction(s)</strong>.
                  No funds will be moved for any of them.
                </p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">
                  Reason <span class="text-slate-600">(applies to all rejected transactions)</span>
                </label>
                <textarea v-model="bulkRejectReason" rows="3"
                          placeholder="e.g. Batch rejected pending additional verification…"
                          class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
                                 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500/40
                                 focus:border-red-500/60 transition resize-none"></textarea>
              </div>
              <div class="flex gap-3 pt-1">
                <button @click="closeBulkReject"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300
                               hover:bg-slate-800 text-sm font-medium transition">
                  Cancel
                </button>
                <button @click="submitBulkReject"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500
                               text-white text-sm font-semibold transition">
                  Reject {{ selectedIds.size }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

    </Teleport>

  </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s, transform .2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(.96); }

.slide-right-enter-active, .slide-right-leave-active { transition: opacity .15s, transform .15s; }
.slide-right-enter-from, .slide-right-leave-to { opacity: 0; transform: translateX(8px); }
</style>
