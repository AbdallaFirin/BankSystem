<template>
  <CustomerLayout>
    <div class="max-w-2xl mx-auto space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-slate-800">Saved Beneficiaries</h2>
          <p class="text-sm text-slate-500 mt-0.5">Quickly send money to people you transfer to often.</p>
        </div>
        <button @click="showAdd = true"
          class="inline-flex items-center gap-2 bg-[#0B1929] text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-[#1a3a5c] transition-colors">
          <i class="ti ti-plus"></i> Add Beneficiary
        </button>
      </div>

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success" class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
        <i class="ti ti-circle-check text-emerald-600"></i>{{ $page.props.flash.success }}
      </div>

      <!-- Empty state -->
      <div v-if="beneficiaries.length === 0"
           class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="ti ti-users text-slate-400 text-3xl"></i>
        </div>
        <h3 class="font-semibold text-slate-700 mb-1">No Saved Beneficiaries</h3>
        <p class="text-sm text-slate-500 mb-6">Add an account to transfer to them faster next time.</p>
        <button @click="showAdd = true"
          class="inline-flex items-center gap-2 bg-[#0B1929] text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-[#1a3a5c] transition-colors">
          <i class="ti ti-plus"></i> Add First Beneficiary
        </button>
      </div>

      <!-- Beneficiary list -->
      <div v-else class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
        <div v-for="b in beneficiaries" :key="b.id"
             class="flex items-center gap-4 px-5 py-4">
          <!-- Avatar -->
          <div class="w-10 h-10 rounded-full bg-[#0B1929]/10 flex items-center justify-center shrink-0">
            <span class="text-[#0B1929] font-bold text-sm">{{ initials(b.nickname || b.account_holder_name) }}</span>
          </div>
          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-800 truncate">{{ b.nickname || b.account_holder_name }}</p>
            <p class="text-xs text-slate-400 font-mono mt-0.5">{{ b.account_number }}</p>
            <p v-if="b.nickname" class="text-xs text-slate-400">{{ b.account_holder_name }}</p>
          </div>
          <!-- Actions -->
          <div class="flex items-center gap-2 shrink-0">
            <Link :href="route('customer.transfer') + '?to=' + b.account_number"
              class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-[#0B1929] text-white hover:bg-[#1a3a5c] transition-colors">
              Transfer
            </Link>
            <button @click="confirmDelete(b)"
              class="w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors flex items-center justify-center">
              <i class="ti ti-trash text-sm"></i>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- Add Beneficiary Modal -->
    <Transition name="overlay">
      <div v-if="showAdd" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-slate-800">Add Beneficiary</h3>
            <button @click="showAdd = false; addForm.reset()" class="text-slate-400 hover:text-slate-600">
              <i class="ti ti-x text-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitAdd" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Account Number</label>
              <input v-model="addForm.account_number" type="text" required placeholder="e.g. SAV001234"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40 focus:border-[#C9A84C]"
                :class="{ 'border-red-400': addForm.errors.account_number }" />
              <p v-if="addForm.errors.account_number" class="mt-1 text-xs text-red-500">{{ addForm.errors.account_number }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nickname <span class="text-slate-400 font-normal">(optional)</span></label>
              <input v-model="addForm.nickname" type="text" placeholder="e.g. Mum, John's Savings"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A84C]/40 focus:border-[#C9A84C]" />
            </div>
            <div class="flex gap-3 pt-1">
              <button type="button" @click="showAdd = false; addForm.reset()"
                class="flex-1 py-2.5 border border-slate-300 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors">
                Cancel
              </button>
              <button type="submit" :disabled="addForm.processing"
                class="flex-1 py-2.5 bg-[#0B1929] text-white rounded-xl text-sm font-semibold hover:bg-[#1a3a5c] transition-colors disabled:opacity-60">
                {{ addForm.processing ? 'Saving…' : 'Save Beneficiary' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Delete confirm -->
    <Transition name="overlay">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
          <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
            <i class="ti ti-trash text-red-500 text-xl"></i>
          </div>
          <h3 class="font-bold text-slate-800 mb-2">Remove Beneficiary?</h3>
          <p class="text-sm text-slate-500 mb-5">
            Remove <strong>{{ deleteTarget.nickname || deleteTarget.account_holder_name }}</strong> from your saved list?
          </p>
          <div class="flex gap-3">
            <button @click="deleteTarget = null" class="flex-1 py-2.5 border border-slate-300 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-50">Cancel</button>
            <button @click="submitDelete" :disabled="deleteForm.processing"
              class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-sm font-semibold hover:bg-red-600 disabled:opacity-60">
              {{ deleteForm.processing ? 'Removing…' : 'Remove' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </CustomerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'

const props = defineProps({
  beneficiaries: { type: Array, default: () => [] },
})

const showAdd     = ref(false)
const deleteTarget = ref(null)

const addForm    = useForm({ account_number: '', nickname: '' })
const deleteForm = useForm({})

function initials(name) {
  return (name || '?').split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase()
}

function submitAdd() {
  addForm.post(route('customer.beneficiaries.store'), {
    onSuccess: () => { showAdd.value = false; addForm.reset() },
  })
}

function confirmDelete(b) {
  deleteTarget.value = b
}

function submitDelete() {
  deleteForm.delete(route('customer.beneficiaries.destroy', deleteTarget.value.id), {
    onSuccess: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s ease; }
.overlay-enter-from, .overlay-leave-to       { opacity: 0; }
</style>
