<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-sm p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <h3 class="text-base font-bold text-white flex items-center gap-2">
            <span>⚖️</span> Registrar Medida Rápida
          </h3>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <form @submit.prevent="submit" class="mt-4 space-y-4">
          <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
            {{ errorMessage }}
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Métrica</label>
            <select 
              v-model="metricCode" 
              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-purple-500"
            >
              <option value="WEIGHT">Peso Corporal (kg)</option>
              <option value="BODY_FAT">Gordura Corporal (% BF)</option>
              <option value="WAIST">Cintura (cm)</option>
              <option value="ARM_RIGHT">Braço Direito (cm)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Valor Medido *</label>
            <input 
              v-model.number="value" 
              type="number" 
              step="0.1" 
              required
              placeholder="Ex: 78.5"
              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-sm focus:outline-none focus:border-purple-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Data</label>
            <input 
              v-model="measuredDate" 
              type="date" 
              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-purple-500"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" @click="close" class="px-4 py-2 text-xs text-slate-400 hover:text-white rounded-xl">
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="saving"
              class="px-5 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl shadow-lg shadow-purple-600/20 disabled:opacity-50"
            >
              {{ saving ? 'Salvando...' : 'Registrar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { assessmentService } from '../services/assessmentService'

const props = defineProps<{
  isOpen: boolean
  studentId: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'recorded'): void
}>()

const metricCode = ref('WEIGHT')
const value = ref<number | null>(null)
const measuredDate = ref(new Date().toISOString().split('T')[0])
const saving = ref(false)
const errorMessage = ref('')

watch(() => props.isOpen, (open) => {
  if (open) {
    value.value = null
    metricCode.value = 'WEIGHT'
    measuredDate.value = new Date().toISOString().split('T')[0]
    errorMessage.value = ''
  }
})

function close() {
  emit('close')
}

async function submit() {
  if (value.value === null || value.value <= 0) {
    errorMessage.value = 'Informe um valor numérico válido.'
    return
  }

  saving.value = true
  errorMessage.value = ''

  try {
    await assessmentService.recordMetric(props.studentId, {
      metric_code: metricCode.value,
      value: Number(value.value),
      date: measuredDate.value,
    })
    emit('recorded')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao registrar métrica.'
  } finally {
    saving.value = false
  }
}
</script>
