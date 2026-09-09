<script setup lang="ts">
import { useThemeStore } from '@/stores/theme';
import { Dumbbell, Moon, Sun } from 'lucide-vue-next';

const themeStore = useThemeStore();
</script>

<template>
  <div class="min-h-screen flex flex-col justify-center items-center p-4 sm:p-6 bg-gradient-to-br from-slate-100 via-slate-50 to-brand-50/30 dark:from-slate-950 dark:via-slate-900 dark:to-brand-950/20 text-slate-800 dark:text-slate-100 transition-colors">
    <!-- Alternador Tema Topo -->
    <div class="absolute top-4 right-4">
      <button
        type="button"
        class="p-2.5 rounded-2xl glass-card text-slate-600 dark:text-slate-300 hover:text-brand-500 transition-colors shadow-sm"
        @click="themeStore.toggleTheme"
      >
        <Sun v-if="themeStore.isDark" class="w-5 h-5 text-amber-400" />
        <Moon v-else class="w-5 h-5" />
      </button>
    </div>

    <!-- Header Logo -->
    <div class="flex flex-col items-center mb-8 text-center">
      <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-glow mb-3">
        <Dumbbell class="w-8 h-8" />
      </div>
      <h1 class="text-3xl font-display font-extrabold tracking-tight text-slate-900 dark:text-white">
        Personal<span class="text-brand-600 dark:text-brand-400">App</span>
      </h1>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm">
        A plataforma de alta performance para Personal Trainers, Assessorias e Alunos
      </p>
    </div>

    <!-- Card de Autenticação -->
    <div class="w-full max-w-md rounded-3xl glass-card p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800/80">
      <slot />
    </div>

    <!-- Rodapé -->
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-8 text-center">
      Protegido com criptografia de ponta a ponta e isolamento seguro de dados.
    </p>
  </div>
</template>
