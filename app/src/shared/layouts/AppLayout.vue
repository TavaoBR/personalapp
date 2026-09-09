<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import {
  Activity,
  Award,
  BarChart3,
  CalendarCheck,
  ChevronDown,
  CreditCard,
  Dumbbell,
  FileText,
  Home,
  LogOut,
  Moon,
  Settings,
  ShieldAlert,
  Sun,
  Target,
  Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();
const route = useRoute();

const isUserMenuOpen = ref(false);
const isTenantMenuOpen = ref(false);

const navigationItems = [
  { name: 'Dashboard', path: '/dashboard', icon: Home },
  { name: 'Alunos', path: '/students', icon: Users },
  { name: 'Fichas & Treinos', path: '/workouts', icon: Dumbbell },
  { name: 'Exercícios', path: '/exercises', icon: Activity },
  { name: 'Avaliações', path: '/assessments', icon: BarChart3 },
  { name: 'Anamnese', path: '/anamnesis', icon: FileText },
  { name: 'Metas', path: '/goals', icon: Target },
  { name: 'Acompanhamento', path: '/follow-up', icon: CalendarCheck },
  { name: 'Financeiro', path: '/financial', icon: CreditCard },
  { name: 'Minha Assinatura', path: '/saas-billing', icon: Award },
  { name: 'Configurações', path: '/settings', icon: Settings },
];

const currentTenantName = computed(() => {
  return authStore.activeMembership?.tenantName || 'Meu Espaço';
});

async function handleSwitchTenant(tenantId: string) {
  isTenantMenuOpen.value = false;
  await authStore.switchTenant(tenantId);
  router.push('/dashboard');
}

function handleLogout() {
  authStore.logout();
  router.push('/login');
}
</script>

<template>
  <div class="min-h-screen flex bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100">
    <!-- Sidebar Desktop -->
    <aside class="hidden lg:flex flex-col w-64 border-r border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl shrink-0">
      <!-- Logo da Plataforma -->
      <div class="h-16 flex items-center px-6 gap-3 border-b border-slate-200 dark:border-slate-800">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-brand-400 flex items-center justify-center text-white font-bold shadow-glow">
          <Dumbbell class="w-5 h-5" />
        </div>
        <div class="flex flex-col">
          <span class="font-display font-bold text-lg leading-none tracking-tight text-slate-900 dark:text-white">
            Personal<span class="text-brand-500">App</span>
          </span>
          <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold mt-0.5">SaaS Pro</span>
        </div>
      </div>

      <!-- Links de Navegação -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <RouterLink
          v-for="item in navigationItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group"
          :class="route.path.startsWith(item.path)
            ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20'
            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white'"
        >
          <component :is="item.icon" class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" />
          <span>{{ item.name }}</span>
        </RouterLink>

        <!-- Link Superadmin (se for Platform Admin) -->
        <div v-if="authStore.isPlatformAdmin" class="pt-4 mt-4 border-t border-slate-200 dark:border-slate-800">
          <RouterLink
            to="/admin/metrics"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors"
          >
            <ShieldAlert class="w-4 h-4 shrink-0" />
            <span>Painel Admin SaaS</span>
          </RouterLink>
        </div>
      </nav>

      <!-- Rodapé Sidebar -->
      <div class="p-4 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-400 flex items-center justify-between">
        <span>© 2026 PersonalApp</span>
        <button
          type="button"
          class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          @click="themeStore.toggleTheme"
        >
          <Sun v-if="themeStore.isDark" class="w-4 h-4 text-amber-400" />
          <Moon v-else class="w-4 h-4 text-slate-600" />
        </button>
      </div>
    </aside>

    <!-- Área de Conteúdo Principal -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Header Superior -->
      <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl flex items-center justify-between px-4 sm:px-6 sticky top-0 z-30">
        <!-- Seletor de Tenant -->
        <div class="relative">
          <button
            type="button"
            class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100/80 dark:bg-slate-800/80 hover:bg-slate-200/80 dark:hover:bg-slate-700/80 transition-colors text-sm font-medium"
            @click="isTenantMenuOpen = !isTenantMenuOpen"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="font-semibold text-slate-900 dark:text-white max-w-[180px] truncate">
              {{ currentTenantName }}
            </span>
            <ChevronDown class="w-4 h-4 text-slate-400" />
          </button>

          <!-- Dropdown Tenants -->
          <div
            v-if="isTenantMenuOpen"
            class="absolute left-0 mt-2 w-64 rounded-2xl glass-card shadow-2xl py-2 z-50 animate-in fade-in zoom-in-95 duration-150"
          >
            <div class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Organizações Vinculadas
            </div>
            <button
              v-for="m in authStore.memberships"
              :key="m.tenantId"
              type="button"
              class="w-full text-left px-3.5 py-2.5 text-sm flex items-center justify-between hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors"
              :class="{ 'font-bold text-brand-600 dark:text-brand-400': m.tenantId === authStore.activeTenantId }"
              @click="handleSwitchTenant(m.tenantId)"
            >
              <div class="flex flex-col">
                <span>{{ m.tenantName }}</span>
                <span class="text-[10px] text-slate-400 uppercase">{{ m.role }} • {{ m.tenantType }}</span>
              </div>
              <span v-if="m.tenantId === authStore.activeTenantId" class="text-xs text-brand-500 font-bold">Ativo</span>
            </button>
          </div>
        </div>

        <!-- Ações do Usuário e Perfil -->
        <div class="flex items-center gap-3">
          <!-- Alternador Tema Mobile -->
          <button
            type="button"
            class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
            @click="themeStore.toggleTheme"
          >
            <Sun v-if="themeStore.isDark" class="w-5 h-5 text-amber-400" />
            <Moon v-else class="w-5 h-5" />
          </button>

          <!-- Menu Usuário -->
          <div class="relative">
            <button
              type="button"
              class="flex items-center gap-2.5 p-1 rounded-full hover:ring-2 hover:ring-brand-500/20 transition-all"
              @click="isUserMenuOpen = !isUserMenuOpen"
            >
              <div class="w-9 h-9 rounded-full bg-brand-100 dark:bg-brand-950/60 border border-brand-200 dark:border-brand-800 text-brand-700 dark:text-brand-300 font-bold text-sm flex items-center justify-center">
                {{ authStore.user?.name.charAt(0).toUpperCase() || 'U' }}
              </div>
            </button>

            <!-- Dropdown Usuário -->
            <div
              v-if="isUserMenuOpen"
              class="absolute right-0 mt-2 w-56 rounded-2xl glass-card shadow-2xl py-2 z-50 animate-in fade-in zoom-in-95 duration-150"
            >
              <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800">
                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">
                  {{ authStore.user?.name }}
                </p>
                <p class="text-xs text-slate-400 truncate">
                  {{ authStore.user?.email }}
                </p>
              </div>

              <button
                type="button"
                class="w-full text-left px-4 py-2.5 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center gap-2.5 transition-colors"
                @click="handleLogout"
              >
                <LogOut class="w-4 h-4" />
                <span>Sair da conta</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Conteúdo da Página -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
