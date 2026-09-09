<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import {
  Activity,
  Calendar,
  Dumbbell,
  Home,
  LogOut,
  Moon,
  Sun,
} from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();
const route = useRoute();

const navItems = [
  { name: 'Início', path: '/portal/home', icon: Home },
  { name: 'Treinar', path: '/portal/workout', icon: Dumbbell },
  { name: 'Histórico', path: '/portal/history', icon: Calendar },
  { name: 'Evolução', path: '/portal/progress', icon: Activity },
];

function handleLogout() {
  authStore.logout();
  router.push('/login');
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 pb-20">
    <!-- Header Aluno -->
    <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl flex items-center justify-between px-4 sticky top-0 z-30">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-bold shadow-sm">
          <Dumbbell class="w-4 h-4" />
        </div>
        <span class="font-display font-bold text-base text-slate-900 dark:text-white">
          {{ authStore.activeMembership?.tenantName || 'Meu Treino' }}
        </span>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
          @click="themeStore.toggleTheme"
        >
          <Sun v-if="themeStore.isDark" class="w-4 h-4 text-amber-400" />
          <Moon v-else class="w-4 h-4" />
        </button>

        <button
          type="button"
          class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40"
          title="Sair"
          @click="handleLogout"
        >
          <LogOut class="w-4 h-4" />
        </button>
      </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="flex-1 p-4 sm:p-6 max-w-lg w-full mx-auto">
      <slot />
    </main>

    <!-- Bottom Navigation Bar Mobile-First -->
    <nav class="fixed bottom-0 inset-x-0 h-16 border-t border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl z-40 flex items-center justify-around px-2 max-w-md mx-auto sm:rounded-t-2xl shadow-lg">
      <RouterLink
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        class="flex flex-col items-center justify-center flex-1 h-full py-1 text-xs font-medium transition-colors"
        :class="route.path === item.path
          ? 'text-brand-600 dark:text-brand-400 font-semibold'
          : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
      >
        <component
          :is="item.icon"
          class="w-5 h-5 mb-0.5 transition-transform"
          :class="{ 'scale-110': route.path === item.path }"
        />
        <span>{{ item.name }}</span>
      </RouterLink>
    </nav>
  </div>
</template>
