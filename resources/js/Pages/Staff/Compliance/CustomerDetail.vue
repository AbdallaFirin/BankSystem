<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    customer:             { type: Object, required: true },
    recent_transactions:  { type: Array,  default: () => [] },
})

const approveForm = useForm({})
const rejectForm  = useForm({ rejection_reason: '' })
const showReject  = ref(false)
const showApprove = ref(false)

function approve() {
    approveForm.post(route('staff.compliance.approve-kyc', props.customer.id), {
        onSuccess: () => { showApprove.value = false },
    })
}

function submitReject() {
    rejectForm.post(route('staff.compliance.reject-kyc', props.customer.id), {
        onSuccess: () => { showReject.value = false },
    })
}

const kycBadge = (status) => ({
    pending:      'bg-amber-900/40   text-amber-400   border-amber-700/40',
    under_review: 'bg-blue-900/40    text-blue-400    border-blue-700/40',
    verified:     'bg-emerald-900/40 text-emerald-400 border-emerald-700/40',
    rejected:     'bg-red-900/40     text-red-400     border-red-700/40',
}[status] ?? 'bg-slate-800 text-slate-400 border-slate-700')

const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const txnColor = (type) => ({
    deposit:    'text-emerald-400',
    withdrawal: 'text-red-400',
    transfer:   'text-blue-400',
}[type] ?? 'text-slate-400')

/* ── Lightbox ── */
const lightbox      = ref(false)
const lightboxIndex = ref(0)
const docs          = computed(() => props.customer.kyc_documents ?? [])
const currentDoc    = computed(() => docs.value[lightboxIndex.value] ?? null)

const isImage = (p) => /\.(jpg|jpeg|png|gif|webp)$/i.test(p ?? '')
const isPdf   = (p) => /\.pdf$/i.test(p ?? '')

function openLightbox(idx) { lightboxIndex.value = idx; lightbox.value = true }
function closeLightbox()   { lightbox.value = false }
</script>

