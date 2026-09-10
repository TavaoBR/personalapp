<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
            <span>📅</span>
            <span>Acompanhamento & Check-ins Periódicos</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Controle de rotina, percepção de esforço, dores musculares e engajamento semanal dos alunos.
          </p>
        </div>

        <RouterLink
          to="/students"
          class="px-4 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg shadow-blue-600/20 transition-all font-display self-start flex items-center gap-2"
        >
          <span>➕</span>
          <span>Check-in em Aluno</span>
        </RouterLink>
      </div>

      <!-- Quick Info Banner -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800 flex items-start gap-3">
          <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-400 text-lg">⚡</span>
          <div>
            <h3 class="text-sm font-bold text-white">Disposição & Sono</h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Monitore a recuperação do sistema nervoso central e adapte a intensidade antes do overtraining.
            </p>
          </div>
        </div>

        <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800 flex items-start gap-3">
          <span class="p-2.5 rounded-2xl bg-rose-500/10 text-rose-400 text-lg">🔥</span>
          <div>
            <h3 class="text-sm font-bold text-white">Dor Muscular Tardia</h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Identifique dores articulares e desconfortos precocemente para substituição de exercícios.
            </p>
          </div>
        </div>

        <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800 flex items-start gap-3">
          <span class="p-2.5 rounded-2xl bg-emerald-500/10 text-emerald-400 text-lg">🥗</span>
          <div>
            <h3 class="text-sm font-bold text-white">Dieta & Hidratação</h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Adesão alimentar consistente é o fator determinante de resultados na consultoria.
            </p>
          </div>
        </div>
      </div>

      <!-- Action Card -->
      <div class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-2xl">
          📅
        </div>
        <h3 class="text-lg font-bold text-white">Central de Check-ins do Aluno</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          Acesse a aba "Acompanhamento" no perfil de qualquer aluno matriculado para registrar novos check-ins, responder com parecer técnico ou prescrever planos de acompanhamento com tarefas periódicas.
        </p>
        <RouterLink
          to="/students"
          class="px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl font-display inline-block shadow-lg shadow-blue-600/20"
        >
          Ver Lista de Alunos
        </RouterLink>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppLayout from '@/shared/layouts/AppLayout.vue'
</script>
