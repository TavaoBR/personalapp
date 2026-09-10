<template>
  <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 backdrop-blur-md space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-base">
          📊
        </div>
        <div>
          <h3 class="text-base font-bold text-white leading-none">Aderência & Carga de Treino</h3>
          <p class="text-xs text-slate-400 mt-0.5">
            Comparativo entre o período atual e o ciclo imediatamente anterior
          </p>
        </div>
      </div>

      <!-- Period selector tabs -->
      <div class="flex items-center gap-1 bg-slate-950/80 p-1 rounded-xl border border-slate-800/80 self-start">
        <button
          v-for="d in [14, 30, 60]"
          :key="d"
          @click="$emit('select-days', d)"
          class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all"
          :class="selectedDays === d 
            ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' 
            : 'text-slate-400 hover:text-white'"
        >
          {{ d }} dias
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-10 text-center text-xs text-slate-400">
      <div class="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Calculando métricas do período...
    </div>

    <!-- Data Grid -->
    <div v-else-if="analytics" class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- 1. Total Sessões -->
        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
          <span class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 block">
            Sessões Concluídas
          </span>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-black text-white font-mono">
              {{ analytics.period_a.completed_sessions }}
              <span class="text-xs text-slate-500 font-normal">/ {{ analytics.period_a.total_sessions }}</span>
            </span>
            <span 
              v-if="analytics.comparison"
              class="text-xs font-bold font-mono px-2 py-0.5 rounded-full border"
              :class="analytics.comparison.diff_sessions >= 0 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
            >
              {{ analytics.comparison.diff_sessions >= 0 ? '+' : '' }}{{ analytics.comparison.diff_sessions }}
            </span>
          </div>
          <span class="text-[10px] text-slate-500 block">vs período anterior</span>
        </div>

        <!-- 2. Volume Total de Carga (kg) -->
        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
          <span class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 block">
            Volume Total de Carga
          </span>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-black text-indigo-400 font-mono">
              {{ formatVolume(analytics.period_a.total_volume_kg) }}
            </span>
            <span 
              v-if="analytics.comparison"
              class="text-xs font-bold font-mono px-2 py-0.5 rounded-full border"
              :class="analytics.comparison.diff_volume >= 0 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
            >
              {{ analytics.comparison.diff_volume >= 0 ? '+' : '' }}{{ formatVolume(analytics.comparison.diff_volume) }}
            </span>
          </div>
          <span class="text-[10px] text-slate-500 block">kg levantados (reps × carga)</span>
        </div>

        <!-- 3. Taxa de Conclusão (%) -->
        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
          <span class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 block">
            Taxa Média de Conclusão
          </span>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-black text-white font-mono">
              {{ analytics.period_a.completion_rate }}%
            </span>
            <span 
              v-if="analytics.comparison"
              class="text-xs font-bold font-mono px-2 py-0.5 rounded-full border"
              :class="analytics.comparison.diff_completion_rate >= 0 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
            >
              {{ analytics.comparison.diff_completion_rate >= 0 ? '+' : '' }}{{ analytics.comparison.diff_completion_rate }}%
            </span>
          </div>
          <span class="text-[10px] text-slate-500 block">fidelidade ao prescrito</span>
        </div>

        <!-- 4. Duração Média -->
        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
          <span class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 block">
            Duração Média
          </span>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-black text-white font-mono">
              {{ analytics.period_a.avg_duration_minutes }}
              <span class="text-xs text-slate-500 font-normal">min</span>
            </span>
            <span class="text-xs text-slate-400">⏱️</span>
          </div>
          <span class="text-[10px] text-slate-500 block">por sessão realizada</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { StudentAnalyticsResponse } from '../types'

defineProps<{
  analytics: StudentAnalyticsResponse | null
  loading?: boolean
  selectedDays: number
}>()

defineEmits<{
  (e: 'select-days', days: number): void
}>()

function formatVolume(val: number): string {
  if (val >= 1000) {
    return `${(val / 1000).toFixed(1)}k kg`
  }
  return `${val} kg`
}
</script>
