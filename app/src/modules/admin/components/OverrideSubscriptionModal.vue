<template>
  <div v-if="isOpen && tenant" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>⚡</span> Alterar Plano do Tenant
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Tenant: <strong class="text-white">{{ tenant.name }}</strong></p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Novo Plano *
            </label>
            <select
              v-model="selectedPlanCode"
              required
              class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500"
            >
              <option value="STARTER">Personal Starter (Até 10 alunos)</option>
              <option value="PRO">Personal Pro (Até 50 alunos)</option>
              <option value="STUDIO">Studio & Assessoria (Ilimitado / Equipe)</option>
              <option value="ENTERPRISE">Enterprise Custom</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Motivo da Alteração (Auditoria) *
            </label>
            <textarea
              v-model="reason"
              required
              rows="3"
              placeholder="Ex: Upgrade concedido por cortesia comercial / Ajuste manual de teste..."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500"
            ></textarea>
          </div>

          <div v-if="errorMessage" class="p-3 text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-xl">
            {{ errorMessage }}
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl transition-all"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-amber-400 hover:bg-amber-300 disabled:opacity-50 rounded-xl shadow-lg shadow-amber-500/20 transition-all font-display"
            >
              {{ submitting ? 'Alterando...' : 'Aplicar Alteração' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { adminService } from '../services/adminService'
import type { AdminTenantItem } from '../types'

const props = defineProps<{
  isOpen: boolean
  tenant: AdminTenantItem | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'updated'): void
}>()

const selectedPlanCode = ref('PRO')
const reason = ref('')
const submitting = ref(false)
const errorMessage = ref('')

function close() {
  errorMessage.value = ''
  reason.value = ''
  emit('close')
}

async function handleSubmit() {
  if (!props.tenant) return

  submitting.value = true
  errorMessage.value = ''
  try {
    await adminService.overrideSubscription(
      props.tenant.id,
      selectedPlanCode.value,
      reason.value.trim()
    )
    emit('updated')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao alterar plano do tenant.'
  } finally {
    submitting.value = false
  }
}
</script>
