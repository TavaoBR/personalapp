<template>
  <StudentLayout>
    <div class="space-y-6 max-w-2xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between gap-4">
        <div>
          <span class="text-xs font-semibold text-purple-400 uppercase tracking-wider">Minha Evolução</span>
          <h1 class="text-2xl font-bold font-display text-slate-900 dark:text-white mt-0.5">
            Métricas Corporais
          </h1>
        </div>

        <button
          @click="isRecordModalOpen = true"
          class="px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl shadow-lg shadow-purple-600/20 transition-all flex items-center gap-1.5"
        >
          <span>⚖️</span>
          <span>Pesar Hoje</span>
        </button>
      </div>

      <!-- Seletor de Métrica -->
      <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 text-xs">
        <button
          v-for="m in metricOptions"
          :key="m.code"
          @click="changeMetric(m.code)"
          class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap border"
          :class="selectedMetric === m.code 
            ? 'bg-purple-600 text-white border-purple-600 font-semibold shadow-md shadow-purple-600/20' 
            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-white hover:border-slate-700'"
        >
          {{ m.label }}
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="py-16 text-center text-slate-400">
        <div class="w-8 h-8 border-3 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="mt-3 text-xs">Carregando métricas de evolução...</p>
      </div>

      <!-- Main Progress Display -->
      <div v-else-if="progress" class="space-y-6">
        <!-- Big Delta Banner -->
        <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 backdrop-blur-sm shadow-xl space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400 font-medium">Valor Mais Recente</span>
            <span 
              v-if="progress.delta !== null"
              class="px-2.5 py-0.5 text-xs font-bold rounded-full border font-mono"
              :class="(progress.delta || 0) <= 0 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
            >
              {{ (progress.delta || 0) > 0 ? '+' : '' }}{{ progress.delta }} {{ progress.unit }} ({{ progress.delta_percentage }}%)
            </span>
          </div>

          <div class="flex items-baseline gap-2">
            <span class="text-4xl font-black text-white font-mono tracking-tight">
              {{ progress.latest_value ?? '-' }}
            </span>
            <span class="text-base font-semibold text-purple-400">{{ progress.unit }}</span>
          </div>

          <!-- Mini Cards: Inicial, Mínimo, Máximo -->
          <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-800/80 text-xs">
            <div>
              <span class="text-[10px] text-slate-500 block uppercase">Inicial</span>
              <span class="font-bold text-slate-200 font-mono">{{ progress.initial_value ?? '-' }} {{ progress.unit }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block uppercase">Mínimo</span>
              <span class="font-bold text-slate-200 font-mono">{{ progress.min_value ?? '-' }} {{ progress.unit }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block uppercase">Máximo</span>
              <span class="font-bold text-slate-200 font-mono">{{ progress.max_value ?? '-' }} {{ progress.unit }}</span>
            </div>
          </div>
        </div>

        <!-- Histórico de Medições -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-3">
          <h3 class="text-sm font-bold text-white mb-2">Registros Anteriores</h3>

          <div v-if="progress.data_points.length === 0" class="py-6 text-center text-xs text-slate-500">
            Nenhum registro encontrado para esta métrica.
          </div>

          <div v-else class="divide-y divide-slate-800/60">
            <div 
              v-for="(point, idx) in progress.data_points" 
              :key="idx"
              class="py-2.5 flex items-center justify-between text-xs"
            >
              <div class="flex items-center gap-2">
                <span class="text-slate-400">{{ formatDate(point.date) }}</span>
                <span 
                  class="px-1.5 py-0.5 text-[9px] rounded font-semibold uppercase"
                  :class="point.source === 'STUDENT' ? 'bg-slate-800 text-slate-400' : 'bg-purple-500/20 text-purple-300'"
                >
                  {{ point.source === 'STUDENT' ? 'Pesagem Própria' : 'Avaliação Técnica' }}
                </span>
              </div>
              <span class="font-bold text-white font-mono text-sm">
                {{ point.value }} {{ progress.unit }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal de Registro de Pesagem -->
      <RecordMetricModal
        v-if="authStore.user"
        :is-open="isRecordModalOpen"
        :student-id="authStore.user.id"
        @close="isRecordModalOpen = false"
        @recorded="loadProgress"
      />
    </div>
  </StudentLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import StudentLayout from '@/shared/layouts/StudentLayout.vue'
import { assessmentService } from '@/modules/assessments/services/assessmentService'
import type { StudentProgressSummary } from '@/modules/assessments/types'
import { useAuthStore } from '@/stores/auth'
import RecordMetricModal from '@/modules/assessments/components/RecordMetricModal.vue'

const authStore = useAuthStore()

const selectedMetric = ref('WEIGHT')
const progress = ref<StudentProgressSummary | null>(null)
const loading = ref(true)
const isRecordModalOpen = ref(false)

const metricOptions = [
  { code: 'WEIGHT', label: 'Peso Corporal' },
  { code: 'BODY_FAT', label: '% Gordura' },
  { code: 'WAIST', label: 'Cintura' },
  { code: 'ARM_RIGHT', label: 'Braço' },
]

async function loadProgress() {
  loading.value = true
  try {
    progress.value = await assessmentService.getMyProgress(selectedMetric.value)
  } catch (err) {
    console.error('Erro ao carregar progresso:', err)
  } finally {
    loading.value = false
  }
}

function changeMetric(code: string) {
  selectedMetric.value = code
  loadProgress()
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

onMounted(() => {
  loadProgress()
})
</script>
