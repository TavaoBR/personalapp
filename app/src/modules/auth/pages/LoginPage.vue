<script setup lang="ts">
import Button from '@/shared/components/ui/Button.vue';
import AuthLayout from '@/shared/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { AlertCircle, ArrowRight, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const errorMessage = ref<string | null>(null);

async function handleLogin() {
  errorMessage.value = null;
  if (!email.value || !password.value) {
    errorMessage.value = 'Por favor, preencha o e-mail e a senha.';
    return;
  }

  try {
    await authStore.login(email.value, password.value);

    // Redirecionamento inteligente por papel
    if (authStore.isPlatformAdmin && !authStore.activeMembership) {
      router.push('/admin/metrics');
    } else if (authStore.isStudent) {
      router.push('/portal/home');
    } else {
      router.push('/dashboard');
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Falha na autenticação. Verifique seus dados.';
  }
}

function fillQuickCredentials(userEmail: string, userPass: string) {
  email.value = userEmail;
  password.value = userPass;
}
</script>

<template>
  <AuthLayout>
    <div class="mb-6 text-center">
      <h2 class="text-2xl font-bold font-display text-slate-900 dark:text-white">
        Acesse sua conta
      </h2>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
        Insira suas credenciais para continuar
      </p>
    </div>

    <!-- Mensagem de Erro -->
    <div
      v-if="errorMessage"
      class="mb-5 p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-sm flex items-start gap-2.5"
    >
      <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Formulário -->
    <form class="space-y-4" @submit.prevent="handleLogin">
      <!-- Campo Email -->
      <div>
        <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
          E-mail
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <Mail class="w-4 h-4" />
          </div>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="seu.email@exemplo.com"
            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all text-sm"
          />
        </div>
      </div>

      <!-- Campo Senha -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
            Senha
          </label>
        </div>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <Lock class="w-4 h-4" />
          </div>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="••••••••"
            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all text-sm"
          />
        </div>
      </div>

      <!-- Botão de Entrar -->
      <div class="pt-2">
        <Button
          type="submit"
          class="w-full justify-center shadow-glow"
          size="lg"
          :loading="authStore.isLoading"
        >
          <span>Entrar no Sistema</span>
          <ArrowRight class="w-4 h-4" />
        </Button>
      </div>
    </form>

    <!-- Preenchimento Rápido em Ambiente de Desenvolvimento / Demonstração -->
    <div class="mt-6 pt-5 border-t border-slate-200/80 dark:border-slate-800/80">
      <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider text-center mb-2.5">
        Acesso rápido de teste
      </p>
      <div class="grid grid-cols-3 gap-2 text-xs">
        <button
          type="button"
          class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800/70 text-slate-700 dark:text-slate-300 transition-colors font-medium truncate"
          @click="fillQuickCredentials('owner@alpha.com', 'pass123')"
        >
          Personal
        </button>
        <button
          type="button"
          class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800/70 text-slate-700 dark:text-slate-300 transition-colors font-medium truncate"
          @click="fillQuickCredentials('student@gym.com', 'pass')"
        >
          Aluno
        </button>
        <button
          type="button"
          class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800/70 text-slate-700 dark:text-slate-300 transition-colors font-medium truncate"
          @click="fillQuickCredentials('admin@saasplatform.com', 'admin12345')"
        >
          Admin
        </button>
      </div>
    </div>
  </AuthLayout>
</template>
