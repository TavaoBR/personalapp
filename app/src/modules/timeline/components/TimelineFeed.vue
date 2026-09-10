<template>
  <div class="space-y-4">
    <!-- Loading -->
    <div v-if="loading" class="py-12 text-center text-xs text-slate-400">
      <div class="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Carregando linha do tempo...
    </div>

    <!-- Empty State -->
    <div v-else-if="events.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
      <div class="w-12 h-12 mx-auto rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl mb-3">
        ⏳
      </div>
      <h4 class="text-base font-semibold text-white">Nenhum evento registrado</h4>
      <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
        Os eventos de treino, avaliações, metas e anotações são registrados automaticamente nesta linha do tempo.
      </p>
    </div>

    <!-- Timeline List -->
    <div v-else class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-800">
      <div 
        v-for="e in events" 
        :key="e.id"
        class="relative group"
      >
        <!-- Circle Node on timeline line -->
        <div 
          class="absolute -left-6 top-1 w-5 h-5 rounded-full border-2 border-slate-900 flex items-center justify-center text-[10px] shadow-sm transition-transform group-hover:scale-110"
          :class="getNodeColor(e.event_type)"
        >
          <span>{{ getEventIcon(e.event_type) }}</span>
        </div>

        <!-- Event Card -->
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all space-y-2">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
            <div class="flex items-center gap-2">
              <span 
                class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider border font-mono"
                :class="getBadgeClass(e.event_type)"
              >
                {{ formatEventType(e.event_type) }}
              </span>
              <h4 class="text-sm font-bold text-white">{{ e.title }}</h4>
            </div>

            <span class="text-[11px] text-slate-500 font-mono">
              {{ formatDateTime(e.occurred_at) }}
            </span>
          </div>

          <p v-if="e.description" class="text-xs text-slate-300 leading-relaxed">
            {{ e.description }}
          </p>

          <!-- Metadados adicionais se houver -->
          <div v-if="e.metadata && Object.keys(e.metadata).length > 0" class="pt-2 flex flex-wrap gap-2 text-[10px] text-slate-400">
            <span 
              v-for="(val, key) in e.metadata" 
              :key="key"
              class="px-2 py-0.5 rounded-lg bg-slate-950/80 border border-slate-800/80"
            >
              <strong class="text-slate-300">{{ key }}:</strong> {{ val }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { TimelineEvent } from '../types'

defineProps<{
  events: TimelineEvent[]
  loading?: boolean
}>()

function getEventIcon(type: string): string {
  switch (type) {
    case 'WORKOUT_COMPLETED': return '🏋️'
    case 'ASSESSMENT_RECORDED': return '📊'
    case 'METRIC_LOGGED': return '⚖️'
    case 'GOAL_CREATED':
    case 'GOAL_ACHIEVED': return '🎯'
    case 'ANAMNESIS_SUBMITTED': return '📋'
    case 'CHECKIN_SUBMITTED': return '💬'
    case 'NOTE_ADDED': return '📝'
    default: return '⚡'
  }
}

function getNodeColor(type: string): string {
  switch (type) {
    case 'WORKOUT_COMPLETED': return 'bg-emerald-500 text-slate-950'
    case 'ASSESSMENT_RECORDED': return 'bg-purple-500 text-white'
    case 'METRIC_LOGGED': return 'bg-pink-500 text-white'
    case 'GOAL_CREATED':
    case 'GOAL_ACHIEVED': return 'bg-amber-500 text-slate-950'
    case 'ANAMNESIS_SUBMITTED': return 'bg-blue-500 text-white'
    case 'CHECKIN_SUBMITTED': return 'bg-cyan-500 text-slate-950'
    default: return 'bg-indigo-500 text-white'
  }
}

function getBadgeClass(type: string): string {
  switch (type) {
    case 'WORKOUT_COMPLETED': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    case 'ASSESSMENT_RECORDED': return 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    case 'METRIC_LOGGED': return 'bg-pink-500/10 text-pink-400 border-pink-500/20'
    case 'GOAL_CREATED':
    case 'GOAL_ACHIEVED': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'ANAMNESIS_SUBMITTED': return 'bg-blue-500/10 text-blue-400 border-blue-500/20'
    case 'CHECKIN_SUBMITTED': return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20'
    default: return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20'
  }
}

function formatEventType(type: string): string {
  const map: Record<string, string> = {
    WORKOUT_COMPLETED: 'Treino Concluído',
    ASSESSMENT_RECORDED: 'Avaliação Física',
    METRIC_LOGGED: 'Medição Corporal',
    GOAL_CREATED: 'Meta Criada',
    GOAL_ACHIEVED: 'Meta Conquistada',
    ANAMNESIS_SUBMITTED: 'Anamnese',
    CHECKIN_SUBMITTED: 'Check-in',
    NOTE_ADDED: 'Anotação',
    PLAN_PRESCRIBED: 'Ficha Prescrita',
    ENROLLED: 'Matrícula'
  }
  return map[type] || type
}

function formatDateTime(dateStr: string): string {
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
