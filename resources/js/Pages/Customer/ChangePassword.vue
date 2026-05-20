<template>
  <CustomerLayout>
    <div class="max-w-md mx-auto">
      <!-- Forced change banner -->
      <div v-if="forced" class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex gap-3 mb-6">
        <i class="ti ti-lock-exclamation text-2xl text-amber-600 flex-shrink-0 mt-0.5"></i>
        <div>
          <p class="font-semibold text-amber-800 text-sm">Password Change Required</p>
          <p class="text-amber-700 text-xs mt-1">
            You are using a temporary password. Please set a new personal password to continue.
          </p>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-1">{{ forced ? 'Set New Password' : 'Change Password' }}</h2>
        <p class="text-sm text-slate-500 mb-6">Choose a strong password that is at least 8 characters long.</p>

        <form @submit.prevent="submit" class="space-y-5">
          <!-- Current password (voluntary only) -->
          <div v-if="!forced">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Current Password</label>
            <div class="relative">
              <input
                v-model="form.current_password"
                :type="showPw ? 'text' : 'password'"
                placeholder="Enter your current password"
                autocomplete="current-password"
                class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
                :class="{ 'border-red-400': form.errors.current_password }"
              />
            </div>
            <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-500">{{ form.errors.current_password }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">New Password</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPw ? 'text' : 'password'"
                placeholder="Minimum 8 characters"
                autocomplete="new-password"
                class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
                :class="{ 'border-red-400': form.errors.password }"
              />
              <button type="button" @click="showPw = !showPw"
                class="absolute inset-y-0 right-3 flex items-center text-slate-400">
                <i :class="showPw ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Confirm Password</label>
            <input
              v-model="form.password_confirmation"
              :type="showPw ? 'text' : 'password'"
              placeholder="Re-enter your new password"
              autocomplete="new-password"
              class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]"
              :class="{ 'border-red-400': form.errors.password_confirmation }"
            />
            <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
          </div>

          <!-- Strength hint -->
          <div v-if="form.password" class="space-y-1">
            <div class="flex gap-1">
              <div v-for="i in 4" :key="i"
                class="h-1 flex-1 rounded-full transition-colors"
                :class="strengthScore >= i ? strengthColor : 'bg-slate-200'"
              ></div>
            </div>
            <p class="text-[11px] text-slate-500">{{ strengthLabel }}</p>
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
            {{ form.processing ? 'Saving…' : 'Set New Password' }}
          </button>
        </form>
      </div>
    </div>
  </CustomerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

const props = defineProps({ forced: { type: Boolean, default: false } })

const showPw = ref(false)

const form = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

function submit() {
  form.post(route('customer.change-password.post'))
}

const strengthScore = computed(() => {
  const pw = form.password
  if (!pw) return 0
  let score = 0
  if (pw.length >= 8)  score++
  if (/[A-Z]/.test(pw)) score++
  if (/[0-9]/.test(pw)) score++
  if (/[^A-Za-z0-9]/.test(pw)) score++
  return score
})

const strengthColor = computed(() => {
  if (strengthScore.value <= 1) return 'bg-red-400'
  if (strengthScore.value === 2) return 'bg-amber-400'
  if (strengthScore.value === 3) return 'bg-blue-400'
  return 'bg-emerald-500'
})

const strengthLabel = computed(() => {
  const labels = ['', 'Weak', 'Fair', 'Good', 'Strong']
  return labels[strengthScore.value] ?? ''
})
</script>
