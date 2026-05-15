<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    staff_id: '',
    password: '',
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
  <Head title="Staff Login" />

  <div class="shell pt-16 flex items-center justify-center min-h-screen">
    <div class="bg-[rgba(255,255,255,0.02)] border border-[#ffffff14] rounded-xl p-8 max-w-md w-full relative overflow-hidden">

      <!-- Staff Only badge -->
      <div class="absolute top-4 right-4 text-[9.5px] tracking-widest text-[#6B7E8E] font-medium uppercase">
        STAFF ONLY
      </div>

      <!-- Logo -->
      <div class="flex items-center gap-3 mb-8">
        <img src="/storage/images/MAin Logo.png" alt="Gobaad Bank" class="h-10 w-auto object-contain shrink-0" />
        <span class="font-serif text-[#C9A84C] tracking-wide text-lg whitespace-nowrap">Gobaad Bank</span>
      </div>

      <div class="font-serif text-3xl leading-tight text-[#F0EBE1] mb-2">
        Staff Portal<br><em class="not-italic text-[#E5C97E]">Authentication</em>
      </div>
      <p class="text-[13px] text-[#A9B8C6] mb-8">Enter your Staff ID and password to access branch operations.</p>

      <form @submit.prevent="submit">

        <!-- Staff ID -->
        <div class="flex flex-col gap-1.5 mb-4">
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Staff ID</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#C9A84C] text-sm pointer-events-none">
              <i class="ti ti-id-badge"></i>
            </span>
            <input
              type="text"
              v-model="form.staff_id"
              required
              placeholder="e.g. CAR120"
              autocomplete="username"
              class="w-full pl-9 font-mono text-[14px] uppercase text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-2.5 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)]"
            />
          </div>
          <div v-if="form.errors.staff_id" class="text-[11px] text-[#E2635A] mt-1">
            {{ form.errors.staff_id }}
          </div>
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1.5 mb-8">
          <label class="text-[11.5px] text-[#A9B8C6] font-medium tracking-wide">Password</label>
          <input
            type="password"
            v-model="form.password"
            required
            autocomplete="current-password"
            class="w-full font-sans text-[13px] text-[#F0EBE1] bg-[rgba(255,255,255,0.04)] border border-[rgba(255,255,255,0.1)] rounded-lg p-2.5 outline-none transition focus:border-[#C9A84C] focus:ring-2 focus:ring-[rgba(201,168,76,0.3)]"
          />
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-[#C9A84C] hover:bg-[#E5C97E] text-[#0B1929] border-none rounded-lg p-3 font-serif text-[15px] cursor-pointer tracking-wide transition flex items-center justify-center gap-2 disabled:opacity-60"
        >
          <i class="ti ti-login font-bold"></i>
          {{ form.processing ? 'Authenticating…' : 'Authenticate' }}
        </button>
      </form>

      <!-- Hint -->
      <p class="mt-6 text-center text-[11px] text-[#4a5f70]">
        Your Staff ID was issued when your account was created.<br>Contact your administrator if you've forgotten it.
      </p>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css');

:global(body) {
  font-family: 'DM Sans', sans-serif;
  background: #0B1929;
  color: #F0EBE1;
  min-height: 100vh;
}
.shell { max-width: 820px; margin: 0 auto; }
input::placeholder { text-transform: none; }
</style>
