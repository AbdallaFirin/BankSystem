<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    staff_branch: { type: Object, default: () => ({}) },
    accountTypes: { type: Array, default: () => [] },
    flash: { type: Object, default: () => ({}) }
});

const currentStep = ref(1);
const totalSteps = 8;

const form = useForm({
    // 1. Personal
    first_name: '',
    middle_name: '',
    last_name: '',
    dob: '',
    gender: '',
    marital_status: '',
    nationality: '',
    religion: '',
    
    // 2. Identity
    id_type: '',
    id_number: '',
    id_issue_date: '',
    id_expiry_date: '',
    
    // 3. Contact
    phone: '',
    secondary_phone: '',
    email: '',
    preferred_contact_method: 'SMS',
    region_city: '',
    district: '',
    street_neighbourhood: '',
    address: '',
    
    // 4. Employment
    employment_status: '',
    employer_name: '',
    monthly_income_range: '',
    source_of_funds: '',
    expected_monthly_transactions: '',
    
    // 5. Account
    account_type_id: '',
    home_branch_id: '',
    
    // 7. Next of Kin
    next_of_kin_name: '',
    next_of_kin_relation: '',
    next_of_kin_phone: '',
    
    // 8. Declaration
    agreed_to_terms: false,
    next_of_kin_parent_type: '', // Matches backend validation name
});

const submit = () => {
    form.post(route('staff.customer-care.store'), {
        onSuccess: () => {
            // Managed by controller redirect
        }
    });
};

const next = () => { if (currentStep.value < totalSteps) currentStep.value++; };
const prev = () => { if (currentStep.value > 1) currentStep.value--; };

const selectAccount = (id) => {
    form.account_type_id = id;
};
</script>

