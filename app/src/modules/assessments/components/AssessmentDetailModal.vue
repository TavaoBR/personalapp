<template>
  <div v-if="isOpen && assessment" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📊</span> Avaliação Física
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Realizada em: <span class="text-white font-semibold">{{ formatDate(assessment.date) }}</span>
              <span v-if="assessment.trainer_name"> por {{ assessment.trainer_name }}</span>
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <div class="mt-4 space-y-4">
          <p v-if="assessment.notes" class="text-xs text-slate-300 italic p-3 bg-slate-950/60 rounded-xl border border-slate-800">
            "{{ assessment.notes }}"
          </p>

          <div class="grid grid-cols-2 gap-2.5">
            <div 
              v-for="m in assessment.metrics" 
              :key="m.metric_code"
              class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between"
            >
              <div>
                <span class="text-[11px] text-slate-400 block">{{ m.metric_name || m.metric_code }}</span>
                <span class="text-base font-bold text-white font-mono">{{ m.value }} {{ m.unit }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-4 mt-4 border-t border-slate-800">
          <button @click="close" class="px-4 py-2 text-xs font-semibold text-slate-950 bg-purple-400 hover:bg-purple-300 rounded-xl">
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { PhysicalAssessmentDetail } from '../types'

defineProps<{
  isOpen: boolean
  assessment?: PhysicalAssessmentDetail | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function close() {
  emit('close')
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>
