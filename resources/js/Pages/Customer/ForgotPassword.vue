<template>
  <div class="min-h-screen bg-gradient-to-br from-[#0B1929] to-[#1a3a5c] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-[#0B1929] px-8 py-7 text-center">
          <img src="/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain mx-auto mb-3" />
          <h1 class="text-white font-bold text-xl tracking-tight">Forgot Password</h1>
          <p class="text-[#7a9ab5] text-xs uppercase tracking-widest mt-1">Customer Portal</p>
        </div>

        <form @submit.prevent="submit" class="px-8 py-7 space-y-5">
          <p class="text-sm text-slate-600">Enter your registered phone number and we'll send a reset code to your email.</p>

          <div v-if="flash?.info" class="bg-blue-50 border border-blue-200 text-blue-700 text-sm px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ti ti-info-circle"></i> {{ flash.info }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Phone Number</label>
            <input v-model="form.phone" type="tel" placeholder="e.g. 0612345678"
              class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
              :class="{ 'border-red-400': form.errors.phone }" />
            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
          </div>

          <button type="submit" :disabled="form.processing"
            class="w-full bg-[#0B1929] hover:bg-[#1a3a5c] text-white font-semibold py-3 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 disabled:opacity-60">
            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ form.processing ? 'Sending…' : 'Send Reset Code' }}
          </button>
        </form>

        <div class="px-8 pb-6 text-center">
          <Link :href="route('customer.login')" class="text-xs text-slate-500 hover:text-slate-700 transition-colors">
            <i class="ti ti-arrow-left mr-1"></i>Back to Login
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

const flash = computed(() => usePage().props.flash ?? {})
const form = useForm({ phone: '' })
function submit() {
  form.post(route('customer.forgot-password.send'))
}
</script>
