<template>
  <CustomerLayout>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Notifications</h1>
        <p class="text-sm text-slate-500 mt-1">
          Your notification inbox.
          <span v-if="unread_count" class="font-semibold text-amber-600">{{ unread_count }} unread</span>
        </p>
      </div>

      <button v-if="unread_count > 0"
        @click="markAllRead"
        class="self-start sm:self-auto px-4 py-2 text-sm font-semibold bg-[#0B1929] text-white rounded-lg hover:bg-[#1a3a5c] transition-colors flex items-center gap-2">
        <i class="ti ti-checks"></i> Mark all as read
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="notifications.data.length" class="divide-y divide-slate-100">
        <div v-for="n in notifications.data" :key="n.id"
          class="flex gap-4 px-5 py-4 transition-colors"
          :class="n.status === 'pending' ? 'bg-blue-50/40' : 'hover:bg-slate-50'">
          <!-- Icon -->
          <div class="flex-shrink-0 mt-0.5">
            <div class="w-8 h-8 rounded-full flex items-center justify-center"
              :class="n.status === 'pending' ? 'bg-blue-100' : 'bg-slate-100'">
              <i class="ti ti-bell text-sm"
                :class="n.status === 'pending' ? 'text-blue-600' : 'text-slate-500'"></i>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-sm text-slate-800 leading-relaxed">{{ n.message }}</p>
            <div class="flex items-center gap-3 mt-1.5">
              <span class="text-[11px] text-slate-400">
                {{ formatDate(n.created_at) }}
              </span>
              <span v-if="n.status === 'pending'"
                class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500"></span>
              <span v-if="n.channel === 'email'"
                class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-0.5">
                <i class="ti ti-mail text-xs"></i> Email sent
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="px-6 py-14 text-center text-slate-400">
        <i class="ti ti-bell-off text-4xl mb-3 block"></i>
        <p class="text-sm">No notifications yet.</p>
      </div>

      <!-- Pagination -->
      <div v-if="notifications.last_page > 1"
        class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">
          Page {{ notifications.current_page }} of {{ notifications.last_page }}
        </p>
        <div class="flex gap-2">
          <Link v-if="notifications.prev_page_url" :href="notifications.prev_page_url"
            class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">
            ← Prev
          </Link>
          <Link v-if="notifications.next_page_url" :href="notifications.next_page_url"
            class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">
            Next →
          </Link>
        </div>
      </div>
    </div>
  </CustomerLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

defineProps({
  notifications: { type: Object, required: true },
  unread_count:  { type: Number, default: 0 },
})

function markAllRead() {
  router.post(route('customer.notifications.read'), {}, {
    preserveScroll: true,
  })
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>
