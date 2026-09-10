<template>
  <div class="space-y-6 max-w-4xl mx-auto pb-12">
    <!-- Top Bar -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider block">
          Treino em Andamento
        </span>
        <h1 class="text-2xl font-black text-white tracking-tight">
          {{ session?.workout_day_name || session?.workout_plan_name || 'Sessão de Treino' }}
        </h1>
      </div>

      <button
        @click="isCompleteModalOpen = true"
        class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-1.5"
      >
        <span>🏁</span>
        <span>Concluir Treino</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-20 text-center text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="mt-4 text-sm">Carregando sessão...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
    </div>

    <!-- Live Session Content -->
    <div v-else-if="session" class="space-y-6">
      <!-- Workout Timer -->
      <WorkoutTimer ref="timerRef" :started-at="session.started_at" />

      <!-- Progress Bar -->
      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2">
        <div class="flex justify-between text-xs">
          <span class="text-slate-400 font-medium">Progresso do Treino</span>
          <span class="text-emerald-400 font-bold font-mono">{{ session.completion_percentage }}% concluído</span>
        </div>
        <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
          <div 
            class="h-full bg-emerald-400 rounded-full transition-all duration-300"
            :style="{ width: session.completion_percentage + '%' }"
          ></div>
        </div>
      </div>

      <!-- Exercise Cards -->
      <div class="space-y-6">
        <div 
          v-for="(ex, exIdx) in session.exercises" 
          :key="ex.id"
          class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-4"
        >
          <!-- Exercise Header -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold text-xs flex items-center justify-center">
                  {{ exIdx + 1 }}
                </span>
                <h3 class="text-lg font-bold text-white">{{ ex.exercise_name }}</h3>
              </div>
              <p class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                <span>{{ ex.muscle_group }}</span>
                <span>•</span>
                <span>{{ ex.equipment || 'Livre' }}</span>
              </p>
            </div>

            <div v-if="ex.video_url">
              <a 
                :href="ex.video_url" 
                target="_blank" 
                class="text-xs text-emerald-400 hover:underline flex items-center gap-1 font-medium"
              >
                <span>▶️</span> Ver Vídeo de Execução
              </a>
            </div>
          </div>

          <!-- Sets Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="text-slate-500 border-b border-slate-800 text-[11px] uppercase tracking-wider">
                  <th class="py-2 px-2">Série</th>
                  <th class="py-2 px-2">Prescrito</th>
                  <th class="py-2 px-2">Carga (kg)</th>
                  <th class="py-2 px-2">Reps</th>
                  <th class="py-2 px-2">RPE</th>
                  <th class="py-2 px-2 text-right">Ação</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60">
                <tr 
                  v-for="set in ex.sets" 
                  :key="set.id"
                  class="transition-colors"
                  :class="set.completed ? 'bg-emerald-500/5' : ''"
                >
                  <td class="py-3 px-2 font-mono font-bold text-white">
                    #{{ set.set_number }}
                  </td>
                  <td class="py-3 px-2 text-slate-400 font-mono">
                    {{ set.reps_prescribed || '-' }} reps <span v-if="set.weight_prescribed">• {{ set.weight_prescribed }}</span>
                  </td>
                  <td class="py-3 px-2">
                    <input 
                      v-model.number="set.weight_performed" 
                      type="number" 
                      step="0.5" 
                      placeholder="kg"
                      class="w-20 px-2.5 py-1 bg-slate-950 border border-slate-700 rounded-lg text-white font-mono text-center focus:outline-none focus:border-emerald-500"
                    />
                  </td>
                  <td class="py-3 px-2">
                    <input 
                      v-model.number="set.reps_performed" 
                      type="number" 
                      min="1" 
                      placeholder="reps"
                      class="w-16 px-2.5 py-1 bg-slate-950 border border-slate-700 rounded-lg text-white font-mono text-center focus:outline-none focus:border-emerald-500"
                    />
                  </td>
                  <td class="py-3 px-2">
                    <input 
                      v-model.number="set.rpe" 
                      type="number" 
                      min="1" 
                      max="10" 
                      placeholder="1-10"
                      class="w-14 px-2.5 py-1 bg-slate-950 border border-slate-700 rounded-lg text-white font-mono text-center focus:outline-none focus:border-emerald-500"
                    />
                  </td>
                  <td class="py-3 px-2 text-right">
                    <button
                      @click="toggleSet(ex.id, set)"
                      class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                      :class="set.completed 
                        ? 'bg-emerald-400 text-slate-950 shadow-md shadow-emerald-500/20' 
                        : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                      {{ set.completed ? '✓ Concluído' : 'Marcar Série' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Conclusão de Treino -->
    <div v-if="isCompleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="isCompleteModalOpen = false"></div>

        <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
          <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-500/10 text-emerald-400 text-2xl flex items-center justify-center">
              🏆
            </div>
            <h3 class="text-xl font-bold text-white">Finalizar Treino</h3>
            <p class="text-xs text-slate-400">
              Parabéns pela dedicação! Como foi a sessão hoje?
            </p>
          </div>

          <div class="mt-4 space-y-3">
            <label class="block text-xs font-medium text-slate-300">Feedback / Percepção de Esforço (Opcional)</label>
            <textarea 
              v-model="feedbackNotes" 
              rows="3" 
              placeholder="Ex: Treino muito intenso, consegui subir carga no agachamento, ombro sem dores..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-emerald-500"
            ></textarea>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
              <button 
                @click="isCompleteModalOpen = false"
                class="px-4 py-2 text-xs text-slate-400 hover:text-white rounded-xl"
              >
                Voltar
              </button>
              <button 
                @click="completeSession"
                :disabled="completing"
                class="px-5 py-2 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 disabled:opacity-50"
              >
                {{ completing ? 'Concluindo...' : 'Confirmar e Salvar' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { workoutSessionService } from '../services/workoutSessionService'
import type { WorkoutSessionDetail, WorkoutSetExecutionItem } from '../types/session'
import WorkoutTimer from '../components/WorkoutTimer.vue'

const route = useRoute()
const router = useRouter()
const sessionId = computed(() => route.params.id as string)

const session = ref<WorkoutSessionDetail | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const timerRef = ref<any>(null)

const isCompleteModalOpen = ref(false)
const feedbackNotes = ref('')
const completing = ref(false)

async function loadSession() {
  loading.value = true
  errorMessage.value = ''
  try {
    session.value = await workoutSessionService.getById(sessionId.value)
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar sessão de treino.'
  } finally {
    loading.value = false
  }
}

async function toggleSet(exerciseExecutionId: string, set: WorkoutSetExecutionItem) {
  const newStatus = !set.completed
  set.completed = newStatus

  try {
    await workoutSessionService.recordSet(sessionId.value, exerciseExecutionId, {
      set_number: set.set_number,
      reps_performed: set.reps_performed || undefined,
      weight_performed: set.weight_performed || undefined,
      rpe: set.rpe || undefined,
      rest_seconds: set.rest_seconds || undefined,
      completed: newStatus,
    })

    // Se concluiu a série, aciona o timer de descanso no WorkoutTimer
    if (newStatus && timerRef.value) {
      timerRef.value.startRest(set.rest_seconds || 60)
    }

    // Recalcula % de conclusão local
    calculateCompletion()
  } catch (err) {
    console.error('Erro ao gravar set:', err)
  }
}

function calculateCompletion() {
  if (!session.value) return
  let totalSets = 0
  let completedSets = 0

  session.value.exercises.forEach(ex => {
    ex.sets.forEach(s => {
      totalSets++
      if (s.completed) completedSets++
    })
  })

  session.value.completion_percentage = totalSets > 0 ? Math.round((completedSets / totalSets) * 100) : 0
}

async function completeSession() {
  completing.value = true
  try {
    await workoutSessionService.complete(sessionId.value, feedbackNotes.value || undefined)
    router.push('/portal/history')
  } catch (err: any) {
    alert(err.message || 'Erro ao concluir sessão.')
  } finally {
    completing.value = false
  }
}

onMounted(() => {
  loadSession()
})
</script>
