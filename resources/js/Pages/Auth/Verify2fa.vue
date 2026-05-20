<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({ email_hint: { type: String, default: '' } })
const flash  = computed(() => usePage().props.flash ?? {})
const timeLeft = ref(180)
const resendCooldown = ref(30)
const form = useForm({ code: '' })

function submit() {
  form.post(route('staff.verify-2fa.post'), {
    onFinish: () => { if (form.errors.code) form.reset('code') },
  })
}
async function resend() {
  if (resendCooldown.value > 0) return
  try { await axios.post(route('staff.resend-2fa')); resendCooldown.value = 60; timeLeft.value = 180 } catch {}
}

let t1, t2
onMounted(() => {
  t1 = setInterval(() => { if (timeLeft.value > 0) timeLeft.value-- }, 1000)
  t2 = setInterval(() => { if (resendCooldown.value > 0) resendCooldown.value-- }, 1000)
})
onUnmounted(() => { clearInterval(t1); clearInterval(t2) })
</script>

<template>
  <div class="shell pt-16 flex items-center justify-center min-h-screen">
    <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-xl p-8 max-w-md w-full text-center">
      <div class="flex items-center justify-center gap-3 mb-8">
        <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain shrink-0" />
        <span class="font-serif text-[#C9A84C] tracking-wide text-lg">Gobaad Bank</span>
      </div>

      <div class="w-16 h-16 rounded-full bg-[#C9A84C]/10 flex items-center justify-center mx-auto mb-4">
        <i class="ti ti-shield-check text-[#C9A84C] text-3xl"></i>
      </div>

      <div class="font-serif text-2xl text-[#F0EBE1] mb-2">Two-Factor Authentication</div>
      <p class="text-[13px] text-[#A9B8C6] mb-2">
        A verification code was sent to <span class="text-[#C9A84C] font-semibold">{{ props.email_hint }}</span>
      </p>

      <div v-if="flash?.info" class="mb-4 text-sm text-[#7EC8A0] bg-[rgba(126,200,160,0.1)] border border-[#7EC8A0]/30 rounded-lg px-4 py-3">
        {{ flash.info }}
      </div>

      <form @submit.prevent="submit" class="space-y-4 mt-6 text-left">
        <div>
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide block mb-2 text-center">Enter 6-Digit Code</label>
          <input v-model="form.code" type="text" maxlength="6" placeholder="000000" autofocus
            class="w-full font-mono text-[22px] tracking-[.6em] text-center text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-xl p-3 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)]"
            :class="{ 'border-red-500': form.errors.code }" />
          <p v-if="form.errors.code" class="mt-1.5 text-xs text-red-400 text-center">{{ form.errors.code }}</p>
        </div>
        <button type="submit" :disabled="form.processing || form.code.length < 6"
          class="w-full py-3 bg-[#C9A84C] hover:bg-[#b8973d] text-[#0B1929] font-bold text-sm rounded-lg transition disabled:opacity-60 flex items-center justify-center gap-2">
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <i v-else class="ti ti-lock-open"></i>
          {{ form.processing ? 'Verifying…' : 'Verify & Continue' }}
        </button>
      </form>

      <div class="mt-5 space-y-2">
        <p class="text-xs text-[#6b7a8d]">Code expires in
          <span :class="timeLeft <= 60 ? 'text-red-400' : 'text-[#A9B8C6]'" class="font-semibold">
            {{ Math.floor(timeLeft/60) }}:{{ String(timeLeft%60).padStart(2,'0') }}
          </span>
        </p>
        <button @click="resend" :disabled="resendCooldown > 0"
          class="text-xs font-semibold text-[#C9A84C] hover:underline disabled:text-[#6b7a8d] disabled:no-underline">
          {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Code' }}
        </button>
      </div>

      <div class="mt-6">
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
