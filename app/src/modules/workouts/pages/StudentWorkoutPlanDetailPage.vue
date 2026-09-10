<template>
  <div class="space-y-6 max-w-5xl">
    <!-- Top Actions -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <button 
          @click="$router.back()"
          class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors text-xs flex items-center gap-1.5"
        >
          <span>←</span>
          <span>Voltar</span>
        </button>
        <h1 class="text-2xl font-bold text-white tracking-tight">
          Ficha de Treino
        </h1>
      </div>

      <div v-if="plan" class="flex items-center gap-2">
        <button
          v-if="plan.status !== 'ACTIVE'"
          @click="activatePlan"
          :disabled="actionLoading"
          class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-all"
        >
          Ativar Esta Ficha
        </button>
        <button
          v-if="plan.status === 'ACTIVE'"
          @click="archivePlan"
          :disabled="actionLoading"
          class="px-4 py-2 text-xs font-semibold text-rose-400 hover:bg-rose-500/10 border border-rose-500/30 rounded-xl transition-all"
        >
          Arquivar Ficha
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Carregando ficha de treino...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
    </div>

    <!-- Plan Content -->
    <div v-else-if="plan" class="space-y-6">
      <!-- Header Card -->
      <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <h2 class="text-xl font-bold text-white">{{ plan.name }}</h2>
              <span 
                class="px-2.5 py-0.5 text-xs font-semibold rounded-full border"
                :class="plan.status === 'ACTIVE' 
                  ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' 
                  : 'bg-slate-500/10 text-slate-400 border-slate-500/30'"
              >
                {{ plan.status === 'ACTIVE' ? 'Ativa' : plan.status }}
              </span>
            </div>
            <p v-if="plan.template_name" class="text-xs text-slate-400 mt-1">
              Baseada no modelo: <span class="text-slate-300 font-medium">{{ plan.template_name }}</span>
            </p>
          </div>

          <div class="text-xs text-slate-500">
            Versão {{ plan.current_version?.version_number || 1 }} • Criada em {{ formatDate(plan.created_at) }}
          </div>
        </div>
      </div>

      <!-- Days & Exercises -->
      <div class="space-y-4">
        <div 
          v-for="(day, dIdx) in plan.current_version?.days || []" 
          :key="dIdx"
          class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 shadow-lg space-y-4"
        >
          <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold text-xs flex items-center justify-center">
                {{ dIdx + 1 }}
              </span>
              {{ day.name }}
            </h3>
            <span class="text-xs text-slate-400 font-medium">
              {{ day.exercises.length }} exercícios
            </span>
          </div>

          <div class="divide-y divide-slate-800/50">
            <div 
              v-for="(ex, exIdx) in day.exercises" 
              :key="exIdx"
              class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
            >
              <div class="space-y-1">
                <span class="font-semibold text-white text-sm block">
                  {{ exIdx + 1 }}. {{ ex.name || 'Exercício' }}
                </span>
                <p v-if="ex.notes" class="text-[11px] text-slate-400 italic">
                  {{ ex.notes }}
                </p>
              </div>

              <!-- Badges de Séries / Reps / Carga -->
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-200 font-mono">
                  <strong>{{ ex.sets }}</strong> séries
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-200 font-mono">
                  <strong>{{ ex.reps }}</strong> reps
                </span>
                <span v-if="ex.weight" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-mono">
                  {{ ex.weight }}
                </span>
                <span v-if="ex.rest_seconds" class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-400 font-mono">
                  ⏱️ {{ ex.rest_seconds }}s
                </span>
                <span v-if="ex.rpe" class="px-2.5 py-1 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 font-mono">
                  RPE {{ ex.rpe }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { workoutPlanService } from '../services/workoutPlanService'
import type { StudentWorkoutPlanSummary } from '../types'

const route = useRoute()
const planId = computed(() => route.params.id as string)

const plan = ref<StudentWorkoutPlanSummary | null>(null)
const loading = ref(true)
const actionLoading = ref(false)
const errorMessage = ref('')

async function loadPlan() {
  loading.value = true
  errorMessage.value = ''
  try {
    plan.value = await workoutPlanService.getById(planId.value)
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar detalhes da ficha.'
  } finally {
    loading.value = false
  }
}

async function activatePlan() {
  actionLoading.value = true
  try {
    await workoutPlanService.activate(planId.value)
    if (plan.value) plan.value.status = 'ACTIVE'
  } catch (err: any) {
    alert(err.message || 'Erro ao ativar ficha.')
  } finally {
    actionLoading.value = false
  }
}

async function archivePlan() {
  if (!confirm('Deseja arquivar esta ficha de treino?')) return
  actionLoading.value = true
  try {
    await workoutPlanService.archive(planId.value)
    if (plan.value) plan.value.status = 'ARCHIVED'
  } catch (err: any) {
    alert(err.message || 'Erro ao arquivar ficha.')
  } finally {
    actionLoading.value = false
  }
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

onMounted(() => {
  loadPlan()
})
</script>
