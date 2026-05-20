<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
const props = defineProps({ staff_id: { type: String, default: '' } })
const showPw = ref(false)
const form   = useForm({ staff_id: props.staff_id, code: '', password: '', password_confirmation: '' })
function submit() { form.post(route('staff.reset-password.confirm')) }
</script>

<template>
  <div class="shell pt-16 flex items-center justify-center min-h-screen">
    <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-xl p-8 max-w-md w-full">
      <div class="flex items-center gap-3 mb-8">
        <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain shrink-0" />
        <span class="font-serif text-[#C9A84C] tracking-wide text-lg">Gobaad Bank</span>
      </div>
      <div class="font-serif text-3xl text-[#F0EBE1] mb-2">Reset Password</div>
      <p class="text-[13px] text-[#A9B8C6] mb-8">Enter the 6-digit code from your email and choose a new password.</p>

      <form @submit.prevent="submit" class="space-y-4">
        <!-- Code -->
        <div>
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide block mb-1.5">Verification Code</label>
          <input v-model="form.code" type="text" maxlength="6" placeholder="000000"
            class="w-full font-mono text-[18px] tracking-[.5em] text-center text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-2.5 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)]"
            :class="{ 'border-red-500': form.errors.code }" />
          <p v-if="form.errors.code" class="mt-1 text-xs text-red-400">{{ form.errors.code }}</p>
        </div>
        <!-- New Password -->
        <div>
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide block mb-1.5">New Password</label>
          <div class="relative">
            <input v-model="form.password" :type="showPw ? 'text' : 'password'" placeholder="Minimum 8 characters"
              class="w-full text-[14px] text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-2.5 pr-9 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)]"
              :class="{ 'border-red-500': form.errors.password }" />
            <button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-3 flex items-center text-[#6b7a8d]">
              <i :class="showPw ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
            </button>
          </div>
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-400">{{ form.errors.password }}</p>
        </div>
        <!-- Confirm -->
        <div>
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide block mb-1.5">Confirm Password</label>
          <input v-model="form.password_confirmation" :type="showPw ? 'text' : 'password'" placeholder="Re-enter new password"
            class="w-full text-[14px] text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-2.5 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)]" />
        </div>
        <button type="submit" :disabled="form.processing"
          class="w-full py-3 bg-[#C9A84C] hover:bg-[#b8973d] text-[#0B1929] font-bold text-sm rounded-lg transition disabled:opacity-60 flex items-center justify-center gap-2">
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ form.processing ? 'Resetting…' : 'Reset Password' }}
        </button>
      </form>
      <div class="mt-6 text-center">
        <Link href="/login" class="text-[12px] text-[#7a9ab5] hover:text-[#C9A84C] transition-colors">
          <i class="ti ti-arrow-left mr-1"></i>Back to Login
        </Link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shell { background: radial-gradient(ellipse at 20% 50%, rgba(11,25,41,.95) 0%, #060e18 100%); }
</style>
