<template>
  <div class="min-h-screen bg-gradient-to-br from-[#0B1929] to-[#1a3a5c] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-[#0B1929] px-8 py-7 text-center">
          <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain mx-auto mb-3" />
          <h1 class="text-white font-bold text-xl tracking-tight">Verify Your Identity</h1>
          <p class="text-[#7a9ab5] text-xs uppercase tracking-widest mt-1">Two-Factor Authentication</p>
        </div>

        <div class="px-8 py-7 space-y-5">
          <div class="text-center">
            <div class="w-14 h-14 rounded-full bg-[#C9A84C]/10 flex items-center justify-center mx-auto mb-3">
              <i class="ti ti-shield-check text-[#C9A84C] text-2xl"></i>
            </div>
            <p class="text-sm text-slate-600">
              A 6-digit verification code was sent to
              <span class="font-semibold text-slate-800">{{ emailHint }}</span>
            </p>
          </div>

          <div v-if="flash?.info" class="bg-blue-50 border border-blue-200 text-blue-700 text-sm px-4 py-3 rounded-lg text-center">
            {{ flash.info }}
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-2 uppercase tracking-wider text-center">Enter Code</label>
              <input v-model="form.code" type="text" maxlength="6" placeholder="000000"
                autofocus
                class="w-full px-4 py-3 border border-slate-300 rounded-xl text-xl font-mono tracking-[.5em] text-center focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
                :class="{ 'border-red-400 bg-red-50': form.errors.code }" />
              <p v-if="form.errors.code" class="mt-1.5 text-xs text-red-500 text-center">{{ form.errors.code }}</p>
            </div>

            <button type="submit" :disabled="form.processing || form.code.length < 6"
              class="w-full bg-[#0B1929] hover:bg-[#1a3a5c] text-white font-semibold py-3 rounded-xl transition-colors text-sm flex items-center justify-center gap-2 disabled:opacity-60">
              <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <i v-else class="ti ti-lock-open"></i>
              {{ form.processing ? 'Verifying…' : 'Verify & Sign In' }}
            </button>
          </form>

          <div class="text-center space-y-2">
            <p class="text-xs text-slate-400">Didn't receive the code?</p>
            <button @click="resend" :disabled="resendCooldown > 0"
              class="text-xs font-semibold text-[#0B1929] hover:underline disabled:text-slate-400 disabled:no-underline transition-colors">
              {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Code' }}
            </button>
          </div>

          <!-- Timer -->
          <div class="text-center">
            <p class="text-xs text-slate-400">Code expires in
              <span class="font-semibold" :class="timeLeft <= 60 ? 'text-red-500' : 'text-slate-600'">
                {{ Math.floor(timeLeft / 60) }}:{{ String(timeLeft % 60).padStart(2, '0') }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <div class="text-center mt-4">
        <Link :href="route('customer.login')" class="text-xs text-white/50 hover:text-white/80 transition-colors">
          <i class="ti ti-arrow-left mr-1"></i>Back to Login
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  email_hint: { type: String, default: '' },
})

const flash      = computed(() => usePage().props.flash ?? {})
const emailHint  = props.email_hint
const timeLeft   = ref(180) // 3 minutes
const resendCooldown = ref(30)

const form = useForm({ code: '' })

function submit() {
  form.post(route('customer.verify-2fa.post'), {
    onFinish: () => { if (form.errors.code) form.reset('code') },
  })
}

async function resend() {
  if (resendCooldown.value > 0) return
  try {
    await axios.post(route('customer.resend-otp'))
    resendCooldown.value = 60
    timeLeft.value = 180
  } catch {}
}

let timer, cooldownTimer
onMounted(() => {
  timer = setInterval(() => { if (timeLeft.value > 0) timeLeft.value-- }, 1000)
  cooldownTimer = setInterval(() => { if (resendCooldown.value > 0) resendCooldown.value-- }, 1000)
})
onUnmounted(() => { clearInterval(timer); clearInterval(cooldownTimer) })
</script>
