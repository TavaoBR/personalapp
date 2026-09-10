<template>
  <div class="space-y-6">
    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
          <span>Templates de Treino</span>
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            {{ templates.length }} modelos
          </span>
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Crie programas de treino padronizados, versionados e aplique a múltiplos alunos com 1 clique.
        </p>
      </div>

      <RouterLink
        to="/workouts/builder"
        class="px-4 py-2.5 text-sm font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2"
      >
        <span class="text-base leading-none">+</span>
        Novo Template
      </RouterLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Carregando templates de treino...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
      <button @click="loadTemplates" class="mt-3 px-4 py-2 text-xs bg-rose-500/20 text-rose-300 rounded-lg">
        Tentar Novamente
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="templates.length === 0" class="p-12 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-800/80 text-slate-400 flex items-center justify-center text-2xl mb-4">
        📋
      </div>
      <h3 class="text-lg font-semibold text-white">Nenhum template cadastrado</h3>
      <p class="text-sm text-slate-400 max-w-md mx-auto mt-1 mb-4">
        Crie seu primeiro modelo de treino (ex: ABC Hipertrofia, Push/Pull/Legs) para prescrever aos seus alunos.
      </p>
      <RouterLink 
        to="/workouts/builder" 
        class="px-5 py-2.5 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl inline-block"
      >
        Criar Primeiro Template
      </RouterLink>
    </div>

    <!-- Templates Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div 
        v-for="tpl in templates" 
        :key="tpl.id"
        class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all shadow-lg hover:shadow-xl flex flex-col justify-between group"
      >
        <div>
          <!-- Header Badges -->
          <div class="flex items-center justify-between gap-2 mb-3">
            <span 
              v-if="tpl.current_version?.objective" 
              class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
            >
              {{ tpl.current_version.objective }}
            </span>
            <span v-else class="text-[10px] text-slate-500 font-medium">Geral</span>

            <span class="text-[10px] text-slate-400 font-mono">
              v{{ tpl.current_version?.version_number || 1 }}
            </span>
          </div>

          <h3 class="font-bold text-white text-base group-hover:text-emerald-400 transition-colors leading-snug">
            {{ tpl.name }}
          </h3>

          <p v-if="tpl.description" class="text-xs text-slate-400 line-clamp-2 mt-2 leading-relaxed">
            {{ tpl.description }}
          </p>

          <!-- Dias e Divisão -->
          <div class="mt-4 pt-3 border-t border-slate-800/80">
            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block mb-2">
              Divisão de Treino ({{ tpl.current_version?.days?.length || 0 }} dias)
            </span>
            <div class="flex flex-wrap gap-1.5">
              <span 
                v-for="(day, dIdx) in tpl.current_version?.days || []" 
                :key="dIdx"
                class="px-2 py-1 text-[11px] font-medium rounded-lg bg-slate-950 border border-slate-800 text-slate-300"
              >
                {{ day.name }}
              </span>
            </div>
          </div>
        </div>

        <!-- Card Actions -->
        <div class="pt-5 mt-5 border-t border-slate-800/80 flex items-center justify-between gap-2">
          <button 
            @click="openAssignModal(tpl)"
            class="px-3.5 py-2 text-xs font-semibold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 rounded-xl transition-all flex items-center gap-1.5"
          >
            <span>👥</span>
            <span>Aplicar a Alunos</span>
          </button>

          <span class="text-[11px] text-slate-500">
            {{ formatDate(tpl.created_at) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Modal de Aplicação -->
    <AssignTemplateModal 
      :is-open="isAssignModalOpen" 
      :template="selectedTemplateForAssign"
      @close="isAssignModalOpen = false" 
      @assigned="handleAssignedSuccess" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { templateService } from '../services/templateService'
import type { WorkoutTemplateSummary } from '../types'
import AssignTemplateModal from '../components/AssignTemplateModal.vue'

const templates = ref<WorkoutTemplateSummary[]>([])
const loading = ref(true)
const errorMessage = ref('')

const isAssignModalOpen = ref(false)
const selectedTemplateForAssign = ref<WorkoutTemplateSummary | null>(null)

async function loadTemplates() {
  loading.value = true
  errorMessage.value = ''
  try {
    templates.value = await templateService.list()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar templates de treino.'
  } finally {
    loading.value = false
  }
}

function openAssignModal(tpl: WorkoutTemplateSummary) {
  selectedTemplateForAssign.value = tpl
  isAssignModalOpen.value = true
}

function handleAssignedSuccess() {
  alert('Treino aplicado com sucesso aos alunos selecionados!')
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

onMounted(() => {
  loadTemplates()
})
</script>
