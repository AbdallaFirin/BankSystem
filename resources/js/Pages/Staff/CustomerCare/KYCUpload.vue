<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    customer: { type: Object, required: true },
    flash: { type: Object, default: () => ({}) }
});

const form = useForm({
    doc_type: '',
    file: null,
});

const reviewForm = useForm({});

const submit = () => {
    form.post(route('staff.customer-care.upload-kyc', props.customer.id), {
        onSuccess: () => form.reset(),
    });
};

const submitForReview = () => {
    reviewForm.post(route('staff.customer-care.kyc.submit-review', props.customer.id));
};

const handleFile = (e) => {
    form.file = e.target.files[0];
};

const hasDoc = (type) => {
    return props.customer.kyc_documents?.some(d => d.doc_type === type);
};

const getDoc = (type) => {
    return props.customer.kyc_documents?.find(d => d.doc_type === type);
};

// Check if all required documents have been uploaded
const isComplete = computed(() => {
    if (props.customer.id_type === 'Passport') {
        return hasDoc('Passport Photo') && hasDoc('Passport Copy');
    } else {
        return hasDoc('Passport Photo') && hasDoc('National ID Front') && hasDoc('National ID Back');
    }
});

// Customer is already submitted if status is under_review or verified
const alreadySubmitted = computed(() => 
    ['under_review', 'verified'].includes(props.customer.kyc_status)
);
</script>

