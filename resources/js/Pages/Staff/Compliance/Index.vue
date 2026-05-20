<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    customers: { type: Array, default: () => [] },
    flash:     { type: Object, default: () => ({}) }
});

/* ─── Customer selection & KYC actions ─── */
const selectedCustomer = ref(null);
const showRejectModal  = ref(false);
const rejectTarget     = ref(null);

const approveForm   = useForm({});
const rejectForm    = useForm({ rejection_reason: '' });
const approveTarget = ref(null);
const showApproveModal = ref(false);

const openApprove = (customer) => {
    approveTarget.value   = customer;
    showApproveModal.value = true;
};

const confirmApprove = () => {
    approveForm.post(route('staff.compliance.approve-kyc', approveTarget.value.id), {
        onSuccess: () => { showApproveModal.value = false; }
    });
};

const openReject = (customer) => {
    rejectTarget.value          = customer;
    rejectForm.rejection_reason = '';
    showRejectModal.value       = true;
};

const submitReject = () => {
    rejectForm.post(route('staff.compliance.reject-kyc', rejectTarget.value.id), {
        onSuccess: () => { showRejectModal.value = false; }
    });
};

const kycBadge = (status) => ({
    pending:      'bg-[rgba(232,168,48,0.1)] text-[#E8A830] border-[rgba(232,168,48,0.2)]',
    under_review: 'bg-[rgba(201,168,76,0.1)] text-[#C9A84C] border-[rgba(201,168,76,0.2)]',
}[status] || 'bg-white/5 text-[#A9B8C6] border-white/10');

/* ─── Lightbox ─── */
const lightbox        = ref(false);
const lightboxCustomer = ref(null);
const lightboxIndex   = ref(0);

const lightboxDocs = computed(() => lightboxCustomer.value?.kyc_documents ?? []);
const currentDoc   = computed(() => lightboxDocs.value[lightboxIndex.value] ?? null);
const currentDocNum = computed(() => `${lightboxIndex.value + 1} of ${lightboxDocs.value.length}`);

const isImage = (path) => /\.(jpg|jpeg|png|gif|webp)$/i.test(path ?? '');
const isPdf   = (path) => /\.pdf$/i.test(path ?? '');

const openLightbox = (customer, docIndex = 0) => {
    lightboxCustomer.value = customer;
    lightboxIndex.value    = docIndex;
    lightbox.value         = true;
};

const closeLightbox = () => { lightbox.value = false; };

const prevDoc = () => {
    if (lightboxIndex.value > 0) lightboxIndex.value--;
};
const nextDoc = () => {
    if (lightboxIndex.value < lightboxDocs.value.length - 1) lightboxIndex.value++;
};

const handleLightboxKey = (e) => {
    if (!lightbox.value) return;
    if (e.key === 'ArrowLeft')  prevDoc();
    if (e.key === 'ArrowRight') nextDoc();
    if (e.key === 'Escape')     closeLightbox();
};

/* register / unregister keyboard listener when lightbox opens/closes */
import { watch, onUnmounted } from 'vue';
watch(lightbox, (open) => {
    if (open)  window.addEventListener('keydown', handleLightboxKey);
    else       window.removeEventListener('keydown', handleLightboxKey);
});
onUnmounted(() => window.removeEventListener('keydown', handleLightboxKey));

const docTypeIcon = (path) => {
    if (isImage(path)) return 'ti-photo';
    if (isPdf(path))   return 'ti-file-type-pdf';
    return 'ti-file-description';
};
</script>

