<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
            <span>📋</span>
            <span>Questionários de Anamnese</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Formulários de triagem de saúde, histórico de lesões, restrições e cirurgias.
          </p>
        </div>

        <button
          @click="isCreateModalOpen = true"
          class="px-4 py-2.5 text-xs font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 rounded-xl shadow-lg shadow-amber-400/20 transition-all font-display self-start flex items-center gap-2"
        >
          <span>➕</span>
          <span>Criar Questionário</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
        <div class="w-10 h-10 border-3 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="mt-4 text-sm">Carregando questionários...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="templates.length === 0" class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-2xl">
          📋
        </div>
        <h3 class="text-lg font-bold text-white">Nenhum questionário cadastrado</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          Crie formulários de anamnese personalizados para coletar histórico de saúde e restrições dos seus alunos.
        </p>
        <button
          @click="isCreateModalOpen = true"
          class="px-4 py-2 text-xs font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 rounded-xl font-display inline-block"
        >
          Criar Primeiro Questionário
        </button>
      </div>

      <!-- Templates Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="t in templates"
          :key="t.id"
          class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800 hover:border-amber-500/30 transition-all flex flex-col justify-between group relative overflow-hidden"
        >
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-500/10 text-amber-400 border border-amber-500/20 font-mono">
                v{{ t.latest_version }}
              </span>
              <span class="text-[10px] text-slate-500">{{ formatDate(t.updated_at) }}</span>
            </div>

            <h3 class="text-base font-bold text-white group-hover:text-amber-300 transition-colors">
              {{ t.title }}
            </h3>

            <p v-if="t.description" class="text-xs text-slate-400 line-clamp-2">
              {{ t.description }}
            </p>
          </div>

          <div class="pt-4 mt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
            <span class="text-slate-500 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full" :class="t.status === 'ACTIVE' ? 'bg-emerald-400' : 'bg-slate-500'"></span>
              {{ t.status === 'ACTIVE' ? 'Ativo' : t.status }}
            </span>

            <RouterLink 
              to="/students" 
              class="text-amber-400 hover:text-amber-300 font-semibold"
            >
              Aplicar em Aluno →
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Modal de Criação de Questionário -->
      <CreateTemplateModal
        :is-open="isCreateModalOpen"
        @close="isCreateModalOpen = false"
        @created="loadTemplates"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '@/shared/layouts/AppLayout.vue'
import { anamnesisService } from '../services/anamnesisService'
import type { AnamnesisTemplateSummary } from '../types'
import CreateTemplateModal from '../components/CreateTemplateModal.vue'

const templates = ref<AnamnesisTemplateSummary[]>([])
const loading = ref(true)
const isCreateModalOpen = ref(false)

async function loadTemplates() {
  loading.value = true
  try {
    templates.value = await anamnesisService.listTemplates()
  } catch (err) {
    console.error('Erro ao carregar templates de anamnese:', err)
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

onMounted(() => {
  loadTemplates()
})
</script>
