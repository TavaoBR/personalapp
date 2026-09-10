<template>
  <StudentLayout>
    <div class="space-y-6 max-w-2xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between gap-4">
        <div>
          <span class="text-xs font-semibold text-purple-400 uppercase tracking-wider">Minha Evolução</span>
          <h1 class="text-2xl font-bold font-display text-slate-900 dark:text-white mt-0.5">
            Progresso & Conquistas
          </h1>
        </div>

        <button
          v-if="activeTab === 'metricas'"
          @click="isRecordModalOpen = true"
          class="px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl shadow-lg shadow-purple-600/20 transition-all flex items-center gap-1.5"
        >
          <span>⚖️</span>
          <span>Pesar Hoje</span>
        </button>
      </div>

      <!-- Navigation Tabs (Métricas / Recordes PRs / Treinos) -->
      <div class="grid grid-cols-3 gap-2 bg-slate-900/80 p-1.5 rounded-2xl border border-slate-800 text-xs font-semibold">
        <button
          @click="activeTab = 'metricas'"
          class="py-2 rounded-xl transition-all flex items-center justify-center gap-1.5"
          :class="activeTab === 'metricas' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'text-slate-400 hover:text-white'"
        >
          <span>⚖️</span>
          <span>Corporal</span>
        </button>
        <button
          @click="activeTab = 'prs'"
          class="py-2 rounded-xl transition-all flex items-center justify-center gap-1.5"
          :class="activeTab === 'prs' ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20' : 'text-slate-400 hover:text-white'"
        >
          <span>🏆</span>
          <span>Recordes</span>
        </button>
        <button
          @click="activeTab = 'treinos'"
          class="py-2 rounded-xl transition-all flex items-center justify-center gap-1.5"
          :class="activeTab === 'treinos' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white'"
        >
          <span>📊</span>
          <span>Treinos</span>
        </button>
      </div>

      <!-- TAB 1: MÉTRICAS CORPORAIS -->
      <div v-if="activeTab === 'metricas'" class="space-y-6">
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
        <div v-if="loadingMetrics" class="py-16 text-center text-slate-400">
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
      </div>

      <!-- TAB 2: RECORDES PESSOAIS (PRs) -->
      <div v-else-if="activeTab === 'prs'" class="space-y-4">
        <PRsListCard
          :prs="prs"
          :loading="loadingPRs"
        />
      </div>

      <!-- TAB 3: VOLUME & FREQUÊNCIA DE TREINOS -->
      <div v-else-if="activeTab === 'treinos'" class="space-y-4">
        <PeriodComparisonCard
          :analytics="trainingAnalytics"
          :loading="loadingAnalytics"
          :selected-days="analyticsDays"
          @select-days="loadMyAnalytics"
        />
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
import { ref, onMounted, watch } from 'vue'
import StudentLayout from '@/shared/layouts/StudentLayout.vue'
import { assessmentService } from '@/modules/assessments/services/assessmentService'
import type { StudentProgressSummary } from '@/modules/assessments/types'
import { analyticsService } from '@/modules/analytics/services/analyticsService'
import type { StudentAnalyticsResponse, PersonalRecord } from '@/modules/analytics/types'
import { useAuthStore } from '@/stores/auth'
import RecordMetricModal from '@/modules/assessments/components/RecordMetricModal.vue'
import PRsListCard from '@/modules/analytics/components/PRsListCard.vue'
import PeriodComparisonCard from '@/modules/analytics/components/PeriodComparisonCard.vue'

const authStore = useAuthStore()

const activeTab = ref<'metricas' | 'prs' | 'treinos'>('metricas')

const selectedMetric = ref('WEIGHT')
const progress = ref<StudentProgressSummary | null>(null)
const loadingMetrics = ref(true)
const isRecordModalOpen = ref(false)

const prs = ref<PersonalRecord[]>([])
const loadingPRs = ref(false)

const trainingAnalytics = ref<StudentAnalyticsResponse | null>(null)
const loadingAnalytics = ref(false)
const analyticsDays = ref(30)

const metricOptions = [
  { code: 'WEIGHT', label: 'Peso Corporal' },
  { code: 'BODY_FAT', label: '% Gordura' },
  { code: 'WAIST', label: 'Cintura' },
  { code: 'ARM_RIGHT', label: 'Braço' },
]

async function loadProgress() {
  loadingMetrics.value = true
  try {
    progress.value = await assessmentService.getMyProgress(selectedMetric.value)
  } catch (err) {
    console.error('Erro ao carregar progresso:', err)
  } finally {
    loadingMetrics.value = false
  }
}

async function loadMyPRs() {
  loadingPRs.value = true
  try {
    prs.value = await analyticsService.getMyPRs()
  } catch (err) {
    console.error('Erro ao carregar recordes:', err)
  } finally {
    loadingPRs.value = false
  }
}

async function loadMyAnalytics(days: number = 30) {
  analyticsDays.value = days
  loadingAnalytics.value = true
  try {
    const now = new Date()
    const startA = new Date(now.getTime() - days * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    const endA = now.toISOString().split('T')[0]
    const startB = new Date(now.getTime() - days * 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    const endB = startA

    trainingAnalytics.value = await analyticsService.getMyAnalytics({
      start_a: startA,
      end_a: endA,
      start_b: startB,
      end_b: endB
    })
  } catch (err) {
    console.error('Erro ao carregar métricas de treino:', err)
  } finally {
    loadingAnalytics.value = false
  }
}

function changeMetric(code: string) {
  selectedMetric.value = code
  loadProgress()
}

watch(activeTab, (tab) => {
  if (tab === 'metricas') {
    loadProgress()
  } else if (tab === 'prs') {
    loadMyPRs()
  } else if (tab === 'treinos') {
    loadMyAnalytics(analyticsDays.value)
  }
})

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

onMounted(() => {
  loadProgress()
})
</script>