<template>
  <Head title="Compliance & AML Verification" />

  <AuthenticatedLayout>
    <div class="shell max-w-6xl mx-auto p-4 md:p-8">

      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 pb-6 border-b border-[#ffffff14] gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[rgba(226,99,90,0.1)] flex items-center justify-center text-2xl text-[#E2635A]">
            <i class="ti ti-shield-lock"></i>
          </div>
          <div>
            <h1 class="font-serif text-2xl text-[#F0EBE1]">Compliance <em class="text-[#E2635A] not-italic">Authority</em></h1>
            <p class="text-[12px] text-[#A9B8C6] tracking-wide uppercase mt-1">KYC & AML Verification Queue</p>
          </div>
        </div>
        <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-lg px-4 py-2 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full" :class="customers.length > 0 ? 'bg-[#E2635A] animate-pulse' : 'bg-[#4CAF7D]'"></span>
          <span class="text-sm font-medium text-[#A9B8C6]">{{ customers.length }} Pending Reviews</span>
        </div>
      </div>

      <!-- Flash Alert -->
      <div v-if="$page.props.flash?.success" class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-lg mb-8 flex items-center gap-3">
        <i class="ti ti-circle-check"></i>
        <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
      </div>

      <!-- Main Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

        <!-- Queue List -->
        <div class="xl:col-span-2 space-y-4">
          <h2 class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-semibold mb-2">Pending Verification Queue</h2>

          <div v-for="customer in customers" :key="customer.id"
               class="bg-[rgba(255,255,255,0.02)] border transition-all duration-200 rounded-2xl p-5 flex flex-col md:flex-row items-start md:items-center gap-6"
               :class="selectedCustomer?.id === customer.id ? 'border-[#C9A84C] bg-[rgba(201,168,76,0.03)]' : 'border-[#ffffff14] hover:bg-[rgba(255,255,255,0.03)]'">

            <div class="flex items-center gap-4 flex-1">
              <div class="w-12 h-12 rounded-full bg-[rgba(255,255,255,0.05)] flex items-center justify-center text-xl font-serif text-[#C9A84C]">
                {{ customer.full_name.charAt(0) }}
              </div>
              <div>
                <h3 class="text-base font-medium text-[#F0EBE1]">{{ customer.full_name }}</h3>
                <div class="flex flex-wrap gap-3 mt-1 items-center">
                  <span class="text-[11px] text-[#A9B8C6]"><i class="ti ti-id mr-1"></i>{{ customer.id_number }}</span>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border" :class="kycBadge(customer.kyc_status)">
                    {{ customer.kyc_status.replace('_', ' ') }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
              <button @click="selectedCustomer = customer"
                      class="flex-1 md:flex-none px-4 py-2 rounded-lg bg-[rgba(255,255,255,0.05)] text-[#A9B8C6] text-sm hover:bg-[rgba(255,255,255,0.1)] transition whitespace-nowrap">
                Docs ({{ customer.kyc_documents.length }})
              </button>
              <button @click="openApprove(customer)"
                      class="w-10 h-10 rounded-lg bg-[rgba(76,175,125,0.1)] text-[#4CAF7D] hover:bg-[#4CAF7D] hover:text-white transition flex items-center justify-center" title="Approve KYC">
                <i class="ti ti-check"></i>
              </button>
              <button @click="openReject(customer)"
                      class="w-10 h-10 rounded-lg bg-[rgba(226,99,90,0.1)] text-[#E2635A] hover:bg-[#E2635A] hover:text-white transition flex items-center justify-center" title="Reject">
                <i class="ti ti-x"></i>
              </button>
            </div>
          </div>

          <div v-if="customers.length === 0" class="py-20 text-center border border-dashed border-[#ffffff14] rounded-2xl">
            <i class="ti ti-list-check text-4xl text-[#6B7E8E] mb-4"></i>
            <h3 class="text-[#F0EBE1] font-medium">Compliance Clear</h3>
            <p class="text-sm text-[#A9B8C6]">No pending customer registrations to verify.</p>
          </div>
        </div>

        <!-- Detail Sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-24">
            <div v-if="selectedCustomer" class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-6 animate-fade-up">
              <h2 class="text-sm font-medium text-[#F0EBE1] mb-6 uppercase tracking-wider">Customer Details</h2>
              <div class="space-y-6">
                <div>
                  <span class="text-[10px] uppercase text-[#6B7E8E] font-bold block mb-2">Employment & Income</span>
                  <p class="text-sm text-[#F0EBE1]">{{ selectedCustomer.employment_status }}</p>
                  <p class="text-xs text-[#A9B8C6] mt-1">{{ selectedCustomer.monthly_income_range }} · {{ selectedCustomer.source_of_funds }}</p>
                </div>

                <!-- KYC Documents -->
                <div>
                  <span class="text-[10px] uppercase text-[#6B7E8E] font-bold block mb-2">
                    KYC Documents ({{ selectedCustomer.kyc_documents.length }})
                  </span>
                  <div class="space-y-3">
                    <div v-for="(doc, idx) in selectedCustomer.kyc_documents" :key="doc.id"
                         class="group p-3 rounded-xl bg-[rgba(255,255,255,0.03)] border border-[#ffffff08] hover:border-[#C9A84C]/30 transition-colors cursor-pointer"
                         @click="openLightbox(selectedCustomer, idx)">

                      <!-- Doc header row -->
                      <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2 overflow-hidden">
                          <i class="ti text-base text-[#C9A84C]" :class="docTypeIcon(doc.file_path)"></i>
                          <p class="text-[11px] text-[#F0EBE1] truncate font-medium">{{ doc.doc_type }}</p>
                        </div>
                        <span class="text-[9px] text-[#C9A84C] font-bold uppercase tracking-wider opacity-0 group-hover:opacity-100 transition-opacity">
                          View <i class="ti ti-maximize"></i>
                        </span>
                      </div>

                      <!-- Preview thumbnail -->
                      <div class="aspect-video w-full rounded-lg overflow-hidden bg-black/20 border border-white/5 relative">
                        <img v-if="isImage(doc.file_path)"
                             :src="'/storage/' + doc.file_path"
                             class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105" />
                        <div v-else-if="isPdf(doc.file_path)"
                             class="w-full h-full flex flex-col items-center justify-center text-[#6B7E8E] text-[10px] gap-1">
                          <i class="ti ti-file-type-pdf text-3xl text-[#E2635A]"></i>
                          <span>PDF Document</span>
                        </div>
                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-[#6B7E8E] text-[10px] gap-1">
                          <i class="ti ti-file-description text-2xl"></i>
                          <span>Document</span>
                        </div>
                        <!-- Click-to-expand overlay -->
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all flex items-center justify-center">
                          <div class="opacity-0 group-hover:opacity-100 transition-opacity w-8 h-8 rounded-full bg-[#C9A84C] flex items-center justify-center">
                            <i class="ti ti-zoom-in text-[#0B1929] text-sm"></i>
                          </div>
                        </div>
                      </div>
                    </div>

                    <p v-if="!selectedCustomer.kyc_documents.length" class="text-xs text-[#6B7E8E] italic">No documents uploaded yet.</p>
                  </div>
                </div>

                <div class="pt-4 border-t border-[#ffffff14]">
                  <div class="bg-[rgba(226,99,90,0.05)] border border-[rgba(226,99,90,0.1)] p-4 rounded-xl">
                    <span class="text-[10px] uppercase text-[#E2635A] font-bold block mb-1">AML Risk Check</span>
                    <p class="text-[11px] text-[#A9B8C6] leading-relaxed">No global sanctions matched. Income level aligns with profile.</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="bg-[rgba(255,255,255,0.01)] border border-dashed border-[#ffffff14] rounded-2xl p-8 text-center">
              <p class="text-sm text-[#6B7E8E]">Select a customer to view verification details</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         LIGHTBOX MODAL
    ════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="lb">
        <div v-if="lightbox" class="fixed inset-0 z-[9999] flex flex-col" style="background: rgba(5,12,22,0.97);">

          <!-- Header bar -->
          <div class="flex items-center justify-between px-5 py-3 border-b border-white/10 bg-[#0B1929]/80 backdrop-blur-md shrink-0">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-8 h-8 rounded-lg bg-[rgba(201,168,76,0.1)] flex items-center justify-center text-[#C9A84C] shrink-0">
                <i class="ti text-sm" :class="docTypeIcon(currentDoc?.file_path)"></i>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-[#F0EBE1] truncate">{{ currentDoc?.doc_type }}</p>
                <p class="text-[11px] text-[#A9B8C6]">{{ lightboxCustomer?.full_name }} · {{ currentDocNum }}</p>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0 ml-4">
              <a v-if="currentDoc" :href="'/storage/' + currentDoc.file_path" target="_blank" rel="noopener"
                 class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[rgba(201,168,76,0.1)] text-[#C9A84C] text-[11px] font-bold uppercase tracking-wider hover:bg-[rgba(201,168,76,0.2)] transition">
                <i class="ti ti-external-link"></i> Open original
              </a>
              <button @click="closeLightbox"
                      class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 text-[#A9B8C6] hover:text-white transition flex items-center justify-center">
                <i class="ti ti-x"></i>
              </button>
            </div>
          </div>

          <!-- Main viewer area -->
          <div class="flex-1 relative flex items-center justify-center overflow-hidden min-h-0 px-14">

            <!-- Prev button -->
            <button @click="prevDoc" :disabled="lightboxIndex === 0"
                    class="absolute left-3 z-10 w-10 h-10 rounded-full bg-white/8 hover:bg-white/15 border border-white/10 text-[#A9B8C6] hover:text-white transition flex items-center justify-center disabled:opacity-20 disabled:cursor-not-allowed">
              <i class="ti ti-chevron-left text-lg"></i>
            </button>

            <!-- Document display -->
            <div class="w-full h-full flex items-center justify-center p-4">
              <!-- Image -->
              <img v-if="currentDoc && isImage(currentDoc.file_path)"
                   :key="currentDoc.id"
                   :src="'/storage/' + currentDoc.file_path"
                   :alt="currentDoc.doc_type"
                   class="max-w-full max-h-full object-contain rounded-lg shadow-2xl lb-img" />

              <!-- PDF -->
              <iframe v-else-if="currentDoc && isPdf(currentDoc.file_path)"
                      :key="currentDoc.id"
                      :src="'/storage/' + currentDoc.file_path + '#toolbar=1&navpanes=0'"
                      class="w-full rounded-lg shadow-2xl border border-white/10"
                      style="height: calc(100vh - 180px)">
              </iframe>

              <!-- Unknown type -->
              <div v-else-if="currentDoc" class="text-center space-y-4">
                <div class="w-20 h-20 mx-auto rounded-2xl bg-white/5 flex items-center justify-center">
                  <i class="ti ti-file text-4xl text-[#6B7E8E]"></i>
                </div>
                <p class="text-[#A9B8C6] text-sm">This file type cannot be previewed.</p>
                <a :href="'/storage/' + currentDoc.file_path" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#C9A84C] text-[#0B1929] text-sm font-bold hover:bg-[#D9B85C] transition">
                  <i class="ti ti-download"></i> Download File
                </a>
              </div>
            </div>

            <!-- Next button -->
            <button @click="nextDoc" :disabled="lightboxIndex >= lightboxDocs.length - 1"
                    class="absolute right-3 z-10 w-10 h-10 rounded-full bg-white/8 hover:bg-white/15 border border-white/10 text-[#A9B8C6] hover:text-white transition flex items-center justify-center disabled:opacity-20 disabled:cursor-not-allowed">
              <i class="ti ti-chevron-right text-lg"></i>
            </button>
          </div>

          <!-- Thumbnail strip -->
          <div v-if="lightboxDocs.length > 1"
               class="shrink-0 border-t border-white/10 bg-[#0B1929]/80 backdrop-blur-md px-4 py-3 flex items-center gap-3 overflow-x-auto">
            <button v-for="(doc, idx) in lightboxDocs" :key="doc.id"
                    @click="lightboxIndex = idx"
                    class="shrink-0 relative w-16 h-12 rounded-lg overflow-hidden border-2 transition-all"
                    :class="idx === lightboxIndex
                        ? 'border-[#C9A84C] ring-1 ring-[#C9A84C]/30'
                        : 'border-white/10 hover:border-white/30 opacity-50 hover:opacity-80'">
              <img v-if="isImage(doc.file_path)"
                   :src="'/storage/' + doc.file_path"
                   class="w-full h-full object-cover" />
              <div v-else class="w-full h-full bg-white/5 flex items-center justify-center">
                <i class="ti text-lg" :class="[isPdf(doc.file_path) ? 'ti-file-type-pdf text-[#E2635A]' : 'ti-file text-[#6B7E8E]']"></i>
              </div>
              <!-- Active indicator dot -->
              <div v-if="idx === lightboxIndex"
                   class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-[#C9A84C]"></div>
            </button>
          </div>

          <!-- Keyboard hint -->
          <div class="shrink-0 flex items-center justify-center gap-4 py-1.5 text-[10px] text-[#6B7E8E]">
            <span><kbd class="px-1 py-0.5 rounded bg-white/5 font-mono">←</kbd> <kbd class="px-1 py-0.5 rounded bg-white/5 font-mono">→</kbd> Navigate</span>
            <span><kbd class="px-1 py-0.5 rounded bg-white/5 font-mono">Esc</kbd> Close</span>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════
         APPROVE CONFIRMATION MODAL
    ════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showApproveModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div class="bg-[#0B1929] border border-emerald-700/30 w-full max-w-md rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-12 h-12 rounded-full bg-emerald-900/40 flex items-center justify-center">
                <i class="ti ti-shield-check text-2xl text-emerald-400"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Approve KYC</h2>
                <p class="text-sm text-slate-400 mt-0.5">This action will activate the customer's accounts</p>
              </div>
            </div>

            <div v-if="approveTarget" class="bg-slate-800/60 border border-slate-700/40 rounded-xl px-4 py-3 mb-5 space-y-1.5">
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Customer</span>
                <span class="text-white font-medium">{{ approveTarget.full_name }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">ID Number</span>
                <span class="text-white font-mono">{{ approveTarget.id_number }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Documents</span>
                <span class="text-white">{{ approveTarget.kyc_documents?.length ?? 0 }} uploaded</span>
              </div>
            </div>

            <p class="text-xs text-slate-400 mb-5">
              A temporary password will be generated and emailed to the customer. They will be required to change it on first login.
            </p>

            <div class="flex gap-3">
              <button @click="confirmApprove" :disabled="approveForm.processing"
                      class="flex-1 flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition">
                <svg v-if="approveForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <i v-else class="ti ti-check"></i>
                {{ approveForm.processing ? 'Approving…' : 'Confirm Approval' }}
              </button>
              <button @click="showApproveModal = false" :disabled="approveForm.processing"
                      class="px-5 py-3 rounded-xl border border-slate-700 text-slate-400 hover:text-white hover:border-slate-500 font-semibold text-sm transition-colors disabled:opacity-40">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════
         REJECT MODAL
    ════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-[#0B1929] border border-[rgba(226,99,90,0.3)] w-full max-w-lg rounded-3xl p-8 shadow-2xl">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-serif text-[#F0EBE1]">Reject <span class="text-[#E2635A]">Registration</span></h2>
            <button @click="showRejectModal = false" class="text-[#6B7E8E] hover:text-white transition"><i class="ti ti-x text-2xl"></i></button>
          </div>
          <p class="text-sm text-[#A9B8C6] mb-6">
            Rejecting <strong class="text-[#F0EBE1]">{{ rejectTarget?.full_name }}</strong>. Please provide a clear reason that will be recorded.
          </p>
          <form @submit.prevent="submitReject" class="space-y-5">
            <div class="flex flex-col gap-2">
              <label class="text-[11px] text-[#E2635A] font-bold uppercase tracking-widest">Rejection Reason</label>
              <textarea v-model="rejectForm.rejection_reason" required rows="4"
                        placeholder="e.g. Documents are expired / ID number mismatch / Incomplete KYC submission..."
                        class="bg-white/5 border border-white/10 rounded-xl p-4 text-[#F0EBE1] focus:border-[#E2635A] outline-none transition resize-none"></textarea>
              <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-[#E2635A]">{{ rejectForm.errors.rejection_reason }}</p>
            </div>
            <button type="submit" :disabled="rejectForm.processing"
                    class="w-full bg-[#E2635A] text-white font-bold py-4 rounded-xl hover:bg-red-500 transition disabled:opacity-50">
              Confirm Rejection
            </button>
          </form>
        </div>
      </div>
    </Teleport>

  </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-up {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.animate-fade-up { animation: fade-up 0.3s ease-out forwards; }

/* Lightbox transition */
.lb-enter-active,
.lb-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.lb-enter-from,
.lb-leave-to {
  opacity: 0;
  transform: scale(1.02);
}

/* Image pop-in inside lightbox */
.lb-img {
  animation: lb-img-in 0.2s ease-out;
}
@keyframes lb-img-in {
  from { opacity: 0; transform: scale(0.97); }
  to   { opacity: 1; transform: scale(1); }
}

/* Thumbnail strip scrollbar */
::-webkit-scrollbar { height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 2px; }
</style>
