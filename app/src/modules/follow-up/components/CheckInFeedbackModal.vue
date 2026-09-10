<template>
  <div v-if="isOpen && checkIn" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>💬</span> Feedback do Treinador no Check-in
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Check-in de: <span class="text-white font-semibold">{{ formatDate(checkIn.check_in_date) }}</span>
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Check-in Summary -->
        <div class="mt-4 space-y-3">
          <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
              <span class="text-[10px] text-slate-500 block uppercase">Energia</span>
              <span class="font-bold text-amber-400 font-mono">{{ checkIn.energy_level ?? '-' }}/5</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
              <span class="text-[10px] text-slate-500 block uppercase">Sono</span>
              <span class="font-bold text-indigo-400 font-mono">{{ checkIn.sleep_quality ?? '-' }}/5</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
              <span class="text-[10px] text-slate-500 block uppercase">Dor</span>
              <span class="font-bold text-rose-400 font-mono">{{ checkIn.soreness_level ?? '-' }}/5</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
              <span class="text-[10px] text-slate-500 block uppercase">Dieta</span>
              <span class="font-bold text-emerald-400 font-mono">{{ checkIn.diet_adherence ?? '-' }}/5</span>
            </div>
          </div>

          <div v-if="checkIn.notes" class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Relato do Aluno:</span>
            <p class="text-slate-300 italic">"{{ checkIn.notes }}"</p>
          </div>
        </div>

        <!-- Form Feedback -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Seu Parecer & Orientações Técnicas *
            </label>
            <textarea
              v-model="feedback"
              rows="4"
              required
              placeholder="Ex: Excelente avanço no sono! Vamos manter o volume desta semana e adicionar 2 séries de aquecimento no manguito rotador antes do supino..."
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500 resize-none"
            ></textarea>
          </div>

          <div v-if="errorMessage" class="p-3 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
            {{ errorMessage }}
          </div>

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
              class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 disabled:opacity-50 rounded-xl transition-all font-display shadow-lg shadow-blue-600/20"
            >
              {{ saving ? 'Enviando...' : 'Enviar Feedback' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { followUpService } from '../services/followUpService'
import type { StudentCheckIn } from '../types'

const props = defineProps<{
  isOpen: boolean
  checkIn: StudentCheckIn | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const feedback = ref('')
const saving = ref(false)
const errorMessage = ref('')

watch(() => props.checkIn, (c) => {
  if (c) {
    feedback.value = c.trainer_feedback || ''
    errorMessage.value = ''
  }
})

function close() {
  emit('close')
}

async function handleSubmit() {
  if (!props.checkIn) return

  saving.value = true
  errorMessage.value = ''
  try {
    await followUpService.feedbackCheckIn(props.checkIn.id, feedback.value)
    emit('saved')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao enviar feedback.'
  } finally {
    saving.value = false
  }
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>
