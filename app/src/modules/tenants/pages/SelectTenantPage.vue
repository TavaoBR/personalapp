<template>
  <div class="min-h-screen bg-slate-950 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Glow Backgrounds -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center relative z-10 mb-8">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-slate-950 font-black text-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20 mb-4">
        ⚡
      </div>
      <h2 class="text-2xl font-extrabold text-white tracking-tight">
        Selecione uma Organização
      </h2>
      <p class="mt-2 text-sm text-slate-400">
        Você possui acesso a múltiplos perfis e assessorias. Escolha o espaço de trabalho desejado.
      </p>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-lg relative z-10">
      <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-2xl backdrop-blur-xl space-y-4">
        <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs text-center">
          {{ errorMessage }}
        </div>

        <div v-if="authStore.availableTenants.length === 0" class="text-center py-8 text-slate-400 text-sm">
          Nenhuma organização ativa encontrada para sua conta.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="tenant in authStore.availableTenants"
            :key="tenant.id"
            @click="handleSelect(tenant.id)"
            class="p-4 rounded-2xl border transition-all cursor-pointer flex items-center justify-between group"
            :class="tenant.id === authStore.activeTenant?.id
              ? 'bg-emerald-500/10 border-emerald-500/40 shadow-lg shadow-emerald-500/5'
              : 'bg-slate-950/60 border-slate-800/80 hover:border-slate-700 hover:bg-slate-950'"
          >
            <div class="flex items-center gap-3.5">
              <div 
                class="w-11 h-11 rounded-xl flex items-center justify-center font-bold text-base shadow-sm"
                :class="tenant.id === authStore.activeTenant?.id 
                  ? 'bg-emerald-400 text-slate-950' 
                  : 'bg-slate-800 text-slate-200 group-hover:bg-slate-700'"
              >
                {{ tenant.name.substring(0, 2).toUpperCase() }}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="font-bold text-white text-sm group-hover:text-emerald-400 transition-colors">
                    {{ tenant.name }}
                  </h3>
                  <span 
                    v-if="tenant.id === authStore.activeTenant?.id"
                    class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-500/20 text-emerald-300"
                  >
                    Ativo
                  </span>
                </div>
                <div class="flex items-center gap-2 mt-1 text-xs text-slate-400">
                  <span class="capitalize">{{ tenant.type.toLowerCase() }}</span>
                  <span>•</span>
                  <span class="text-slate-300 font-medium">{{ formatRole(tenant.role) }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center">
              <span class="text-slate-500 group-hover:text-emerald-400 text-base transition-colors">→</span>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
          <RouterLink to="/dashboard" class="text-slate-400 hover:text-white transition-colors">
            ← Voltar ao painel
          </RouterLink>
          <button @click="authStore.logout" class="text-rose-400 hover:text-rose-300 font-medium transition-colors">
            Encerrar Sessão
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const errorMessage = ref('')
const loadingId = ref('')

async function handleSelect(tenantId: string) {
  if (tenantId === authStore.activeTenant?.id) {
    router.push('/dashboard')
    return
  }

  loadingId.value = tenantId
  errorMessage.value = ''

  try {
    await authStore.switchTenant(tenantId)
    router.push('/dashboard')
  } catch (err: any) {
    errorMessage.value = err.message || 'Falha ao alternar organização.'
  } finally {
    loadingId.value = ''
  }
}

function formatRole(role: string): string {
  const map: Record<string, string> = {
    OWNER: 'Proprietário',
    ADMIN: 'Administrador',
    MANAGER: 'Gestor',
    TRAINER: 'Treinador',
    STUDENT: 'Aluno',
  }
  return map[role] || role
}
</script>
