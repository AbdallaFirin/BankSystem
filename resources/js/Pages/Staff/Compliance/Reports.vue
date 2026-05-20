<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, reactive, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    reports:      { type: Object, default: () => ({}) },
    customers:    { type: Array,  default: () => [] },
    flagged_txns: { type: Array,  default: () => [] },
    filters:      { type: Object, default: () => ({}) },
})

const filters = reactive({
    status:     props.filters.status     ?? '',
    risk_level: props.filters.risk_level ?? '',
})

watch(filters, () => {
    router.get(route('staff.compliance.reports'), { ...filters }, { preserveState: true, replace: true })
})

/* ── New SAR form ── */
const showForm = ref(false)
const sarForm  = useForm({
    customer_id:    '',
    transaction_id: '',
    risk_level:     'medium',
    type:           'unusual_activity',
    description:    '',
    status:         'draft',
})

function submitSar() {
    sarForm.post(route('staff.compliance.reports.store'), {
        onSuccess: () => { showForm.value = false; sarForm.reset() },
    })
}

/* ── Status update ── */
const updateForms = reactive({})
function getUpdateForm(id) {
    if (!updateForms[id]) {
        const r = props.reports.data?.find(x => x.id === id)
        updateForms[id] = useForm({ status: r?.status ?? 'draft', notes: r?.notes ?? '' })
    }
    return updateForms[id]
}
function submitUpdate(id) {
    const form = getUpdateForm(id)
    form.put(route('staff.compliance.reports.update', id), { preserveScroll: true })
}

/* ── Helpers ── */
const riskColor = (level) => ({
    low:      'text-emerald-400 bg-emerald-900/30 border-emerald-700/40',
    medium:   'text-amber-400   bg-amber-900/30   border-amber-700/40',
    high:     'text-orange-400  bg-orange-900/30  border-orange-700/40',
    critical: 'text-red-400     bg-red-900/30     border-red-700/40',
}[level] ?? 'text-slate-400 bg-slate-800 border-slate-700')

const sarStatusColor = (s) => ({
    draft:        'bg-slate-800      text-slate-400',
    submitted:    'bg-amber-900/40   text-amber-400',
    under_review: 'bg-blue-900/40    text-blue-400',
    closed:       'bg-emerald-900/40 text-emerald-400',
}[s] ?? 'bg-slate-800 text-slate-400')

const typeLabel = (t) => ({
    structuring:      'Structuring',
    money_laundering: 'Money Laundering',
    fraud:            'Fraud',
    identity_theft:   'Identity Theft',
    unusual_activity: 'Unusual Activity',
    other:            'Other',
}[t] ?? t)

const expandedSar = ref(null)
function toggleExpand(id) {
    expandedSar.value = expandedSar.value === id ? null : id
}
</script>

