<template>
    <AuthenticatedLayout>
        <Head title="Customer Portal Access" />

        <div class="max-w-2xl mx-auto space-y-6 py-8 px-4">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold text-[#F0EBE1]">Customer Portal Access</h1>
                <p class="text-sm text-[#A9B8C6] mt-1">
                    Send portal login credentials to existing verified customers who don't yet have access.
                </p>
            </div>

            <!-- Stats card -->
            <div class="bg-[#112236] border border-[#ffffff14] rounded-2xl p-6 flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-[rgba(201,168,76,0.1)] flex items-center justify-center flex-shrink-0">
                    <i class="ti ti-users text-3xl text-[#C9A84C]"></i>
                </div>
                <div class="flex-1">
                    <p class="text-[11px] uppercase tracking-widest text-[#6B7E8E] font-bold mb-1">Verified Customers Without Portal Access</p>
                    <p class="text-3xl font-bold text-[#F0EBE1]">
                        {{ without_access }}
                        <span class="text-base font-normal text-[#A9B8C6]">/ {{ total_verified }} verified</span>
                    </p>
                    <p class="text-sm text-[#A9B8C6] mt-1">
                        <span v-if="without_access === 0" class="text-[#4CAF7D]">
                            <i class="ti ti-circle-check mr-1"></i>All verified customers already have portal access.
                        </span>
                        <span v-else>
                            {{ without_access }} customer{{ without_access !== 1 ? 's' : '' }} still need{{ without_access === 1 ? 's' : '' }} a portal password.
                        </span>
                    </p>
                </div>
            </div>

            <!-- No-email warning -->
            <div v-if="without_email > 0"
                class="bg-amber-900/20 border border-amber-600/30 rounded-2xl px-5 py-4 flex items-start gap-3">
                <i class="ti ti-mail-off text-xl text-amber-400 flex-shrink-0 mt-0.5"></i>
                <div>
                    <p class="font-semibold text-amber-300 text-sm">
                        {{ without_email }} customer{{ without_email !== 1 ? 's' : '' }} have no email address
                    </p>
                    <p class="text-amber-400/80 text-xs mt-1">
                        Their password will be set, but no email can be sent. Go to each customer's profile in Customer Care and add their email before sending portal access.
                    </p>
                </div>
            </div>

            <!-- How it works -->
            <div class="bg-[#112236] border border-[#ffffff14] rounded-2xl p-6 space-y-4">
                <h2 class="text-[11px] uppercase tracking-widest text-[#C9A84C] font-bold">How It Works</h2>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#C9A84C] text-[#0B1929] text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                        <p class="text-sm text-[#A9B8C6]">
                            For each customer without a password, a temporary password is generated using
                            <span class="text-[#F0EBE1] font-mono bg-white/5 px-1 rounded">last 4 digits of phone + birth year</span>
                            (e.g. <span class="text-[#F0EBE1] font-mono bg-white/5 px-1 rounded">56781995</span>).
                        </p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#C9A84C] text-[#0B1929] text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                        <p class="text-sm text-[#A9B8C6]">
                            The password is hashed and stored. An email is dispatched with the credentials and a link to the portal login page.
                        </p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#C9A84C] text-[#0B1929] text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                        <p class="text-sm text-[#A9B8C6]">
                            Customers must change the temporary password on first login. Customers who already have a password are skipped.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Flash success -->
            <div v-if="$page.props.flash?.success"
                class="bg-emerald-900/30 border border-emerald-500/30 text-emerald-300 px-5 py-4 rounded-xl flex items-center gap-3 text-sm">
                <i class="ti ti-circle-check text-xl"></i>
                {{ $page.props.flash.success }}
            </div>

            <!-- Action -->
            <div class="flex gap-4">
                <button
                    @click="showConfirm = true"
                    :disabled="form.processing || without_access === 0"
                    class="px-8 py-3 rounded-xl bg-[#C9A84C] text-[#0B1929] font-bold text-sm hover:bg-[#E5C97E] transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <i class="ti ti-send"></i>
                    Send Access to {{ without_access }} Customer{{ without_access !== 1 ? 's' : '' }}
                </button>

                <Link :href="route('staff.admin.customers.index')"
                    class="px-6 py-3 rounded-xl border border-[#ffffff14] text-[#A9B8C6] text-sm font-semibold hover:bg-white/5 transition">
                    View Customers
                </Link>
            </div>

            <p class="text-xs text-[#4a6070]">
                <i class="ti ti-info-circle mr-1"></i>
                Emails are dispatched as background jobs. Delivery may take a few minutes depending on queue load.
            </p>
        </div>

        <!-- ── Bulk Send Confirmation Modal ── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showConfirm"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     @click.self="showConfirm = false">

                    <div class="bg-[#112236] border border-amber-700/30 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">

                        <!-- Top accent bar -->
                        <div class="h-1 bg-gradient-to-r from-[#C9A84C] via-[#E5C97E] to-[#C9A84C]"></div>

                        <!-- Header -->
                        <div class="px-6 pt-6 pb-4 flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-amber-900/40 border border-amber-700/30 flex items-center justify-center flex-shrink-0">
                                    <i class="ti ti-send text-[#C9A84C] text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-base leading-tight">Bulk Portal Access</h3>
                                    <p class="text-xs text-slate-400 mt-0.5">Send credentials to all pending customers</p>
                                </div>
                            </div>
                            <button @click="showConfirm = false"
                                class="text-slate-500 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/5">
                                <i class="ti ti-x text-lg"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 pb-4 space-y-4">

                            <!-- Count breakdown -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-slate-800/50 border border-slate-700/40 rounded-xl px-4 py-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-900/50 flex items-center justify-center flex-shrink-0">
                                        <i class="ti ti-mail text-emerald-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ without_access - without_email }} customer{{ (without_access - without_email) !== 1 ? 's' : '' }}</p>
                                        <p class="text-xs text-slate-400">will receive credentials by email</p>
                                    </div>
                                </div>
                                <div v-if="without_email > 0" class="flex items-center gap-3 bg-amber-900/20 border border-amber-700/30 rounded-xl px-4 py-3">
                                    <div class="w-8 h-8 rounded-full bg-amber-900/50 flex items-center justify-center flex-shrink-0">
                                        <i class="ti ti-mail-off text-amber-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-amber-300">{{ without_email }} customer{{ without_email !== 1 ? 's' : '' }}</p>
                                        <p class="text-xs text-amber-400/70">password set but no email — no address on file</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2 text-[11px] text-slate-500">
                                <i class="ti ti-info-circle mt-0.5 flex-shrink-0 text-sm text-amber-600/80"></i>
                                <span>Customers who already have a portal password will not be affected.</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="px-6 pb-6 flex gap-3">
                            <button
                                @click="send"
                                :disabled="form.processing"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-[#C9A84C] hover:bg-[#E5C97E] text-[#0B1929] font-bold text-sm transition-colors disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <i v-else class="ti ti-send text-base"></i>
                                {{ form.processing ? 'Sending…' : 'Confirm & Send' }}
                            </button>
                            <button
                                @click="showConfirm = false"
                                :disabled="form.processing"
                                class="px-5 py-2.5 rounded-xl border border-slate-700 text-slate-400 hover:text-white hover:border-slate-500 font-semibold text-sm transition-colors disabled:opacity-40"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    without_access: { type: Number, required: true },
    total_verified: { type: Number, required: true },
    without_email:  { type: Number, default: 0 },
})

const showConfirm = ref(false)
const form = useForm({})

function send() {
    form.post(route('staff.admin.portal-access.bulk-send'), {
        preserveScroll: true,
        onSuccess: () => { showConfirm.value = false },
    })
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s, transform .2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(.95); }
</style>