<template>
  <Head :title="customer.full_name + ' — Compliance'" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-[#070F1A] text-white">
      <div class="max-w-6xl mx-auto px-5 py-7 space-y-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <Link :href="route('staff.compliance.customers')"
                  class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-all">
              <i class="ti ti-arrow-left text-base"></i>
            </Link>
            <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-lg font-bold text-[#C9A84C]">
              {{ customer.full_name.charAt(0) }}
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">{{ customer.full_name }}</h1>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-xs text-slate-400 font-mono">{{ customer.id_number }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold uppercase"
                      :class="kycBadge(customer.kyc_status)">
                  {{ customer.kyc_status?.replace('_', ' ') }}
                </span>
              </div>
            </div>
          </div>

          <div v-if="customer.kyc_status === 'pending' || customer.kyc_status === 'under_review'"
               class="flex gap-2">
            <button @click="showApprove = true"
                    class="px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors flex items-center gap-2">
              <i class="ti ti-check"></i> Approve KYC
            </button>
            <button @click="showReject = true"
                    class="px-4 py-2 bg-red-900/40 hover:bg-red-800/50 border border-red-700/40 text-red-400 text-sm font-semibold rounded-xl transition-colors flex items-center gap-2">
              <i class="ti ti-x"></i> Reject
            </button>
          </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
             class="bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
          <i class="ti ti-circle-check text-lg"></i>{{ $page.props.flash.success }}
        </div>

        <!-- Rejection reason banner -->
        <div v-if="customer.kyc_status === 'rejected' && customer.rejection_reason"
             class="bg-red-900/30 border border-red-700/40 rounded-xl px-5 py-3 text-sm text-red-300 flex items-start gap-3">
          <i class="ti ti-alert-circle mt-0.5 shrink-0"></i>
          <span><strong>Rejection reason:</strong> {{ customer.rejection_reason }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <!-- Customer Info -->
          <div class="lg:col-span-1 space-y-4">
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl p-5 space-y-4">
              <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Personal Details</h2>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Gender</span><span class="text-white">{{ customer.gender }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">DOB</span><span class="text-white">{{ customer.dob }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Nationality</span><span class="text-white">{{ customer.nationality }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Phone</span><span class="text-white font-mono">{{ customer.phone }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">City</span><span class="text-white">{{ customer.region_city }}</span></div>
              </div>
            </div>

            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl p-5 space-y-4">
              <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Financial Profile</h2>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Employment</span><span class="text-white text-right max-w-[60%]">{{ customer.employment_status }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Income Range</span><span class="text-white">{{ customer.monthly_income_range }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Source of Funds</span><span class="text-white text-right max-w-[60%]">{{ customer.source_of_funds }}</span></div>
              </div>
            </div>

            <!-- Accounts -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
              <div class="px-5 py-3 border-b border-slate-800">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Accounts</h2>
              </div>
              <div class="divide-y divide-slate-800">
                <div v-for="acc in customer.accounts" :key="acc.id" class="px-5 py-3">
                  <p class="text-xs font-mono text-white">{{ acc.account_number }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">{{ acc.account_type?.name ?? '—' }} · {{ acc.status }}</p>
                </div>
                <div v-if="!customer.accounts?.length" class="px-5 py-4 text-xs text-slate-500 text-center">
                  No accounts
                </div>
              </div>
            </div>
          </div>

          <!-- KYC Docs + Transactions -->
          <div class="lg:col-span-2 space-y-5">

            <!-- KYC Documents -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
              <div class="px-5 py-4 border-b border-slate-800">
                <h2 class="font-semibold text-white">KYC Documents ({{ customer.kyc_documents?.length ?? 0 }})</h2>
              </div>
              <div class="p-5 grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div v-for="(doc, idx) in customer.kyc_documents" :key="doc.id"
                     @click="openLightbox(idx)"
                     class="group cursor-pointer rounded-xl bg-slate-800 border border-slate-700 hover:border-[#E2635A]/50 transition-colors overflow-hidden">
                  <div class="aspect-video relative">
                    <img v-if="isImage(doc.file_path)" :src="'/storage/' + doc.file_path"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <i class="ti text-3xl" :class="isPdf(doc.file_path) ? 'ti-file-type-pdf text-[#E2635A]' : 'ti-file text-slate-400'"></i>
                    </div>
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center">
                      <i class="ti ti-zoom-in text-white text-xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                  </div>
                  <div class="px-3 py-2">
                    <p class="text-xs font-medium text-white truncate">{{ doc.doc_type }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5 capitalize">{{ doc.status }}</p>
                  </div>
                </div>
                <div v-if="!customer.kyc_documents?.length" class="col-span-3 py-8 text-center text-slate-500 text-sm">
                  No documents uploaded yet.
                </div>
              </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-slate-900 border border-slate-700/60 rounded-2xl overflow-hidden">
              <div class="px-5 py-4 border-b border-slate-800">
                <h2 class="font-semibold text-white">Recent Transactions</h2>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-slate-800/60">
                    <tr class="text-xs text-slate-400 uppercase">
                      <th class="px-4 py-2.5 text-left">Reference</th>
                      <th class="px-4 py-2.5 text-left">Type</th>
                      <th class="px-4 py-2.5 text-right">Amount</th>
                      <th class="px-4 py-2.5 text-center">Status</th>
                      <th class="px-4 py-2.5 text-center">Flag</th>
                      <th class="px-4 py-2.5 text-right">Date</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-800">
                    <tr v-for="txn in recent_transactions" :key="txn.id" class="hover:bg-slate-800/30">
                      <td class="px-4 py-2.5 font-mono text-xs text-white">{{ txn.reference }}</td>
                      <td class="px-4 py-2.5 capitalize text-slate-300 text-xs">{{ txn.type?.replace('_',' ') }}</td>
                      <td class="px-4 py-2.5 text-right font-semibold" :class="txnColor(txn.type)">${{ fmt(txn.amount) }}</td>
                      <td class="px-4 py-2.5 text-center">
                        <span class="text-[10px] px-2 py-0.5 rounded-full"
                              :class="txn.status === 'completed' ? 'bg-emerald-900/40 text-emerald-400' : 'bg-amber-900/40 text-amber-400'">
                          {{ txn.status?.replace('_',' ') }}
                        </span>
                      </td>
                      <td class="px-4 py-2.5 text-center">
                        <i v-if="txn.is_flagged" class="ti ti-flag-3 text-orange-400"></i>
                        <span v-else class="text-slate-600">—</span>
                      </td>
                      <td class="px-4 py-2.5 text-right text-slate-400 text-xs">
                        {{ new Date(txn.created_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="!recent_transactions.length" class="py-8 text-center text-slate-500 text-sm">
                No transactions found.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
      <Transition name="lb">
        <div v-if="lightbox" class="fixed inset-0 z-[9999] flex flex-col" style="background:rgba(5,12,22,0.97)">
          <div class="flex items-center justify-between px-5 py-3 border-b border-white/10 bg-[#0B1929]/80 shrink-0">
            <div>
              <p class="text-sm font-medium text-white">{{ currentDoc?.doc_type }}</p>
              <p class="text-xs text-slate-400">{{ customer.full_name }} · {{ lightboxIndex + 1 }} of {{ docs.length }}</p>
            </div>
            <div class="flex items-center gap-2">
              <a :href="'/storage/' + currentDoc?.file_path" target="_blank"
                 class="px-3 py-1.5 bg-[#C9A84C]/10 text-[#C9A84C] text-xs rounded-lg hover:bg-[#C9A84C]/20 transition">
                <i class="ti ti-external-link mr-1"></i> Open
              </a>
              <button @click="closeLightbox" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition flex items-center justify-center">
                <i class="ti ti-x"></i>
              </button>
            </div>
          </div>
          <div class="flex-1 flex items-center justify-center relative px-14 min-h-0">
            <button @click="lightboxIndex > 0 && lightboxIndex--" :disabled="lightboxIndex === 0"
                    class="absolute left-3 w-10 h-10 rounded-full bg-white/8 hover:bg-white/15 border border-white/10 text-slate-400 hover:text-white flex items-center justify-center disabled:opacity-20">
              <i class="ti ti-chevron-left"></i>
            </button>
            <img v-if="currentDoc && isImage(currentDoc.file_path)"
                 :src="'/storage/' + currentDoc.file_path" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl" />
            <iframe v-else-if="currentDoc && isPdf(currentDoc.file_path)"
                    :src="'/storage/' + currentDoc.file_path" class="w-full rounded-lg border border-white/10 shadow-2xl" style="height:calc(100vh - 180px)"></iframe>
            <button @click="lightboxIndex < docs.length - 1 && lightboxIndex++" :disabled="lightboxIndex >= docs.length - 1"
                    class="absolute right-3 w-10 h-10 rounded-full bg-white/8 hover:bg-white/15 border border-white/10 text-slate-400 hover:text-white flex items-center justify-center disabled:opacity-20">
              <i class="ti ti-chevron-right"></i>
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Approve Confirmation Modal -->
    <Teleport to="body">
      <Transition name="lb">
        <div v-if="showApprove" class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div class="bg-[#0B1929] border border-emerald-700/30 w-full max-w-md rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-12 h-12 rounded-full bg-emerald-900/40 flex items-center justify-center">
                <i class="ti ti-shield-check text-2xl text-emerald-400"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Approve KYC</h2>
                <p class="text-sm text-slate-400 mt-0.5">This will activate the customer's accounts</p>
              </div>
            </div>

            <div class="bg-slate-800/60 border border-slate-700/40 rounded-xl px-4 py-3 mb-5 space-y-1.5">
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Customer</span>
                <span class="text-white font-medium">{{ customer.full_name }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Phone</span>
                <span class="text-white font-mono">{{ customer.phone }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Documents</span>
                <span class="text-white">{{ customer.kyc_documents?.length ?? 0 }} uploaded</span>
              </div>
            </div>

            <p class="text-xs text-slate-400 mb-5">
              A temporary password will be generated and emailed to the customer. They will be required to change it on first login.
            </p>

            <div class="flex gap-3">
              <button @click="approve" :disabled="approveForm.processing"
                      class="flex-1 flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition">
                <svg v-if="approveForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <i v-else class="ti ti-check"></i>
                {{ approveForm.processing ? 'Approving…' : 'Confirm Approval' }}
              </button>
              <button @click="showApprove = false" :disabled="approveForm.processing"
                      class="px-5 py-3 rounded-xl border border-slate-700 text-slate-400 hover:text-white hover:border-slate-500 font-semibold text-sm transition-colors disabled:opacity-40">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Reject Modal -->
    <Teleport to="body">
      <div v-if="showReject" class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-[#0B1929] border border-red-700/30 w-full max-w-lg rounded-2xl p-8 shadow-2xl">
          <div class="flex justify-between items-center mb-5">
            <h2 class="text-xl font-bold text-white">Reject KYC</h2>
            <button @click="showReject = false" class="text-slate-400 hover:text-white transition"><i class="ti ti-x text-xl"></i></button>
          </div>
          <form @submit.prevent="submitReject" class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-red-400 uppercase tracking-wide mb-1.5 block">Rejection Reason *</label>
              <textarea v-model="rejectForm.rejection_reason" rows="4" required
                        placeholder="e.g. Expired ID document, name mismatch…"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-600 transition resize-none"></textarea>
            </div>
            <button type="submit" :disabled="rejectForm.processing"
                    class="w-full bg-red-700 hover:bg-red-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition">
              Confirm Rejection
            </button>
          </form>
        </div>
      </div>
    </Teleport>

  </AuthenticatedLayout>
</template>

<style scoped>
.lb-enter-active, .lb-leave-active { transition: opacity 0.2s ease; }
.lb-enter-from, .lb-leave-to { opacity: 0; }
</style>
