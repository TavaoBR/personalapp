<template>
  <div v-if="isOpen && goal" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 shrink-0">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-base font-bold text-white">{{ goal.title }}</span>
              <span 
                class="px-2 py-0.5 text-[10px] font-bold rounded-full border"
                :class="statusClass(goal.status)"
              >
                {{ statusLabel(goal.status) }}
              </span>
            </div>
            <p v-if="goal.description" class="text-xs text-slate-400 mt-1">
              "{{ goal.description }}"
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Content -->
        <div class="mt-4 flex-1 overflow-y-auto pr-1 space-y-5">
          <!-- Big Progress Banner -->
          <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-400 font-medium">Evolução do Objetivo</span>
              <span class="text-xs font-black text-rose-400 font-mono">
                {{ goal.progress_percentage }}%
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
              <div 
                class="h-full rounded-full transition-all duration-500 bg-gradient-to-r from-rose-500 via-pink-500 to-amber-400"
                :style="{ width: `${Math.min(100, Math.max(0, goal.progress_percentage))}%` }"
              ></div>
            </div>

            <!-- 3 Column Metrics (Inicial, Atual, Alvo) -->
            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-800/80 text-center text-xs">
              <div>
                <span class="text-[10px] text-slate-500 block uppercase">Inicial</span>
                <span class="font-bold text-slate-300 font-mono">
                  {{ goal.initial_value ?? '-' }} {{ goal.unit }}
                </span>
              </div>
              <div>
                <span class="text-[10px] text-slate-500 block uppercase">Atual</span>
                <span class="font-bold text-rose-400 font-mono text-sm">
                  {{ goal.current_value ?? goal.initial_value ?? '-' }} {{ goal.unit }}
                </span>
              </div>
              <div>
                <span class="text-[10px] text-slate-500 block uppercase">Alvo</span>
                <span class="font-bold text-emerald-400 font-mono text-sm">
                  {{ goal.target_value ?? '-' }} {{ goal.unit }}
                </span>
              </div>
            </div>
          </div>

          <!-- Datas & Prazos -->
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800">
              <span class="text-[10px] text-slate-500 block uppercase">Início</span>
              <span class="font-semibold text-slate-200">📅 {{ formatDate(goal.start_date) }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800">
              <span class="text-[10px] text-slate-500 block uppercase">Prazo Previsto</span>
              <span class="font-semibold text-slate-200">
                {{ goal.target_date ? `🎯 ${formatDate(goal.target_date)}` : 'Sem prazo definido' }}
              </span>
            </div>
          </div>

          <!-- Histórico de Check-ins / Medições -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">
              Histórico de Registros ({{ goal.progress_history?.length || 0 }})
            </h4>

            <div v-if="!goal.progress_history || goal.progress_history.length === 0" class="py-6 text-center text-xs text-slate-500 border border-dashed border-slate-800 rounded-2xl">
              Nenhuma medição registrada ainda nesta meta.
            </div>

            <div v-else class="space-y-2">
              <div 
                v-for="p in goal.progress_history" 
                :key="p.id"
                class="p-3 rounded-xl bg-slate-950/70 border border-slate-800/80 flex items-center justify-between text-xs"
              >
                <div class="space-y-0.5">
                  <div class="flex items-center gap-2">
                    <span class="text-slate-400">{{ formatDate(p.recorded_at) }}</span>
                    <span class="px-1.5 py-0.5 text-[9px] rounded font-semibold uppercase bg-slate-800 text-slate-300">
                      {{ p.source === 'TRAINER' ? 'Personal' : 'Aluno' }}
                    </span>
                  </div>
                  <p v-if="p.notes" class="text-slate-300 italic text-[11px]">"{{ p.notes }}"</p>
                </div>

                <div class="text-right">
                  <span class="text-sm font-bold text-rose-400 font-mono">
                    {{ p.value }} {{ goal.unit }}
                  </span>
                  <span v-if="p.previous_value !== null && p.previous_value !== undefined" class="text-[10px] text-slate-500 block font-mono">
                    de {{ p.previous_value }} {{ goal.unit }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions: Mudar Status da Meta -->
        <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-800 shrink-0">
          <div class="flex items-center gap-2">
            <button
              v-if="goal.status === 'IN_PROGRESS'"
              type="button"
              @click="updateStatus('COMPLETED')"
              :disabled="updating"
              class="px-3 py-1.5 text-xs font-semibold text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 rounded-xl transition-all"
            >
              ✓ Concluir Meta
            </button>
            <button
              v-if="goal.status === 'IN_PROGRESS'"
              type="button"
              @click="updateStatus('ABANDONED')"
              :disabled="updating"
              class="px-3 py-1.5 text-xs font-semibold text-slate-400 hover:text-rose-400 bg-slate-800 rounded-xl transition-all"
            >
              Cancelar Meta
            </button>
            <button
              v-if="goal.status !== 'IN_PROGRESS'"
              type="button"
              @click="updateStatus('IN_PROGRESS')"
              :disabled="updating"
              class="px-3 py-1.5 text-xs font-semibold text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 rounded-xl transition-all"
            >
              Reabrir Meta
            </button>
          </div>

          <button
            type="button"
            @click="close"
            class="px-4 py-1.5 text-xs font-semibold text-slate-400 hover:text-white rounded-xl"
          >
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { goalService } from '../services/goalService'
import type { StudentGoalDetail, GoalStatus } from '../types'

const props = defineProps<{
  isOpen: boolean
  goal: StudentGoalDetail | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'updated'): void
}>()

const updating = ref(false)

function close() {
  emit('close')
}

async function updateStatus(status: GoalStatus) {
  if (!props.goal) return
  updating.value = true
  try {
    await goalService.update(props.goal.id, { status })
    emit('updated')
    close()
  } catch (err: any) {
    alert(err.message || 'Erro ao alterar status da meta.')
  } finally {
    updating.value = false
  }
}

function statusClass(status: GoalStatus): string {
  if (status === 'COMPLETED') return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
  if (status === 'ABANDONED') return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
}

function statusLabel(status: GoalStatus): string {
  if (status === 'COMPLETED') return 'Concluída'
  if (status === 'ABANDONED') return 'Cancelada'
  return 'Em Andamento'
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>
