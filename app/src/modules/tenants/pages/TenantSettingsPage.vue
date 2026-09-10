<template>
  <div class="space-y-6 max-w-5xl">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">
          Configurações da Organização
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Dados cadastrais da sua assessoria, plano SaaS e limites de consumo.
        </p>
      </div>

      <RouterLink
        to="/select-tenant"
        class="px-4 py-2 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl transition-colors flex items-center gap-2"
      >
        <span>🔄</span>
        <span>Alternar Organização</span>
      </RouterLink>
    </div>

    <!-- Tenant Card -->
    <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm shadow-xl">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-slate-950 font-black text-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
          {{ authStore.activeTenant?.name?.substring(0, 2).toUpperCase() || 'OG' }}
        </div>
        <div>
          <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-white">
              {{ authStore.activeTenant?.name }}
            </h2>
            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
              {{ authStore.activeTenant?.type }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Seu nível de acesso neste tenant: 
            <span class="text-emerald-400 font-semibold">{{ authStore.activeRole }}</span>
          </p>
        </div>
      </div>
    </div>

    <!-- SaaS Usage & Limits Card -->
    <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm shadow-xl space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-bold text-white flex items-center gap-2">
            <span>📊</span> Consumo do Plano SaaS
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">
            Plano atual: 
            <span class="text-white font-semibold">{{ usage?.plan_name || 'Carregando...' }}</span>
          </p>
        </div>

        <span 
          v-if="usage"
          class="px-3 py-1 text-xs font-semibold rounded-full border"
          :class="usage.subscription_status === 'ACTIVE' 
            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' 
            : 'bg-amber-500/10 text-amber-400 border-amber-500/30'"
        >
          {{ usage.subscription_status }}
        </span>
      </div>

      <div v-if="loading" class="py-10 text-center text-slate-400 text-xs">
        Carregando dados de uso...
      </div>

      <div v-else-if="usage" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Students Limit Bar -->
        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-3">
          <div class="flex justify-between items-center text-xs">
            <span class="text-slate-400 font-medium">Alunos Ativos</span>
            <span class="text-white font-bold">
              {{ usage.students.current }} / {{ usage.students.max !== null ? usage.students.max : '∞' }}
            </span>
          </div>

          <!-- Progress Bar -->
          <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden">
            <div 
              class="h-full rounded-full transition-all duration-500"
              :class="usage.students.reached ? 'bg-rose-500' : 'bg-emerald-400'"
              :style="{ width: getPercentage(usage.students.current, usage.students.max) + '%' }"
            ></div>
          </div>

          <p v-if="usage.students.reached" class="text-[11px] text-rose-400 font-medium">
            ⚠️ Limite de alunos atingido. Faça upgrade para matricular novos alunos.
          </p>
        </div>

        <!-- Trainers Limit Bar -->
        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-3">
          <div class="flex justify-between items-center text-xs">
            <span class="text-slate-400 font-medium">Treinadores na Equipe</span>
            <span class="text-white font-bold">
              {{ usage.trainers.current }} / {{ usage.trainers.max !== null ? usage.trainers.max : '∞' }}
            </span>
          </div>

          <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden">
            <div 
              class="h-full rounded-full transition-all duration-500"
              :class="usage.trainers.reached ? 'bg-rose-500' : 'bg-indigo-400'"
              :style="{ width: getPercentage(usage.trainers.current, usage.trainers.max) + '%' }"
            ></div>
          </div>

          <p v-if="usage.trainers.reached" class="text-[11px] text-rose-400 font-medium">
            ⚠️ Limite de treinadores atingido.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { tenantService } from '../services/tenantService'
import type { TenantUsage } from '../types'

const authStore = useAuthStore()

const usage = ref<TenantUsage | null>(null)
const loading = ref(true)

async function loadUsage() {
  loading.value = true
  try {
    usage.value = await tenantService.getUsage()
  } catch (err) {
    console.error('Erro ao carregar estatísticas de uso:', err)
  } finally {
    loading.value = false
  }
}

function getPercentage(current: number, max: number | null): number {
  if (max === null || max === 0) return Math.min(current * 10, 100)
  return Math.min(Math.round((current / max) * 100), 100)
}

onMounted(() => {
  loadUsage()
})
</script>
