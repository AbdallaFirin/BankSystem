<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
  <Head title="403 - Unauthorized" />

  <div class="shell flex items-center justify-center min-h-screen pt-4">
    <div class="text-center max-w-lg w-full">
      <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-[rgba(226,99,90,0.15)] text-[#E2635A] text-5xl mb-6">
        <i class="ti ti-shield-x"></i>
      </div>
      
      <div class="font-serif text-5xl leading-tight text-[#F0EBE1] mb-4">Access <em class="text-[#E2635A] not-italic">Denied</em></div>
      <p class="text-[14px] text-[#A9B8C6] mb-8 leading-relaxed">
        You do not have the required permissions to perform this action. This attempt has been logged for security auditing.
      </p>

      <div class="flex items-center justify-center gap-4">
        <button @click="() => window.history.back()" class="bg-transparent text-[#A9B8C6] border border-[#ffffff26] rounded-lg px-6 py-2.5 font-sans text-[13px] hover:border-[#6B7E8E] hover:text-[#F0EBE1] hover:bg-[rgba(255,255,255,0.04)] transition">
          Return Back
        </button>
        <Link href="/staff/dashboard" class="bg-[#C9A84C] text-[#0B1929] rounded-lg px-6 py-2.5 font-serif text-[15px] tracking-wide hover:bg-[#E5C97E] transition flex items-center gap-2">
           <i class="ti ti-dashboard"></i> Dashboard
        </Link>
      </div>
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
</style>
