<template>
  <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 backdrop-blur-md space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-base">
          🏆
        </div>
        <div>
          <h3 class="text-base font-bold text-white leading-none">Recordes Pessoais (PRs)</h3>
          <p class="text-xs text-slate-400 mt-0.5">Maiores cargas registradas em cada exercício</p>
        </div>
      </div>
      <span v-if="prs.length > 0" class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
        {{ prs.length }} marcas
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-8 text-center text-xs text-slate-400">
      <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Carregando recordes pessoais...
    </div>

    <!-- Empty State -->
    <div v-else-if="prs.length === 0" class="py-8 text-center text-slate-400 border border-dashed border-slate-800/80 rounded-2xl">
      <p class="text-xs font-medium">Nenhum recorde registrado ainda.</p>
      <p class="text-[11px] text-slate-500 mt-0.5">As marcas são computadas automaticamente nas execuções de treino.</p>
    </div>

    <!-- PRs Grid / List -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-96 overflow-y-auto pr-1">
      <div 
        v-for="pr in prs" 
        :key="pr.exercise_name"
        class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/90 hover:border-amber-500/30 transition-all flex items-center justify-between group"
      >
        <div class="space-y-0.5">
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-white group-hover:text-amber-400 transition-colors">
              {{ pr.exercise_name }}
            </span>
          </div>
          <div class="flex items-center gap-2 text-[10px] text-slate-400">
            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-medium">
              {{ pr.muscle_group }}
            </span>
            <span>📅 {{ formatDate(pr.date) }}</span>
          </div>
        </div>

        <div class="text-right">
          <div class="text-base font-black text-amber-400 font-mono tracking-tight">
            {{ pr.max_weight }} <span class="text-xs font-semibold text-slate-400">kg</span>
          </div>
          <span v-if="pr.reps_at_max" class="text-[10px] text-slate-400 font-medium block">
            {{ pr.reps_at_max }} reps
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { PersonalRecord } from '../types'

defineProps<{
  prs: PersonalRecord[]
  loading?: boolean
}>()

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>
