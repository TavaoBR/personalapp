<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
            <span>🎯</span>
            <span>Metas & Desafios da Carteira</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Acompanhe a evolução de composição corporal e objetivos de força dos seus alunos.
          </p>
        </div>

        <RouterLink
          to="/students"
          class="px-4 py-2.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 rounded-xl shadow-lg shadow-rose-600/20 transition-all font-display self-start flex items-center gap-2"
        >
          <span>➕</span>
          <span>Definir Meta em Aluno</span>
        </RouterLink>
      </div>

      <!-- Info Banner -->
      <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-xl shrink-0">
            🎯
          </div>
          <div>
            <h3 class="text-sm font-bold text-white leading-tight">Metas Ativas & Engajamento</h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Alunos com metas bem definidas apresentam taxa de renovação de plano 42% superior.
            </p>
          </div>
        </div>

        <RouterLink 
          to="/students" 
          class="px-3.5 py-1.5 text-xs font-semibold text-rose-300 hover:text-white bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 rounded-xl whitespace-nowrap transition-all"
        >
          Ver Alunos →
        </RouterLink>
      </div>

      <!-- Empty / Guide State -->
      <div class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-2xl">
          🎯
        </div>
        <h3 class="text-lg font-bold text-white">Central de Metas do Aluno</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          As metas são vinculadas individualmente a cada aluno, permitindo acompanhamento temporal de peso, percentual de gordura e cargas de treino.
        </p>
        <RouterLink
          to="/students"
          class="px-5 py-2.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 rounded-xl font-display inline-block shadow-lg shadow-rose-600/20"
        >
          Acessar Perfil de um Aluno para Criar Meta
        </RouterLink>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppLayout from '@/shared/layouts/AppLayout.vue'
</script>
