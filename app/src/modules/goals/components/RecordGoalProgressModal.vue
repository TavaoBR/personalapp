<template>
  <div v-if="isOpen && goal" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>📈</span> Registrar Progresso da Meta
            </h3>
            <p class="text-xs text-slate-400 mt-0.5 truncate max-w-[280px]">
              {{ goal.title }}
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <!-- Valor Atual -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Valor Medido Atualmente ({{ goal.unit }}) *
            </label>
            <div class="relative">
              <input
                v-model.number="value"
                type="number"
                step="any"
                required
                placeholder="Ex: 78.5"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-base text-white focus:outline-none focus:border-rose-500 font-mono font-bold"
              />
              <span class="absolute right-3.5 top-3 text-xs font-semibold text-slate-400">
                {{ goal.unit }}
              </span>
            </div>
            <div class="flex items-center justify-between mt-1 text-[11px] text-slate-500">
              <span>Anterior: {{ goal.current_value ?? goal.initial_value ?? '-' }} {{ goal.unit }}</span>
              <span>Alvo: {{ goal.target_value ?? '-' }} {{ goal.unit }}</span>
            </div>
          </div>

          <!-- Data da Medição -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Data da Medição *
            </label>
            <input
              v-model="recordedAt"
              type="date"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500"
            />
          </div>

          <!-- Observação -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Observações & Comentários do Check-in
            </label>
            <textarea
              v-model="notes"
              rows="2"
              placeholder="Ex: Aluno relatou excelente disciplina na semana e redução de retenção..."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500 resize-none"
            ></textarea>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-3 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
            {{ errorMessage }}
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 disabled:opacity-50 rounded-xl transition-all font-display shadow-lg shadow-rose-600/20"
            >
              {{ saving ? 'Salvando...' : 'Registrar Progresso' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { goalService } from '../services/goalService'
import type { StudentGoalSummary } from '../types'

const props = defineProps<{
  isOpen: boolean
  goal: StudentGoalSummary | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'recorded'): void
}>()

const value = ref<number | undefined>(undefined)
const recordedAt = ref(new Date().toISOString().split('T')[0])
const notes = ref('')
const saving = ref(false)
const errorMessage = ref('')

watch(() => props.goal, (newGoal) => {
  if (newGoal) {
    value.value = newGoal.current_value !== null ? newGoal.current_value : (newGoal.initial_value ?? undefined)
    recordedAt.value = new Date().toISOString().split('T')[0]
    notes.value = ''
    errorMessage.value = ''
  }
})

function close() {
  emit('close')
}

async function handleSubmit() {
  if (!props.goal || value.value === undefined) return

  saving.value = true
  errorMessage.value = ''
  try {
    await goalService.recordProgress(props.goal.id, {
      value: value.value,
      notes: notes.value || undefined,
      recorded_at: recordedAt.value
    })
    emit('recorded')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao registrar progresso.'
  } finally {
    saving.value = false
  }
}
</script>
