<template>
  <div v-if="isOpen && anamnesis" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 shrink-0">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📋</span> Prontuário de Anamnese
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ anamnesis.template_title }} (v{{ anamnesis.version_number }}) • {{ formatDate(anamnesis.answered_at) }}
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Content -->
        <div class="mt-4 flex-1 overflow-y-auto pr-1 space-y-4">
          <!-- Parecer / Notas Clínicas se houver -->
          <div v-if="anamnesis.notes" class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 space-y-1">
            <span class="text-[10px] uppercase tracking-wider font-bold text-amber-400 block">
              Parecer Técnico do Treinador
            </span>
            <p class="text-xs text-slate-300 italic">"{{ anamnesis.notes }}"</p>
          </div>

          <!-- Respostas -->
          <div class="space-y-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">
              Respostas Registradas ({{ anamnesis.answers.length }})
            </span>

            <div 
              v-for="(ans, idx) in anamnesis.answers" 
              :key="ans.id || idx"
              class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-1.5"
            >
              <div class="text-xs font-medium text-slate-400">
                <span class="text-amber-400 font-mono mr-1">#{{ idx + 1 }}</span>
                {{ ans.question_text }}
              </div>

              <div class="text-sm font-semibold text-white">
                <span 
                  v-if="typeof ans.answer === 'boolean'"
                  class="px-2 py-0.5 text-xs rounded-md font-bold"
                  :class="ans.answer ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-300'"
                >
                  {{ ans.answer ? 'SIM' : 'NÃO' }}
                </span>
                <span v-else class="text-slate-200">
                  {{ ans.answer || 'Sem resposta' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end pt-3 border-t border-slate-800 shrink-0">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-xl"
          >
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { StudentAnamnesisDetail } from '../types'

defineProps<{
  isOpen: boolean
  anamnesis: StudentAnamnesisDetail | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function close() {
  emit('close')
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
