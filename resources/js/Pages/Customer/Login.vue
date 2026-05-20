<template>
  <div class="min-h-screen bg-gradient-to-br from-[#0B1929] to-[#1a3a5c] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-[#0B1929] px-8 py-7 text-center">
          <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-[#C9A84C]/20 mb-3">
            <span class="text-[#C9A84C] font-bold text-xl">GB</span>
          </div>
          <h1 class="text-white font-bold text-xl tracking-tight">Gobaad Bank</h1>
          <p class="text-[#7a9ab5] text-xs uppercase tracking-widest mt-1">Customer Portal</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="px-8 py-7 space-y-5">
          <div v-if="flash?.error" class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ti ti-alert-circle"></i> {{ flash.error }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Phone Number</label>
            <input
              v-model="form.phone"
              type="tel"
              placeholder="e.g. 0612345678"
              autocomplete="username"
              class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C] focus:border-transparent"
              :class="{ 'border-red-400': form.errors.phone }"
            />
            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Password</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Enter your password"
                autocomplete="current-password"
                class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C] focus:border-transparent"
                :class="{ 'border-red-400': form.errors.password }"
              />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600">
                <i :class="showPassword ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input id="remember" v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-[#C9A84C] focus:ring-[#C9A84C]">
            <label for="remember" class="text-sm text-slate-600">Remember me</label>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-[#0B1929] hover:bg-[#1a3a5c] text-white font-semibold py-3 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ form.processing ? 'Signing in…' : 'Sign In' }}
          </button>
        </form>

        <!-- Footer hint -->
        <div class="px-8 pb-6 text-center space-y-2">
          <Link :href="route('customer.forgot-password')" class="block text-xs font-semibold text-[#0B1929] hover:underline">
            Forgot your password?
          </Link>
          <p class="text-xs text-slate-400">
            First time? Use the temporary password sent to your email after KYC approval.
          </p>
        </div>
      </div>

      <div class="text-center mt-4 space-y-2">
        <p class="text-xs text-white/40">Gobaad Bank &mdash; Secure Banking</p>
        <Link :href="route('customer.register')" class="text-xs text-[#C9A84C]/70 hover:text-[#C9A84C] transition-colors">
          New customer? Register here
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

const page = usePage()
const flash = computed(() => page.props.flash ?? {})

const showPassword = ref(false)

const form = useForm({
  phone:    '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('customer.login.post'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
