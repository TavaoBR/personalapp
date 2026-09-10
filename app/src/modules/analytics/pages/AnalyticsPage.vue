<template>
  <AppLayout>
    <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
          <span>📊</span>
          <span>Analytics & Indicadores Operacionais</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">
          Monitoramento em tempo real de retenção, volume de treinos e carteira de alunos.
        </p>
      </div>

      <button
        @click="loadDashboard"
        :disabled="loading"
        class="px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl transition-all self-start flex items-center gap-2"
      >
        <span :class="{ 'animate-spin': loading }">🔄</span>
        <span>Atualizar Métricas</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !dashboard" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Compilando indicadores operacionais...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium text-sm">{{ errorMessage }}</p>
      <button @click="loadDashboard" class="mt-3 px-4 py-1.5 text-xs bg-rose-500/20 text-rose-300 rounded-xl">
        Tentar Novamente
      </button>
    </div>

    <!-- Dashboard Content -->
    <div v-else-if="dashboard" class="space-y-6">
      <!-- 1. Top KPI Cards (Portfolio Health) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Alunos Ativos / Total -->
        <div class="p-5 rounded-3xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md relative overflow-hidden">
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Carteira de Alunos</span>
            <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm">👥</span>
          </div>
          <div class="mt-4 flex items-baseline gap-2">
            <span class="text-3xl font-black text-white font-mono">{{ dashboard.active_students }}</span>
            <span class="text-xs text-slate-400 font-medium">ativos de {{ dashboard.total_students }}</span>
          </div>
          <div class="mt-3 w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
            <div 
              class="bg-emerald-400 h-full rounded-full transition-all duration-500"
              :style="{ width: `${dashboard.total_students > 0 ? (dashboard.active_students / dashboard.total_students) * 100 : 0}%` }"
            ></div>
          </div>
        </div>

        <!-- Card 2: Alunos Sem Ficha Ativa -->
        <div 
          class="p-5 rounded-3xl bg-slate-900/70 border backdrop-blur-md relative overflow-hidden transition-all"
          :class="dashboard.students_without_active_plan > 0 ? 'border-amber-500/30' : 'border-slate-800/80'"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Sem Ficha de Treino</span>
            <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 text-sm">⚠️</span>
          </div>
          <div class="mt-4 flex items-baseline gap-2">
            <span class="text-3xl font-black text-amber-400 font-mono">{{ dashboard.students_without_active_plan }}</span>
            <span class="text-xs text-slate-400">alunos pendentes</span>
          </div>
          <div class="mt-3 flex items-center justify-between text-[11px]">
            <span class="text-slate-500">Exigem prescrição</span>
            <RouterLink to="/students" class="text-amber-400 hover:text-amber-300 font-medium underline">
              Ver Alunos →
            </RouterLink>
          </div>
        </div>

        <!-- Card 3: Alunos em Risco de Evasão (14d+ sem treinar) -->
        <div 
          class="p-5 rounded-3xl bg-slate-900/70 border backdrop-blur-md relative overflow-hidden transition-all"
          :class="dashboard.students_at_risk > 0 ? 'border-rose-500/30' : 'border-slate-800/80'"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Alunos em Risco (14d+)</span>
            <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 text-sm">🚨</span>
          </div>
          <div class="mt-4 flex items-baseline gap-2">
            <span class="text-3xl font-black text-rose-400 font-mono">{{ dashboard.students_at_risk }}</span>
            <span class="text-xs text-slate-400">sem treinar há 14 dias</span>
          </div>
          <div class="mt-3 flex items-center justify-between text-[11px]">
            <span class="text-rose-400 font-semibold">Alerta de Churn</span>
            <RouterLink to="/students" class="text-rose-400 hover:text-rose-300 font-medium underline">
              Recuperar Alunos →
            </RouterLink>
          </div>
        </div>

        <!-- Card 4: Volume de Treinos (7d vs 30d) -->
        <div class="p-5 rounded-3xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md relative overflow-hidden">
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Volume de Treinos</span>
            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 text-sm">🏋️</span>
          </div>
          <div class="mt-4 flex items-baseline gap-2">
            <span class="text-3xl font-black text-indigo-400 font-mono">{{ dashboard.workouts_last_7_days }}</span>
            <span class="text-xs text-slate-400 font-medium">sessões nos últimos 7d</span>
          </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
            <span>Últimos 30 dias:</span>
            <span class="font-bold text-slate-200 font-mono">{{ dashboard.workouts_last_30_days }} sessões</span>
          </div>
        </div>
      </div>

      <!-- 2. Insights Operacionais & Ações Recomendadas -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>💡</span> Insights & Recomendações Automáticas
            </h3>
            <span class="text-xs text-slate-500">Inteligência Operacional</span>
          </div>

          <div class="space-y-3">
            <!-- Insight 1: Alunos sem treino -->
            <div 
              v-if="dashboard.students_without_active_plan > 0"
              class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 flex items-start gap-3"
            >
              <span class="text-lg text-amber-400 mt-0.5">⚠️</span>
              <div class="flex-1 text-xs">
                <h4 class="font-bold text-amber-300">
                  {{ dashboard.students_without_active_plan }} aluno(s) aguardando prescrição
                </h4>
                <p class="text-slate-400 mt-0.5">
                  Alunos sem ficha de treino ativa têm 3x mais probabilidade de evasão nos primeiros 30 dias.
                </p>
                <div class="mt-2.5 flex items-center gap-2">
                  <RouterLink 
                    to="/workouts/builder" 
                    class="px-3 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-semibold rounded-lg"
                  >
                    Montar Treino Agora
                  </RouterLink>
                  <RouterLink 
                    to="/workouts" 
                    class="px-3 py-1 text-slate-400 hover:text-white"
                  >
                    Aplicar Modelo da Biblioteca
                  </RouterLink>
                </div>
              </div>
            </div>

            <!-- Insight 2: Alunos em risco -->
            <div 
              v-if="dashboard.students_at_risk > 0"
              class="p-4 rounded-2xl bg-rose-500/5 border border-rose-500/20 flex items-start gap-3"
            >
              <span class="text-lg text-rose-400 mt-0.5">🚨</span>
              <div class="flex-1 text-xs">
                <h4 class="font-bold text-rose-300">
                  {{ dashboard.students_at_risk }} aluno(s) sem realizar treinos há 14+ dias
                </h4>
                <p class="text-slate-400 mt-0.5">
                  Faça um check-in de engajamento via WhatsApp ou readeque a carga de treino.
                </p>
                <div class="mt-2.5">
                  <RouterLink 
                    to="/students" 
                    class="px-3 py-1 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 font-semibold rounded-lg"
                  >
                    Abrir Lista de Alunos
                  </RouterLink>
                </div>
              </div>
            </div>

            <!-- Insight 3: Tudo sob controle -->
            <div 
              v-if="dashboard.students_without_active_plan === 0 && dashboard.students_at_risk === 0"
              class="p-4 rounded-2xl bg-emerald-500/5 border border-emerald-500/20 flex items-start gap-3"
            >
              <span class="text-lg text-emerald-400 mt-0.5">🎉</span>
              <div class="flex-1 text-xs">
                <h4 class="font-bold text-emerald-300">Excelente retenção e engajamento!</h4>
                <p class="text-slate-400 mt-0.5">
                  100% dos alunos estão com fichas ativas e com frequência consistente nos últimos 14 dias.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Coluna Lateral: Resumo Rápido de Treinos -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-4 flex flex-col justify-between">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>⚡</span> Ações Rápidas
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Atalhos para o dia a dia do treinador</p>
          </div>

          <div class="space-y-2 text-xs">
            <RouterLink 
              to="/students" 
              class="w-full p-3 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-emerald-500/30 text-slate-300 hover:text-white flex items-center justify-between transition-all"
            >
              <span class="flex items-center gap-2">
                <span>➕</span> Matricular Novo Aluno
              </span>
              <span>→</span>
            </RouterLink>

            <RouterLink 
              to="/workouts/builder" 
              class="w-full p-3 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-indigo-500/30 text-slate-300 hover:text-white flex items-center justify-between transition-all"
            >
              <span class="flex items-center gap-2">
                <span>🏋️</span> Criar Novo Template de Treino
              </span>
              <span>→</span>
            </RouterLink>

            <RouterLink 
              to="/exercises" 
              class="w-full p-3 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-purple-500/30 text-slate-300 hover:text-white flex items-center justify-between transition-all"
            >
              <span class="flex items-center gap-2">
                <span>📖</span> Biblioteca de Exercícios
              </span>
              <span>→</span>
            </RouterLink>
          </div>

          <div class="pt-4 border-t border-slate-800/80 text-[11px] text-slate-500">
            Métricas atualizadas dinamicamente a cada sessão finalizada.
          </div>
        </div>
      </div>
    </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '@/shared/layouts/AppLayout.vue'
import { analyticsService } from '../services/analyticsService'
import type { PortfolioDashboardData } from '../types'

const dashboard = ref<PortfolioDashboardData | null>(null)
const loading = ref(true)
const errorMessage = ref('')

async function loadDashboard() {
  loading.value = true
  errorMessage.value = ''
  try {
    dashboard.value = await analyticsService.getPortfolioDashboard()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao compilar métricas analíticas.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboard()
})
</script>
