<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  token:      { type: String, required: true },
  staff_name: { type: String, default: '' },
  staff_id:   { type: String, default: '' },
  role:       { type: String, default: '' },
  branch:     { type: String, default: '' },
})

const form = useForm({
  token:                 props.token,
  password:              '',
  password_confirmation: '',
})

function submit() {
  form.post(route('staff.accept-invite.post'))
}
</script>

<template>
  <div class="shell pt-16 flex items-center justify-center min-h-screen">
    <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-xl p-8 max-w-md w-full">

      <!-- Header -->
      <div class="flex items-center justify-center gap-3 mb-8">
        <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain shrink-0" />
        <span class="font-serif text-[#C9A84C] tracking-wide text-lg">Gobaad Bank</span>
      </div>

      <!-- Icon -->
      <div class="w-16 h-16 rounded-full bg-[#C9A84C]/10 flex items-center justify-center mx-auto mb-4">
        <i class="ti ti-mail-check text-[#C9A84C] text-3xl"></i>
      </div>

      <div class="font-serif text-2xl text-[#F0EBE1] mb-2 text-center">Accept Invitation</div>
      <p class="text-[13px] text-[#A9B8C6] mb-6 text-center">
        Set your password to activate your Gobaad Bank staff account.
      </p>

      <!-- Staff info card -->
      <div class="bg-[rgba(201,168,76,0.07)] border border-[#C9A84C]/20 rounded-xl p-4 mb-6 space-y-1.5">
        <div class="flex justify-between text-sm">
          <span class="text-[#7a9ab5]">Name</span>
          <span class="text-[#F0EBE1] font-semibold">{{ staff_name }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-[#7a9ab5]">Staff ID</span>
          <span class="text-[#C9A84C] font-mono font-bold">{{ staff_id }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-[#7a9ab5]">Role</span>
          <span class="text-[#F0EBE1]">{{ role }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-[#7a9ab5]">Branch</span>
          <span class="text-[#F0EBE1]">{{ branch }}</span>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide block mb-1.5">
            New Password
          </label>
          <input v-model="form.password" type="password" placeholder="Minimum 8 characters" required autofocus
            class="w-full text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-xl p-3 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)] text-sm"
            :class="{ 'border-red-500': form.errors.password }" />
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-400">{{ form.errors.password }}</p>
        </div>

        <div>
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide block mb-1.5">
            Confirm Password
          </label>
          <input v-model="form.password_confirmation" type="password" placeholder="Repeat your password" required
            class="w-full text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-xl p-3 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)] text-sm"
            :class="{ 'border-red-500': form.errors.password_confirmation }" />
          <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-400">{{ form.errors.password_confirmation }}</p>
        </div>

        <p v-if="form.errors.token" class="text-xs text-red-400 text-center">{{ form.errors.token }}</p>

        <button type="submit" :disabled="form.processing || !form.password || !form.password_confirmation"
          class="w-full py-3 bg-[#C9A84C] hover:bg-[#b8973d] text-[#0B1929] font-bold text-sm rounded-lg transition disabled:opacity-60 flex items-center justify-center gap-2 mt-2">
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <i v-else class="ti ti-check"></i>
          {{ form.processing ? 'Activating…' : 'Activate My Account' }}
        </button>
      </form>

      <p class="text-[11px] text-[#6b7a8d] text-center mt-5">
        After activation you will be asked to log in with your Staff ID and 2FA code.
      </p>
    </div>
  </div>
</template>

<style scoped>
.shell { background: radial-gradient(ellipse at 20% 50%, rgba(11,25,41,.95) 0%, #060e18 100%); }
</style>