<template>
  <Head title="KYC Document Upload" />

  <AuthenticatedLayout>
    <div class="shell max-w-4xl mx-auto p-4 md:p-10">
      
      <!-- Header -->
      <div class="flex items-center gap-4 mb-10 pb-6 border-b border-[rgba(255,255,255,0.1)]">
        <div class="w-12 h-12 rounded-xl bg-[rgba(76,175,125,0.1)] flex items-center justify-center text-2xl text-[#4CAF7D]">
          <i class="ti ti-file-upload"></i>
        </div>
        <div>
          <h1 class="font-serif text-2xl text-[#F0EBE1]">KYC <em class="text-[#4CAF7D] not-italic">Documentation</em></h1>
          <p class="text-sm text-[#A9B8C6] mt-1">Upload required files for {{ customer.full_name }}</p>
        </div>
        <Link :href="route('staff.dashboard')" class="ml-auto text-sm text-[#A9B8C6] hover:text-[#C9A84C] transition">Skip to Dashboard</Link>
      </div>

      <!-- Alerts -->
      <div v-if="flash.success" class="bg-[rgba(76,175,125,0.1)] border border-[#4CAF7D] text-[#4CAF7D] p-4 rounded-lg mb-8 flex items-center gap-3">
        <i class="ti ti-check-circle"></i>
        <span class="text-sm">{{ flash.success }}</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Upload Form -->
        <div class="bg-[rgba(255,255,255,0.02)] border border-[rgba(255,255,255,0.1)] rounded-2xl p-6 h-fit">
            <h2 class="text-sm font-medium text-[#F0EBE1] mb-6 uppercase tracking-wider">New Document</h2>
            
            <form @submit.prevent="submit" class="space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-[11px] text-[#A9B8C6] font-semibold uppercase tracking-widest">Document Type</label>
                    <select v-model="form.doc_type" class="form-select" required>
                        <option value="" class="bg-[#112236]">Select Type</option>
                        <template v-if="customer.id_type === 'Passport'">
                            <option class="bg-[#112236]">Passport Copy</option>
                        </template>
                        <template v-else>
                            <option class="bg-[#112236]">National ID Front</option>
                            <option class="bg-[#112236]">National ID Back</option>
                        </template>
                        <option class="bg-[#112236]">Passport Photo</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-[11px] text-[#A9B8C6] font-semibold uppercase tracking-widest">Select File</label>
                    <div class="relative group">
                        <input type="file" @change="handleFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                        <div class="border-2 border-dashed border-[rgba(255,255,255,0.1)] group-hover:border-[#C9A84C] rounded-xl p-8 text-center transition">
                            <i class="ti ti-cloud-upload text-3xl text-[#6B7E8E] mb-2 group-hover:text-[#C9A84C]"></i>
                            <p class="text-sm text-[#A9B8C6] font-medium">{{ form.file ? form.file.name : 'Click or drag file to upload' }}</p>
                            <p class="text-[10px] text-[#6B7E8E] mt-1">PDF, JPG, PNG (Max 2MB)</p>
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="form.processing" class="w-full bg-[#C9A84C] text-[#0B1929] py-3.5 rounded-xl font-bold hover:bg-[#E5C97E] transition shadow-lg active:scale-[0.98] disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Save Document' }}
                </button>
            </form>
        </div>

        <!-- Checklist Status -->
        <div class="space-y-4">
            <h2 class="text-sm font-medium text-[#F0EBE1] mb-2 uppercase tracking-wider">Checklist Status</h2>
            
            <div class="bg-[rgba(201,168,76,0.1)] border border-[rgba(201,168,76,0.2)] rounded-lg p-3 mb-4 flex items-center gap-2">
                <i class="ti ti-info-circle text-[#C9A84C]"></i>
                <span class="text-[10px] uppercase tracking-wider text-[#F0EBE1] font-bold">Requirement: {{ customer.id_type }}</span>
            </div>

            <!-- Dynamic KYC Item: Photo (Mandatory for both) -->
            <div class="bg-[rgba(255,255,255,0.01)] border border-[rgba(255,255,255,0.05)] rounded-xl p-5 flex items-center gap-4 transition-all duration-300 shadow-lg"
                 :class="hasDoc('Passport Photo') ? 'border-[#4CAF7D33] bg-[#4CAF7D0D]' : 'bg-[rgba(255,255,255,0.02)]'">
                <div class="w-14 h-14 rounded-lg overflow-hidden bg-[rgba(76,175,125,0.1)] text-[#4CAF7D] flex items-center justify-center text-2xl shrink-0 border border-[rgba(255,255,255,0.05)]">
                    <img v-if="getDoc('Passport Photo')" :src="'/storage/' + getDoc('Passport Photo').file_path" class="w-full h-full object-cover" />
                    <i v-else class="ti ti-camera"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold" :class="hasDoc('Passport Photo') ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">Person's Photo (Portrait)</h3>
                    <p class="text-[11px] mt-0.5" :class="hasDoc('Passport Photo') ? 'text-[#4CAF7D]' : 'text-[#A9B8C6]'">
                       <i v-if="hasDoc('Passport Photo')" class="ti ti-circle-check-filled mr-1"></i>
                       {{ hasDoc('Passport Photo') ? 'Photo Uploaded / Tig' : 'Required for ID verification' }}
                    </p>
                </div>
                <div class="ml-auto shirk-0">
                    <span v-if="hasDoc('Passport Photo')" class="flex items-center gap-1.5 text-[10px] px-3 py-1.5 rounded-full bg-[rgba(76,175,125,0.15)] text-[#4CAF7D] border border-[rgba(76,175,125,0.2)] font-bold uppercase tracking-wider">
                        <i class="ti ti-check"></i> OK
                    </span>
                    <span v-else class="text-[10px] px-3 py-1.5 rounded-full bg-[rgba(232,168,48,0.1)] text-[#E8A830] border border-[rgba(232,168,48,0.2)] font-medium uppercase tracking-wider">Required</span>
                </div>
            </div>

            <!-- Dynamic KYC Item: Identity Document (Conditional) -->
            <template v-if="customer.id_type === 'Passport'">
                <div class="bg-[rgba(255,255,255,0.01)] border border-[rgba(255,255,255,0.05)] rounded-xl p-5 flex items-center gap-4 transition-all duration-300 shadow-lg"
                 :class="hasDoc('Passport Copy') ? 'border-[#4CAF7D33] bg-[#4CAF7D0D]' : 'bg-[rgba(255,255,255,0.02)]'">
                    <div class="w-14 h-14 rounded-lg overflow-hidden bg-[rgba(201,168,76,0.1)] text-[#C9A84C] flex items-center justify-center text-2xl shrink-0 border border-[rgba(255,255,255,0.05)]">
                        <img v-if="getDoc('Passport Copy')" :src="'/storage/' + getDoc('Passport Copy').file_path" class="w-full h-full object-cover" />
                        <i v-else class="ti ti-notebook"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-bold" :class="hasDoc('Passport Copy') ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">Passport Document Copy</h3>
                        <p class="text-[11px] mt-0.5" :class="hasDoc('Passport Copy') ? 'text-[#4CAF7D]' : 'text-[#A9B8C6]'">
                           <i v-if="hasDoc('Passport Copy')" class="ti ti-circle-check-filled mr-1"></i>
                           {{ hasDoc('Passport Copy') ? 'Document Uploaded / Tig' : 'Full page passport copy' }}
                        </p>
                    </div>
                    <div class="ml-auto shirk-0">
                        <span v-if="hasDoc('Passport Copy')" class="flex items-center gap-1.5 text-[10px] px-3 py-1.5 rounded-full bg-[rgba(76,175,125,0.15)] text-[#4CAF7D] border border-[rgba(76,175,125,0.2)] font-bold uppercase tracking-wider">
                            <i class="ti ti-check"></i> OK
                        </span>
                        <span v-else class="text-[10px] px-3 py-1.5 rounded-full bg-[rgba(232,168,48,0.1)] text-[#E8A830] border border-[rgba(232,168,48,0.2)] font-medium uppercase tracking-wider">Required</span>
                    </div>
                </div>
            </template>
            <template v-else>
                <!-- ID Front -->
                <div class="bg-[rgba(255,255,255,0.01)] border border-[rgba(255,255,255,0.05)] rounded-xl p-5 flex items-center gap-4 transition-all duration-300 shadow-lg"
                 :class="hasDoc('National ID Front') ? 'border-[#4CAF7D33] bg-[#4CAF7D0D]' : 'bg-[rgba(255,255,255,0.02)]'">
                    <div class="w-14 h-14 rounded-lg overflow-hidden bg-[rgba(201,168,76,0.1)] text-[#C9A84C] flex items-center justify-center text-2xl shrink-0 border border-[rgba(255,255,255,0.05)]">
                        <img v-if="getDoc('National ID Front')" :src="'/storage/' + getDoc('National ID Front').file_path" class="w-full h-full object-cover" />
                        <i v-else class="ti ti-id"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-bold" :class="hasDoc('National ID Front') ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">National ID (Front)</h3>
                        <p class="text-[11px] mt-0.5" :class="hasDoc('National ID Front') ? 'text-[#4CAF7D]' : 'text-[#A9B8C6]'">
                           {{ hasDoc('National ID Front') ? 'Front Uploaded / Tig' : 'National Identity Front' }}
                        </p>
                    </div>
                    <div class="ml-auto shirk-0">
                        <span v-if="hasDoc('National ID Front')" class="text-[10px] px-3 py-1.5 rounded-full bg-[rgba(76,175,125,0.15)] text-[#4CAF7D] font-bold">OK</span>
                        <span v-else class="text-[10px] px-3 py-1.5 rounded-full bg-[rgba(232,168,48,0.1)] text-[#E8A830]">Required</span>
                    </div>
                </div>
                <!-- ID Back -->
                <div class="bg-[rgba(255,255,255,0.01)] border border-[rgba(255,255,255,0.05)] rounded-xl p-5 flex items-center gap-4 transition-all duration-300 shadow-lg"
                 :class="hasDoc('National ID Back') ? 'border-[#4CAF7D33] bg-[#4CAF7D0D]' : 'bg-[rgba(255,255,255,0.02)]'">
                    <div class="w-14 h-14 rounded-lg overflow-hidden bg-[rgba(201,168,76,0.1)] text-[#C9A84C] flex items-center justify-center text-2xl shrink-0 border border-[rgba(255,255,255,0.05)]">
                        <img v-if="getDoc('National ID Back')" :src="'/storage/' + getDoc('National ID Back').file_path" class="w-full h-full object-cover" />
                        <i v-else class="ti ti-id"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-bold" :class="hasDoc('National ID Back') ? 'text-[#F0EBE1]' : 'text-[#6B7E8E]'">National ID (Back)</h3>
                        <p class="text-[11px] mt-0.5" :class="hasDoc('National ID Back') ? 'text-[#4CAF7D]' : 'text-[#A9B8C6]'">
                           {{ hasDoc('National ID Back') ? 'Back Uploaded / Tig' : 'National Identity Back' }}
                        </p>
                    </div>
                    <div class="ml-auto shirk-0">
                        <span v-if="hasDoc('National ID Back')" class="text-[10px] px-3 py-1.5 rounded-full bg-[rgba(76,175,125,0.15)] text-[#4CAF7D] font-bold">OK</span>
                        <span v-else class="text-[10px] px-3 py-1.5 rounded-full bg-[rgba(232,168,48,0.1)] text-[#E8A830]">Required</span>
                    </div>
                </div>
            </template>

            <!-- Compliance Note -->
            <div class="mt-8 p-4 bg-[rgba(201,168,76,0.05)] border border-[rgba(201,168,76,0.1)] rounded-xl">
                <p class="text-[11px] text-[#C9A84C] leading-relaxed flex gap-2">
                    <i class="ti ti-shield-lock text-sm"></i>
                    Compliance: Accounts remain <span class="font-bold underline text-[#E5C97E]">INACTIVE</span> until verified by our internal compliance team.
                </p>
            </div>

            <!-- ✅ Submit for Pending Review Button -->
            <div class="mt-6">
                <!-- Already submitted -->
                <div v-if="alreadySubmitted" class="bg-[rgba(76,175,125,0.08)] border border-[#4CAF7D] rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-[rgba(76,175,125,0.15)] flex items-center justify-center">
                        <i class="ti ti-circle-check-filled text-xl text-[#4CAF7D]"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-[#4CAF7D]">Submitted for Compliance Review</p>
                        <p class="text-[11px] text-[#A9B8C6] mt-0.5">This customer is now in the compliance queue.</p>
                    </div>
                </div>

                <!-- Docs complete → show submit button -->
                <div v-else-if="isComplete">
                    <button @click="submitForReview" :disabled="reviewForm.processing"
                        class="w-full flex items-center justify-center gap-3 bg-[#4CAF7D] text-white py-4 rounded-2xl font-bold hover:bg-[#3d9e6e] transition shadow-xl active:scale-[0.98] disabled:opacity-50 text-sm">
                        <i class="ti ti-send text-lg"></i>
                        {{ reviewForm.processing ? 'Submitting...' : 'Complete & Send to Pending Reviews' }}
                    </button>
                    <p class="text-[11px] text-[#A9B8C6] text-center mt-3">
                        All required documents are uploaded. Click to submit this customer for compliance verification.
                    </p>
                </div>

                <!-- Docs incomplete → show hint -->
                <div v-else class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-2xl p-5 text-center">
                    <i class="ti ti-file-unknown text-2xl text-[#6B7E8E] block mb-2"></i>
                    <p class="text-xs text-[#6B7E8E]">Upload all required documents above to unlock the submission button.</p>
                </div>
            </div>

        </div>

      </div>

    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.form-select {
    @apply w-full text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg px-4 py-2.5 outline-none transition-all duration-200 focus:border-[#C9A84C] appearance-none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7E8E' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
}
</style>