<template>
  <Head title="New Customer Registration" />

  <AuthenticatedLayout>
    <div class="registration-shell max-w-4xl mx-auto p-4 md:p-10">
      
      <!-- Custom Header matching the provided design -->
      <div class="hdr flex flex-col md:flex-row justify-between items-start mb-10 gap-6">
        <div class="hdr-left">
          <div class="flex items-center gap-3 mb-4">
            <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-9 w-auto object-contain shrink-0" />
            <span class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Gobaad Bank</span>
          </div>
          <h1 class="font-serif text-3xl text-[#F0EBE1] leading-tight">New Customer<br><em class="text-[#E5C97E] not-italic">Registration</em></h1>
          <p class="text-sm text-[#A9B8C6] mt-3 max-w-md">Complete all required fields. KYC documents must be verified before account activation.</p>
        </div>
        
        <div class="hdr-right text-right">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-[rgba(255,255,255,0.15)] text-[11px] text-[#6B7E8E] mb-4">
            <i class="ti ti-hash text-[#C9A84C]"></i> REF: CUS-{{ new Date().getFullYear() }}-AUTO
          </div>
          <div class="flex flex-col items-end">
            <span class="text-[11px] text-[#6B7E8E] mb-2 uppercase">Step {{ currentStep }} of {{ totalSteps }}</span>
            <div class="flex gap-1.5">
                <div v-for="s in totalSteps" :key="s" 
                     class="w-8 h-1 rounded-sm transition-all duration-300"
                     :class="s <= currentStep ? 'bg-[#C9A84C]' : 'bg-[rgba(255,255,255,0.1)]'"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="h-px bg-gradient-to-r from-[rgba(201,168,76,0.18)] to-transparent mb-10"></div>

      <!-- Error Summary -->
      <div v-if="Object.keys(form.errors).length > 0" class="mb-8 p-4 bg-[rgba(226,99,90,0.1)] border border-[rgba(226,99,90,0.2)] rounded-xl text-[#E2635A] text-sm animate-fade-up">
          <div class="flex items-center gap-2 mb-2 font-bold uppercase text-[10px] tracking-widest">
              <i class="ti ti-alert-circle text-base"></i> Registration Error
          </div>
          <ul class="list-disc list-inside space-y-1">
              <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
          </ul>
      </div>

      <form @submit.prevent="submit" class="space-y-12">
        
        <!-- 1. Personal Information -->
        <section v-show="currentStep === 1" class="section animate-fade-up">
            <div class="flex items-center gap-3 mb-6 pb-2 border-b border-[rgba(255,255,255,0.08)]">
                <div class="w-7 h-7 rounded bg-[rgba(201,168,76,0.18)] flex items-center justify-center text-[#C9A84C] text-sm">
                    <i class="ti ti-user"></i>
                </div>
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Personal Information</h2>
                <span class="ml-auto text-[11px] text-[#6B7E8E]">01</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">First Name <span class="text-[#C9A84C]">*</span></label>
                    <input type="text" v-model="form.first_name" placeholder="e.g. Abdalla" class="form-input" required />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Middle Name</label>
                    <input type="text" v-model="form.middle_name" placeholder="Optional" class="form-input" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Last Name <span class="text-[#C9A84C]">*</span></label>
                    <input type="text" v-model="form.last_name" placeholder="e.g. Firin" class="form-input" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Date of Birth <span class="text-[#C9A84C]">*</span></label>
                    <input type="date" v-model="form.dob" class="form-input text-gray-400" required />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Gender <span class="text-[#C9A84C]">*</span></label>
                    <select v-model="form.gender" class="form-select" required>
                        <option value="">Select</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Marital Status</label>
                    <select v-model="form.marital_status" class="form-select">
                        <option value="">Select</option>
                        <option>Single</option>
                        <option>Married</option>
                        <option>Divorced</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Nationality <span class="text-[#C9A84C]">*</span></label>
                    <select v-model="form.nationality" class="form-select" required>
                        <option value="">Select</option>
                        <option>Somali</option>
                        <option>Kenyan</option>
                        <option>Ethiopian</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Religion</label>
                    <select v-model="form.religion" class="form-select">
                        <option value="">Select</option>
                        <option>Islam</option>
                        <option>Christianity</option>
                        <option>Other</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- 2. Identity -->
        <section v-show="currentStep === 2" class="section animate-fade-up">
            <div class="flex items-center gap-3 mb-6 pb-2 border-b border-[rgba(255,255,255,0.08)]">
                <div class="w-7 h-7 rounded bg-[rgba(201,168,76,0.18)] flex items-center justify-center text-[#C9A84C] text-sm">
                    <i class="ti ti-id"></i>
                </div>
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Identity & National ID</h2>
                <span class="ml-auto text-[11px] text-[#6B7E8E]">02</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">ID Type <span class="text-[#C9A84C]">*</span></label>
                    <select v-model="form.id_type" class="form-select" required>
                        <option value="">Select</option>
                        <option>National ID Card</option>
                        <option>Passport</option>
                        <option>Driver's License</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">ID Number <span class="text-[#C9A84C]">*</span></label>
                    <input type="text" v-model="form.id_number" placeholder="e.g. SOM-1234567" class="form-input" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Issue Date <span class="text-[#C9A84C]">*</span></label>
                    <input type="date" v-model="form.id_issue_date" class="form-input" required />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Expiry Date</label>
                    <input type="date" v-model="form.id_expiry_date" class="form-input" />
                    <span class="text-[11px] text-[#6B7E8E] mt-1">Leave empty if ID has no expiry</span>
                </div>
            </div>
        </section>

        <!-- 3. Contact Information -->
        <section v-show="currentStep === 3" class="section animate-fade-up">
            <div class="flex items-center gap-3 mb-6 pb-2 border-b border-[rgba(255,255,255,0.08)]">
                <div class="w-7 h-7 rounded bg-[rgba(201,168,76,0.18)] flex items-center justify-center text-[#C9A84C] text-sm">
                    <i class="ti ti-phone"></i>
                </div>
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Contact Information</h2>
                <span class="ml-auto text-[11px] text-[#6B7E8E]">03</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Primary Phone <span class="text-[#C9A84C]">*</span></label>
                    <input type="tel" v-model="form.phone" placeholder="+252 61 000 0000" class="form-input" required />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Email Address</label>
                    <input type="email" v-model="form.email" placeholder="example@email.com" class="form-input" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">City <span class="text-[#C9A84C]">*</span></label>
                    <input type="text" v-model="form.region_city" placeholder="e.g. Garoowe" class="form-input" required />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Region / State</label>
                    <input type="text" v-model="form.district" placeholder="e.g. Puntland" class="form-input" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Street / Neighbourhood</label>
                    <input type="text" v-model="form.street_neighbourhood" placeholder="e.g. KM4 Road" class="form-input" />
                </div>
            </div>
        </section>

        <!-- 4. Employment -->
        <section v-show="currentStep === 4" class="section animate-fade-up">
            <div class="flex items-center gap-3 mb-6 pb-2 border-b border-[rgba(255,255,255,0.08)]">
                <div class="w-7 h-7 rounded bg-[rgba(201,168,76,0.18)] flex items-center justify-center text-[#C9A84C] text-sm">
                    <i class="ti ti-briefcase"></i>
                </div>
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Employment & Financial Profile</h2>
                <span class="ml-auto text-[11px] text-[#6B7E8E]">04</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5" style="grid-column: span 1">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Employment Status <span class="text-[#C9A84C]">*</span></label>
                    <select v-model="form.employment_status" class="form-select" required>
                        <option value="">Select</option>
                        <option>Employed</option>
                        <option>Self-employed</option>
                        <option>Business Owner</option>
                        <option>Student</option>
                        <option>Unemployed</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Monthly Income Range <span class="text-[#C9A84C]">*</span></label>
                    <select v-model="form.monthly_income_range" class="form-select" required>
                        <option value="">Select</option>
                        <option>Under $500</option>
                        <option>$500 - $1,500</option>
                        <option>Above $1,500</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1.5 mt-4">
                <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Source of Funds <span class="text-[#C9A84C]">*</span></label>
                <select v-model="form.source_of_funds" class="form-select" required>
                    <option value="">Select</option>
                    <option>Salary</option>
                    <option>Business Profit</option>
                    <option>Remittance</option>
                    <option>Savings</option>
                </select>
            </div>
        </section>

        <!-- 5. Account Type Selection -->
        <section v-show="currentStep === 5" class="section animate-fade-up">
            <div class="flex items-center gap-3 mb-6 pb-2 border-b border-[rgba(255,255,255,0.08)]">
                <div class="w-7 h-7 rounded bg-[rgba(201,168,76,0.18)] flex items-center justify-center text-[#C9A84C] text-sm">
                    <i class="ti ti-credit-card"></i>
                </div>
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Account Type Selection</h2>
                <span class="ml-auto text-[11px] text-[#6B7E8E]">05</span>
            </div>
            
            <div class="acc-grid grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">
                <div v-for="type in accountTypes" :key="type.id" 
                     @click="form.account_type_id = type.id"
                     class="acc-card relative cursor-pointer p-4 rounded-xl border transition-all duration-200"
                     :class="form.account_type_id === type.id ? 'bg-[rgba(201,168,76,0.08)] border-[#C9A84C]' : 'bg-[rgba(255,255,255,0.02)] border-[rgba(255,255,255,0.1)] hover:border-[rgba(201,168,76,0.5)]'">
                    <div class="absolute top-2 right-2 w-5 h-5 rounded-full border border-[#ffffff20] flex items-center justify-center text-[10px]"
                         :class="form.account_type_id === type.id ? 'bg-[#C9A84C] text-[#0B1929] border-[#C9A84C]' : 'text-transparent'">
                         <i class="ti ti-check"></i>
                    </div>
                    <h3 class="text-sm font-medium text-[#F0EBE1] mb-1">{{ type.type_name }}</h3>
                    <p class="text-[11px] text-[#A9B8C6] mb-2 leading-relaxed">Standard account for daily banking.</p>
                    <span class="text-[11px] font-medium text-[#C9A84C]">{{ type.interest_rate > 0 ? type.interest_rate + '% Annual Interest' : 'No Interest' }}</span>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Home Branch</label>
                <div class="form-input flex items-center gap-2 text-[#C9A84C] font-medium cursor-not-allowed opacity-80">
                    <i class="ti ti-building-bank text-sm"></i>
                    {{ props.staff_branch?.branch_name ?? '—' }}
                </div>
            </div>
        </section>

        <!-- 7. Next of Kin -->
        <section v-show="currentStep === 6" class="section animate-fade-up">
            <div class="flex items-center gap-3 mb-6 pb-2 border-b border-[rgba(255,255,255,0.08)]">
                <div class="w-7 h-7 rounded bg-[rgba(201,168,76,0.18)] flex items-center justify-center text-[#C9A84C] text-sm">
                    <i class="ti ti-users"></i>
                </div>
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Next of Kin</h2>
                <span class="ml-auto text-[11px] text-[#6B7E8E]">07</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Full Name <span class="text-[#C9A84C]">*</span></label>
                    <input type="text" v-model="form.next_of_kin_name" placeholder="John Doe" class="form-input" required />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Relationship <span class="text-[#C9A84C]">*</span></label>
                    <select v-model="form.next_of_kin_relation" class="form-select" required>
                        <option value="">Select</option>
                        <option>Spouse</option>
                        <option>Parent</option>
                        <option>Sibling</option>
                        <option>Child</option>
                    </select>
                </div>
                <div v-if="form.next_of_kin_relation === 'Parent'" class="flex flex-col gap-1.5 animate-fade-up">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Parent Type <span class="text-[#C9A84C]">*</span></label>
                    <select v-model="form.next_of_kin_parent_type" class="form-select" required>
                        <option value="">Select</option>
                        <option>Father</option>
                        <option>Mother</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Phone Number <span class="text-[#C9A84C]">*</span></label>
                    <input type="tel" v-model="form.next_of_kin_phone" placeholder="+252 ..." class="form-input" required />
                </div>
            </div>
        </section>

        <!-- 8. Declaration -->
        <section v-show="currentStep === 7" class="section animate-fade-up">
            <div class="flex items-center gap-3 mb-6 pb-2 border-b border-[rgba(255,255,255,0.08)]">
                <div class="w-7 h-7 rounded bg-[rgba(201,168,76,0.18)] flex items-center justify-center text-[#C9A84C] text-sm">
                    <i class="ti ti-pen"></i>
                </div>
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-semibold">Declaration & Signature</h2>
                <span class="ml-auto text-[11px] text-[#6B7E8E]">08</span>
            </div>
            
            <div class="bg-[rgba(201,168,76,0.04)] border-l-2 border-[#C9A84C] p-4 text-[12.5px] text-[#A9B8C6] leading-relaxed mb-6">
                I, the undersigned, declare that the information provided in this form is true, accurate, and complete to the best of my knowledge. I agree to notify the bank of any changes to my details. I consent to the bank processing my data for account management, compliance, and regulatory purposes.
            </div>

            <div class="space-y-4 mb-8">
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="checkbox" v-model="form.agreed_to_terms" class="mt-1 accent-[#C9A84C]" required />
                    <span class="text-[12.5px] text-[#A9B8C6] group-hover:text-[#F0EBE1] transition">I have read and agree to the Terms & Conditions and Privacy Policy</span>
                </label>
            </div>
            
            <div class="flex flex-col gap-1.5 max-w-sm">
                <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide mb-1">Customer Signature</label>
                <div class="h-20 bg-[rgba(255,255,255,0.02)] border border-dashed border-[rgba(255,255,255,0.1)] rounded-lg flex items-center justify-center text-[12px] text-[#6B7E8E] hover:border-[#C9A84C] hover:text-[#C9A84C] cursor-pointer transition">
                    <i class="ti ti-signature mr-2 text-lg"></i> Sign Here
                </div>
            </div>
        </section>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex gap-3 pt-6 border-t border-[rgba(255,255,255,0.08)]">
            <button v-show="currentStep > 1" @click.prevent="prev" type="button" class="px-6 py-2.5 rounded-lg border border-[rgba(255,255,255,0.1)] text-[#A9B8C6] text-sm hover:bg-[rgba(255,255,255,0.05)] transition">Previous</button>
            <button v-show="currentStep < 7" @click.prevent="next" type="button" class="flex-1 bg-[#C9A84C] text-[#0B1929] px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-[#E5C97E] transition">Continue</button>
            <button v-show="currentStep === 7" type="submit" :disabled="form.processing" class="flex-1 bg-[#C9A84C] text-[#0B1929] px-6 py-2.5 rounded-lg text-sm font-bold shadow-lg hover:bg-[#E5C97E] active:scale-95 transition flex items-center justify-center gap-2">
                <i class="ti ti-check text-lg"></i> Complete Registration
            </button>
        </div>

      </form>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.form-input {
    @apply w-full text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg px-4 py-2.5 outline-none transition-all duration-200 focus:border-[#C9A84C] focus:ring-4 focus:ring-[rgba(201,168,76,0.08)];
}

.form-select {
    @apply w-full text-sm text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg px-4 py-2.5 outline-none transition-all duration-200 focus:border-[#C9A84C] focus:ring-4 focus:ring-[rgba(201,168,76,0.08)] appearance-none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7E8E' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
}

select option {
    background: #112236;
    color: #F0EBE1;
}

@keyframes fade-up {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-up {
    animation: fade-up 0.4s ease-out forwards;
}
</style>
