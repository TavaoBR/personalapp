<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
          <span>Histórico de Treinos</span>
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            {{ history.length }} sessões
          </span>
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Acompanhe todas as sessões executadas, adesão e percepções de treino.
        </p>
      </div>

      <!-- Filtro de Período -->
      <div class="flex items-center gap-2">
        <select 
          v-model="selectedPeriod" 
          @change="loadHistory"
          class="px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-emerald-500"
        >
          <option value="">Todo o Período</option>
          <option value="today">Hoje</option>
          <option value="week">Últimos 7 Dias</option>
          <option value="30d">Últimos 30 Dias</option>
          <option value="60d">Últimos 60 Dias</option>
          <option value="90d">Últimos 90 Dias</option>
        </select>
      </div>
    </div>

    <!-- Quick Stats -->
    <div v-if="history.length > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
        <span class="text-slate-500 text-[11px] block uppercase font-semibold">Total de Sessões</span>
        <span class="text-2xl font-bold text-white font-mono">{{ history.length }}</span>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
        <span class="text-slate-500 text-[11px] block uppercase font-semibold">Média de Conclusão</span>
        <span class="text-2xl font-bold text-emerald-400 font-mono">{{ averageCompletion }}%</span>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
        <span class="text-slate-500 text-[11px] block uppercase font-semibold">Duração Média</span>
        <span class="text-2xl font-bold text-cyan-400 font-mono">{{ averageDuration }} min</span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-20 text-center text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="mt-4 text-sm">Carregando histórico...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="history.length === 0" class="p-12 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-800/80 text-slate-400 flex items-center justify-center text-2xl mb-4">
        📅
      </div>
      <h3 class="text-lg font-semibold text-white">Nenhum treino no período</h3>
      <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
        Não há sessões de treino registradas para o filtro selecionado.
      </p>
    </div>

    <!-- Sessions Timeline List -->
    <div v-else class="space-y-4">
      <div 
        v-for="item in history" 
        :key="item.id"
        class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
      >
        <div class="space-y-1.5">
          <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-400 font-bold text-xs flex items-center justify-center">
              🏋️
            </span>
            <h3 class="font-bold text-white text-base">
              {{ item.workout_day_name || 'Treino Concluído' }}
            </h3>
            <span 
              class="px-2 py-0.5 text-[10px] font-semibold rounded-full border"
              :class="item.status === 'COMPLETED' 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' 
                : 'bg-amber-500/10 text-amber-400 border-amber-500/30'"
            >
              {{ item.status === 'COMPLETED' ? 'Concluído' : item.status }}
            </span>
          </div>

          <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 ml-11">
            <span>📅 {{ formatDateTime(item.started_at) }}</span>
            <span v-if="item.duration_minutes">⏱️ {{ item.duration_minutes }} minutos</span>
            <span>💪 {{ item.exercises_count }} exercícios</span>
          </div>

          <p v-if="item.feedback_notes" class="text-xs text-slate-300 italic ml-11 pt-1">
            "{{ item.feedback_notes }}"
          </p>
        </div>

        <!-- Completion Indicator -->
        <div class="flex items-center gap-3 shrink-0">
          <div class="text-right">
            <span class="text-xs text-slate-500 block">Adesão</span>
            <span class="text-sm font-bold text-emerald-400 font-mono">{{ item.completion_percentage }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { workoutSessionService } from '../services/workoutSessionService'
import type { WorkoutSessionHistoryItem } from '../types/session'

const history = ref<WorkoutSessionHistoryItem[]>([])
const loading = ref(true)
const errorMessage = ref('')
const selectedPeriod = ref('')

async function loadHistory() {
  loading.value = true
  errorMessage.value = ''
  try {
    history.value = await workoutSessionService.getMyHistory(selectedPeriod.value || undefined)
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar histórico de treinos.'
  } finally {
    loading.value = false
  }
}

const averageCompletion = computed(() => {
  if (history.value.length === 0) return 0
  const sum = history.value.reduce((acc, h) => acc + (h.completion_percentage || 0), 0)
  return Math.round(sum / history.value.length)
})

const averageDuration = computed(() => {
  const valid = history.value.filter(h => h.duration_minutes !== null && h.duration_minutes !== undefined)
  if (valid.length === 0) return 0
  const sum = valid.reduce((acc, h) => acc + (h.duration_minutes || 0), 0)
  return Math.round(sum / valid.length)
})

function formatDateTime(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  loadHistory()
})
</script>
