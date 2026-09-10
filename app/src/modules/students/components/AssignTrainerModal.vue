<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl z-10 text-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-sm">
                🤝
              </span>
              Atribuir Treinador
            </h3>
            <p class="text-xs text-slate-400 mt-1">
              Aluno: <span class="text-slate-200 font-semibold">{{ studentName }}</span>
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
            ✕
          </button>
        </div>

        <form @submit.prevent="submit" class="mt-5 space-y-4">
          <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            {{ errorMessage }}
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Selecione o Treinador Responsável *</label>
            <select 
              v-model="selectedTrainerId" 
              required
              class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm"
            >
              <option value="" disabled>Escolha um profissional da equipe</option>
              <option v-for="t in trainers" :key="t.id" :value="t.id">
                {{ t.user.name }} ({{ t.cref || 'Sem CREF' }})
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2 pt-2">
            <input 
              id="isPrimary" 
              v-model="isPrimary" 
              type="checkbox" 
              class="rounded border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-slate-950" 
            />
            <label for="isPrimary" class="text-xs text-slate-300">
              Definir como treinador principal do aluno
            </label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button 
              type="button" 
              @click="close"
              class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="loading || !selectedTrainerId"
              class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-600/20 disabled:opacity-50 transition-all flex items-center gap-2"
            >
              <span v-if="loading" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ loading ? 'Salvando...' : 'Confirmar Atribuição' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { studentService } from '../services/studentService'
import { trainerService } from '@/modules/trainers/services/trainerService'
import type { TrainerProfile } from '@/modules/trainers/types'

const props = defineProps<{
  isOpen: boolean
  studentId: string
  studentName: string
  currentTrainerId?: string | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'assigned'): void
}>()

const loading = ref(false)
const errorMessage = ref('')
const selectedTrainerId = ref('')
const isPrimary = ref(true)
const trainers = ref<TrainerProfile[]>([])

async function fetchTrainers() {
  try {
    trainers.value = await trainerService.list()
  } catch (err) {
    console.error('Erro ao carregar treinadores:', err)
  }
}

watch(() => props.isOpen, (open) => {
  if (open) {
    errorMessage.value = ''
    selectedTrainerId.value = props.currentTrainerId || ''
    isPrimary.value = true
    fetchTrainers()
  }
})

function close() {
  emit('close')
}

async function submit() {
  if (!selectedTrainerId.value) return

  loading.value = true
  errorMessage.value = ''

  try {
    await studentService.assignTrainer(props.studentId, selectedTrainerId.value, isPrimary.value)
    emit('assigned')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Falha ao atribuir treinador.'
  } finally {
    loading.value = false
  }
}
</script>
