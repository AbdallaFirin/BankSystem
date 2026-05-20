<template>
  <div class="min-h-screen bg-gradient-to-br from-[#0B1929] to-[#1a3a5c] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-[#0B1929] px-8 py-7 text-center">
          <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain mx-auto mb-3" />
          <h1 class="text-white font-bold text-xl tracking-tight">Reset Password</h1>
          <p class="text-[#7a9ab5] text-xs uppercase tracking-widest mt-1">Customer Portal</p>
        </div>

        <form @submit.prevent="submit" class="px-8 py-7 space-y-5">
          <p class="text-sm text-slate-600">Enter the 6-digit code sent to your email, then choose a new password.</p>

          <!-- OTP Code -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Verification Code</label>
            <input v-model="form.code" type="text" maxlength="6" placeholder="000000"
              class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm font-mono tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
              :class="{ 'border-red-400': form.errors.code }" />
            <p v-if="form.errors.code" class="mt-1 text-xs text-red-500">{{ form.errors.code }}</p>
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">New Password</label>
            <div class="relative">
              <input v-model="form.password" :type="showPw ? 'text' : 'password'" placeholder="Minimum 8 characters"
                class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
                :class="{ 'border-red-400': form.errors.password }" />
              <button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-3 flex items-center text-slate-400">
                <i :class="showPw ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Confirm Password</label>
            <input v-model="form.password_confirmation" :type="showPw ? 'text' : 'password'" placeholder="Re-enter new password"
              class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]" />
          </div>

          <button type="submit" :disabled="form.processing"
            class="w-full bg-[#0B1929] hover:bg-[#1a3a5c] text-white font-semibold py-3 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 disabled:opacity-60">
            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ form.processing ? 'Resetting…' : 'Reset Password' }}
          </button>
        </form>

        <div class="px-8 pb-6 text-center">
          <Link :href="route('customer.forgot-password')" class="text-xs text-slate-500 hover:text-slate-700 transition-colors">
            <i class="ti ti-refresh mr-1"></i>Resend code
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({ phone: { type: String, default: '' } })
const showPw = ref(false)
const form = useForm({
  phone: props.phone,
  code: '',
  password: '',
  password_confirmation: '',
})
function submit() {
  form.post(route('customer.reset-password.confirm'))
}
</script>
