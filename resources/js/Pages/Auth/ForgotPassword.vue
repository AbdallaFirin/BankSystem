<script setup>
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
const flash = computed(() => usePage().props.flash ?? {})
const form  = useForm({ staff_id: '' })
function submit() { form.post(route('staff.forgot-password.send')) }
</script>

<template>
  <div class="shell pt-16 flex items-center justify-center min-h-screen">
    <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-xl p-8 max-w-md w-full">
      <div class="flex items-center gap-3 mb-8">
        <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain shrink-0" />
        <span class="font-serif text-[#C9A84C] tracking-wide text-lg">Gobaad Bank</span>
      </div>
      <div class="font-serif text-3xl text-[#F0EBE1] mb-2">
        Forgot Password<br><em class="not-italic text-[#E5C97E] text-2xl">Staff Reset</em>
      </div>
      <p class="text-[13px] text-[#A9B8C6] mb-8">Enter your Staff ID and a reset code will be sent to your registered email.</p>

      <div v-if="flash?.info" class="mb-4 text-sm text-[#7EC8A0] bg-[rgba(126,200,160,0.1)] border border-[#7EC8A0]/30 rounded-lg px-4 py-3">
        {{ flash.info }}
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide block mb-1.5">Staff ID</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#C9A84C] text-sm pointer-events-none"><i class="ti ti-id-badge"></i></span>
            <input v-model="form.staff_id" type="text" placeholder="e.g. CAR120" required
              class="w-full pl-9 font-mono text-[14px] uppercase text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-2.5 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)]"
              :class="{ 'border-red-500': form.errors.staff_id }" />
          </div>
          <p v-if="form.errors.staff_id" class="mt-1 text-xs text-red-400">{{ form.errors.staff_id }}</p>
        </div>
        <button type="submit" :disabled="form.processing"
          class="w-full py-3 bg-[#C9A84C] hover:bg-[#b8973d] text-[#0B1929] font-bold text-sm rounded-lg transition disabled:opacity-60 flex items-center justify-center gap-2">
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ form.processing ? 'Sending…' : 'Send Reset Code' }}
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