<template>
  <Head title="SAR Reports — Compliance" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-7xl mx-auto px-5 py-7 space-y-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <Link :href="route('staff.compliance.dashboard')"
                  class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-all">
              <i class="ti ti-arrow-left text-base"></i>
            </Link>
            <div class="w-10 h-10 rounded-xl bg-red-900/30 flex items-center justify-center">
              <i class="ti ti-report-analytics text-red-400 text-xl"></i>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">Suspicious Activity Reports</h1>
              <p class="text-xs text-slate-400">File, track and manage SARs</p>
            </div>
          </div>
          <button @click="showForm = !showForm"
                  class="px-4 py-2 bg-[#E2635A]/20 hover:bg-[#E2635A]/30 border border-[#E2635A]/30 text-[#E2635A] text-sm font-semibold rounded-xl transition-colors flex items-center gap-2">
            <i :class="showForm ? 'ti ti-x' : 'ti ti-plus'"></i>
            {{ showForm ? 'Cancel' : 'File SAR' }}
          </button>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
        </div>

        <!-- New SAR Form -->
        <Transition name="slide">
          <div v-if="showForm" class="bg-slate-900 border border-[#E2635A]/30 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800 bg-[#E2635A]/5">
              <h2 class="font-semibold text-white">New Suspicious Activity Report</h2>
            </div>
            <form @submit.prevent="submitSar" class="p-6 space-y-5">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1.5">Risk Level *</label>
                  <select v-model="sarForm.risk_level" class="field-input">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1.5">Incident Type *</label>
                  <select v-model="sarForm.type" class="field-input">
                    <option value="structuring">Structuring</option>
                    <option value="money_laundering">Money Laundering</option>
                    <option value="fraud">Fraud</option>
                    <option value="identity_theft">Identity Theft</option>
                    <option value="unusual_activity">Unusual Activity</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1.5">Related Customer</label>
                  <select v-model="sarForm.customer_id" class="field-input">
                    <option value="">— None —</option>
                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.full_name }} ({{ c.id_number }})</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1.5">Related Flagged Transaction</label>
                  <select v-model="sarForm.transaction_id" class="field-input">
                    <option value="">— None —</option>
                    <option v-for="t in flagged_txns" :key="t.id" :value="t.id">
                      {{ t.reference }} — ${{ Number(t.amount).toFixed(2) }} ({{ t.type }})
                    </option>
                  </select>
                </div>

                <div class="md:col-span-2">
                  <label class="block text-xs font-medium text-slate-400 mb-1.5">Description *</label>
                  <textarea v-model="sarForm.description" rows="5" required
                            placeholder="Describe the suspicious activity in detail — what happened, when, amounts involved, parties…"
                            class="field-input resize-none"></textarea>
                  <p v-if="sarForm.errors.description" class="text-xs text-red-400 mt-1">{{ sarForm.errors.description }}</p>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1.5">Submit as</label>
                  <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" v-model="sarForm.status" value="draft" class="accent-[#C9A84C]" />
                      <span class="text-sm text-slate-300">Save as Draft</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" v-model="sarForm.status" value="submitted" class="accent-[#C9A84C]" />
                      <span class="text-sm text-slate-300">Submit Now</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="sarForm.processing"
                        class="px-6 py-2.5 bg-[#E2635A] hover:bg-red-500 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors flex items-center gap-2">
                  <i class="ti ti-report-analytics"></i>
                  {{ sarForm.processing ? 'Filing…' : 'File Report' }}
                </button>
                <button type="button" @click="showForm = false"
                        class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-sm rounded-xl transition-colors">
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </Transition>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
          <select v-model="filters.status"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="under_review">Under Review</option>
            <option value="closed">Closed</option>
          </select>
          <select v-model="filters.risk_level"
                  class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none transition-colors">
            <option value="">All Risk Levels</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>

        <!-- SAR List -->
        <div class="space-y-3">
          <div v-for="sar in reports.data" :key="sar.id"
               class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">

            <!-- Summary row -->
            <div class="px-6 py-4 flex flex-wrap items-start gap-4 cursor-pointer hover:bg-slate-800/30 transition-colors"
                 @click="toggleExpand(sar.id)">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                  <span class="font-mono text-sm font-bold text-white">{{ sar.reference }}</span>
                  <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold uppercase" :class="riskColor(sar.risk_level)">
                    {{ sar.risk_level }}
                  </span>
                  <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold" :class="sarStatusColor(sar.status)">
                    {{ sar.status.replace('_', ' ') }}
                  </span>
                </div>
                <p class="text-sm text-slate-300 mt-1">
                  <span class="text-slate-400">Type:</span> {{ typeLabel(sar.type) }}
                  <span v-if="sar.customer" class="ml-3 text-slate-400">· {{ sar.customer.full_name }}</span>
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                  Filed by {{ sar.reporter?.full_name ?? '—' }} ·
                  {{ new Date(sar.created_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) }}
                </p>
              </div>
              <i class="ti text-slate-400 text-lg mt-1 shrink-0" :class="expandedSar === sar.id ? 'ti-chevron-up' : 'ti-chevron-down'"></i>
            </div>

            <!-- Expanded detail -->
            <Transition name="slide">
              <div v-if="expandedSar === sar.id" class="border-t border-slate-800 px-6 py-5 space-y-5">

                <!-- Description -->
                <div>
                  <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Description</h3>
                  <p class="text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">{{ sar.description }}</p>
                </div>

                <!-- Linked transaction -->
                <div v-if="sar.transaction" class="bg-slate-800/60 border border-slate-700 rounded-xl p-4">
                  <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Linked Transaction</h3>
                  <p class="font-mono text-sm text-white">{{ sar.transaction.reference }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">
                    ${{ Number(sar.transaction.amount).toFixed(2) }} · {{ sar.transaction.type }}
                  </p>
                </div>

                <!-- Status update form -->
                <div>
                  <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Update Status</h3>
                  <form @submit.prevent="submitUpdate(sar.id)" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-36">
                      <label class="text-xs text-slate-500 mb-1 block">Status</label>
                      <select v-model="getUpdateForm(sar.id).status" class="field-input">
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="closed">Closed</option>
                      </select>
                    </div>
                    <div class="flex-1 min-w-48">
                      <label class="text-xs text-slate-500 mb-1 block">Internal Notes</label>
                      <input v-model="getUpdateForm(sar.id).notes" type="text" placeholder="Add review notes…" class="field-input" />
                    </div>
                    <button type="submit" :disabled="getUpdateForm(sar.id).processing"
                            class="px-4 py-2.5 bg-[#C9A84C] hover:bg-[#b8973e] disabled:opacity-50 text-[#070F1A] text-sm font-semibold rounded-xl transition-colors whitespace-nowrap">
                      {{ getUpdateForm(sar.id).processing ? '…' : 'Update' }}
                    </button>
                  </form>
                  <p v-if="sar.notes" class="text-xs text-slate-400 mt-2">
                    <i class="ti ti-notes mr-1"></i>{{ sar.notes }}
                  </p>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Empty -->
          <div v-if="!reports.data?.length" class="bg-slate-900 border border-slate-700/60 rounded-2xl py-16 text-center text-slate-500">
            <i class="ti ti-report-analytics text-4xl mb-3 block"></i>
            <p>No SARs found. File one if suspicious activity is detected.</p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="reports.last_page > 1" class="flex items-center justify-between">
          <span class="text-xs text-slate-400">{{ reports.from }}–{{ reports.to }} of {{ reports.total }}</span>
          <div class="flex gap-1">
            <Link v-if="reports.prev_page_url" :href="reports.prev_page_url"
                  class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">← Prev</Link>
            <Link v-if="reports.next_page_url" :href="reports.next_page_url"
                  class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs text-slate-300 transition-colors">Next →</Link>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.field-input {
  @apply w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white
         placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#E2635A]/40 focus:border-[#E2635A]/60
         transition-colors;
}
.slide-enter-active, .slide-leave-active { transition: all 0.25s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
