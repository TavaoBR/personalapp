<template>
  <div v-if="isOpen && plan" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 shrink-0">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>📅</span> {{ plan.title }}
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Início: {{ formatDate(plan.start_date) }} • {{ plan.tasks.length }} tarefas programadas
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Content -->
        <div class="mt-4 flex-1 overflow-y-auto pr-1 space-y-3">
          <div 
            v-for="t in plan.tasks" 
            :key="t.id"
            class="p-3.5 rounded-2xl border transition-all flex items-center justify-between gap-3"
            :class="t.status === 'COMPLETED' 
              ? 'bg-emerald-500/5 border-emerald-500/20' 
              : 'bg-slate-950/70 border-slate-800'"
          >
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <button
                type="button"
                @click="toggleTask(t)"
                class="w-5 h-5 rounded-lg border flex items-center justify-center text-xs transition-all shrink-0"
                :class="t.status === 'COMPLETED' 
                  ? 'bg-emerald-500 border-emerald-500 text-white font-bold' 
                  : 'border-slate-700 hover:border-blue-500 bg-slate-900'"
              >
                <span v-if="t.status === 'COMPLETED'">✓</span>
              </button>

              <div class="min-w-0">
                <span 
                  class="text-xs font-semibold block truncate"
                  :class="t.status === 'COMPLETED' ? 'text-slate-400 line-through' : 'text-white'"
                >
                  {{ t.title }}
                </span>
                <span class="text-[10px] text-slate-500 block">
                  Prazo: {{ formatDate(t.due_date) }}
                  <span v-if="t.completed_at" class="text-emerald-400 ml-1">
                    • Concluído em {{ formatDate(t.completed_at) }}
                  </span>
                </span>
              </div>
            </div>

            <span 
              class="px-2 py-0.5 text-[10px] font-bold rounded-full border shrink-0"
              :class="t.status === 'COMPLETED' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700'"
            >
              {{ t.status === 'COMPLETED' ? 'Concluída' : 'Pendente' }}
            </span>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end pt-3 border-t border-slate-800 shrink-0">
          <button
            type="button"
            @click="close"
            class="px-4 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-xl"
          >
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { followUpService } from '../services/followUpService'
import type { FollowUpPlanDetail, FollowUpTask } from '../types'

const props = defineProps<{
  isOpen: boolean
  plan: FollowUpPlanDetail | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'updated'): void
}>()

function close() {
  emit('close')
}

async function toggleTask(task: FollowUpTask) {
  const newStatus = task.status === 'COMPLETED' ? 'PENDING' : 'COMPLETED'
  try {
    await followUpService.updateTask(task.id, newStatus)
    task.status = newStatus
    task.completed_at = newStatus === 'COMPLETED' ? new Date().toISOString() : null
    emit('updated')
  } catch (err: any) {
    alert(err.message || 'Erro ao atualizar tarefa.')
  }
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>
