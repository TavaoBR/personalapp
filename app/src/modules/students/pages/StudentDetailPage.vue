<template>
  <div class="space-y-6">
    <!-- Breadcrumb & Back button -->
    <div class="flex items-center gap-3">
      <RouterLink 
        to="/students" 
        class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors flex items-center gap-1.5 text-xs font-medium"
      >
        <span>←</span>
        <span>Voltar para Alunos</span>
      </RouterLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Carregando perfil do aluno...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-8 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
      <RouterLink to="/students" class="mt-4 inline-block px-4 py-2 text-xs bg-rose-500/20 text-rose-300 rounded-xl">
        Retornar à lista
      </RouterLink>
    </div>

    <!-- Main Content -->
    <div v-else-if="student" class="space-y-6">
      <!-- Top Profile Card -->
      <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md shadow-2xl relative overflow-hidden">
        <!-- Glow effect background -->
        <div class="absolute -right-10 -top-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
          <div class="flex items-center gap-5">
            <!-- Big Avatar -->
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-500 text-white font-bold text-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20 shrink-0">
              {{ getInitials(student.user.name) }}
            </div>

            <div>
              <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">
                  {{ student.user.name }}
                </h1>
                <span 
                  class="px-2.5 py-0.5 text-xs font-semibold rounded-full border"
                  :class="student.status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-500/10 text-slate-400 border-slate-500/30'"
                >
                  {{ student.status === 'ACTIVE' ? 'Ativo' : 'Inativo' }}
                </span>
              </div>
              
              <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-slate-400">
                <span class="flex items-center gap-1.5">
                  <span>✉️</span> {{ student.user.email }}
                </span>
                <span v-if="student.user.phone" class="flex items-center gap-1.5">
                  <span>📱</span> {{ student.user.phone }}
                </span>
                <span v-if="student.birth_date" class="flex items-center gap-1.5">
                  <span>🎂</span> {{ formatBirthDate(student.birth_date) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Trainer Box & Actions -->
          <div class="flex items-center gap-4">
            <div class="px-4 py-3 rounded-2xl bg-slate-950/60 border border-slate-800">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 block">
                Treinador Responsável
              </span>
              <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full" :class="student.trainer ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                <span class="text-sm font-semibold text-white">
                  {{ student.trainer?.name || 'Não Atribuído' }}
                </span>
                <button
                  v-if="canManage"
                  @click="isAssignModalOpen = true"
                  class="ml-2 text-xs text-emerald-400 hover:text-emerald-300 underline font-medium"
                >
                  Alterar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs (10 Domain Tabs) -->
      <div class="border-b border-slate-800 flex overflow-x-auto no-scrollbar gap-2 text-sm font-medium">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 py-3 whitespace-nowrap transition-all border-b-2 flex items-center gap-2"
          :class="activeTab === tab.id 
            ? 'border-emerald-400 text-emerald-400 font-semibold bg-emerald-500/5' 
            : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
        >
          <span>{{ tab.icon }}</span>
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- Tab Contents -->

      <!-- 1. Visão Geral -->
      <div v-if="activeTab === 'visao-geral'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Detalhes do Aluno -->
        <div class="lg:col-span-2 space-y-6">
          <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
              <span>📋</span> Informações Gerais
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div>
                <span class="text-slate-500 block mb-1">Gênero</span>
                <span class="text-slate-200 font-medium text-sm">{{ formatGender(student.gender) }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-1">Data de Início</span>
                <span class="text-slate-200 font-medium text-sm">{{ formatDate(student.created_at) }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-1">Contato de Emergência</span>
                <span class="text-slate-200 font-medium text-sm">{{ student.emergency_contact_name || 'Não informado' }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-1">Telefone de Emergência</span>
                <span class="text-slate-200 font-medium text-sm">{{ student.emergency_contact_phone || 'Não informado' }}</span>
              </div>
            </div>
          </div>

          <!-- Notas e Anotações -->
          <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>📝</span> Anotações & Observações do Aluno
              </h3>
              <button
                v-if="!isEditingNotes"
                @click="startEditNotes"
                class="text-xs text-emerald-400 hover:text-emerald-300 font-medium"
              >
                Editar
              </button>
            </div>
            <div v-if="isEditingNotes" class="space-y-3">
              <textarea
                v-model="notesInput"
                rows="4"
                class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
              ></textarea>
              <div class="flex justify-end gap-2">
                <button
                  @click="isEditingNotes = false"
                  class="px-3 py-1.5 text-xs text-slate-400 hover:text-white rounded-lg"
                >
                  Cancelar
                </button>
                <button
                  @click="saveNotes"
                  :disabled="savingNotes"
                  class="px-4 py-1.5 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-lg"
                >
                  {{ savingNotes ? 'Salvando...' : 'Salvar' }}
                </button>
              </div>
            </div>
            <p v-else class="text-sm text-slate-300 whitespace-pre-wrap">
              {{ student.notes || 'Nenhuma anotação registrada ainda.' }}
            </p>
          </div>
        </div>

        <!-- Sidebar Rápida -->
        <div class="space-y-6">
          <div class="p-6 rounded-2xl bg-gradient-to-b from-slate-900/80 to-slate-950/80 border border-slate-800">
            <h3 class="text-sm font-bold text-white mb-4">Ações Rápidas</h3>
            <div class="space-y-2.5">
              <button 
                @click="activeTab = 'treinos'" 
                class="w-full py-2.5 px-4 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all flex items-center justify-between"
              >
                <span>🏋️ Montar / Ver Treino</span>
                <span>→</span>
              </button>
              <button 
                @click="activeTab = 'avaliacoes'" 
                class="w-full py-2.5 px-4 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all flex items-center justify-between"
              >
                <span>📊 Nova Avaliação Física</span>
                <span>→</span>
              </button>
              <button 
                @click="activeTab = 'anamnese'" 
                class="w-full py-2.5 px-4 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all flex items-center justify-between"
              >
                <span>📋 Visualizar Anamnese</span>
                <span>→</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Treinos -->
      <div v-else-if="activeTab === 'treinos'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>🏋️</span> Fichas & Planos de Treino
            </h3>
            <p class="text-xs text-slate-400">Fichas ativas e histórico de periodização prescrito para este aluno.</p>
          </div>

          <RouterLink
            to="/workouts/builder"
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-all"
          >
            + Montar Novo Treino
          </RouterLink>
        </div>

        <div v-if="loadingPlans" class="py-12 text-center text-xs text-slate-400">
          Carregando fichas de treino...
        </div>

        <div v-else-if="studentPlans.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
          <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-3">
            🏋️
          </div>
          <h4 class="text-base font-semibold text-white">Nenhuma ficha prescrita</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Este aluno ainda não possui uma ficha de treino ativa.
          </p>
          <RouterLink 
            to="/workouts" 
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl inline-block"
          >
            Aplicar um Template Existente
          </RouterLink>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div 
            v-for="p in studentPlans" 
            :key="p.id"
            class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between"
          >
            <div>
              <div class="flex items-center justify-between mb-2">
                <span 
                  class="px-2 py-0.5 text-[10px] font-bold rounded-md border"
                  :class="p.status === 'ACTIVE' 
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' 
                    : 'bg-slate-500/10 text-slate-400 border-slate-500/30'"
                >
                  {{ p.status === 'ACTIVE' ? 'Ficha Ativa' : p.status }}
                </span>
                <span class="text-[10px] text-slate-500">{{ formatDate(p.created_at) }}</span>
              </div>

              <h4 class="font-bold text-white text-base leading-snug">{{ p.name }}</h4>
              <p v-if="p.template_name" class="text-xs text-slate-400 mt-1">
                Origem: <span class="text-slate-300">{{ p.template_name }}</span>
              </p>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-800 flex items-center justify-between">
              <span class="text-xs text-slate-400">
                {{ p.current_version?.days?.length || 0 }} dias de treino
              </span>
              <RouterLink 
                :to="`/workouts/plans/${p.id}`"
                class="text-xs font-semibold text-emerald-400 hover:text-emerald-300"
              >
                Abrir Ficha →
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Evolução -->
      <div v-else-if="activeTab === 'evolucao'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-xl mb-3">
          📈
        </div>
        <h3 class="text-lg font-semibold text-white">Evolução & Gráficos</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Acompanhamento comparativo de cargas máximas (1RM), volume semanal e medidas corporais.
        </p>
      </div>

      <!-- 4. Avaliações -->
      <div v-else-if="activeTab === 'avaliacoes'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl mb-3">
          📊
        </div>
        <h3 class="text-lg font-semibold text-white">Avaliações Físicas & Métricas</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1 mb-4">
          Protocolos de dobras cutâneas (Pollock), bioimpedância e fotos posturais comparativas.
        </p>
        <button class="px-5 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl">
          + Iniciar Avaliação
        </button>
      </div>

      <!-- 5. Anamnese -->
      <div v-else-if="activeTab === 'anamnese'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl mb-3">
          📋
        </div>
        <h3 class="text-lg font-semibold text-white">Questionário de Anamnese</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Histórico médico, cirurgias, medicamentos contínuos e restrições articulares.
        </p>
      </div>

      <!-- 6. Metas -->
      <div v-else-if="activeTab === 'metas'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-xl mb-3">
          🎯
        </div>
        <h3 class="text-lg font-semibold text-white">Metas & Desafios</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Metas de composição corporal, adesão semanal e marcos temporais.
        </p>
      </div>

      <!-- 7. Acompanhamento -->
      <div v-else-if="activeTab === 'acompanhamento'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl mb-3">
          📅
        </div>
        <h3 class="text-lg font-semibold text-white">Acompanhamento & Check-ins</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Feedback semanal do aluno, percepção de esforço, dores musculares e agenda de revisões.
        </p>
      </div>

      <!-- 8. Financeiro -->
      <div v-else-if="activeTab === 'financeiro'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-3">
          💰
        </div>
        <h3 class="text-lg font-semibold text-white">Financeiro & Assinatura do Aluno</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Planos contratados, faturas emitidas e controle de mensalidades da consultoria.
        </p>
      </div>

      <!-- 9. Timeline -->
      <div v-else-if="activeTab === 'timeline'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl mb-3">
          ⏳
        </div>
        <h3 class="text-lg font-semibold text-white">Timeline & Linha do Tempo</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Registro cronológico auditável de todos os eventos de treino, metas e prescrições.
        </p>
      </div>

      <!-- 10. Arquivos -->
      <div v-else-if="activeTab === 'arquivos'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-slate-500/10 text-slate-400 flex items-center justify-center text-xl mb-3">
          📁
        </div>
        <h3 class="text-lg font-semibold text-white">Arquivos & Fotos</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Armazenamento seguro de exames médicos, fotos posturais e documentos do aluno.
        </p>
      </div>

      <!-- Modal de Atribuição de Treinador -->
      <AssignTrainerModal
        :is-open="isAssignModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        :current-trainer-id="student.trainer?.id"
        @close="isAssignModalOpen = false"
        @assigned="loadStudent"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { studentService } from '../services/studentService'
import type { StudentDetail } from '../types'
import { workoutPlanService } from '@/modules/workouts/services/workoutPlanService'
import type { StudentWorkoutPlanSummary } from '@/modules/workouts/types'
import { useAuthStore } from '@/stores/auth'
import AssignTrainerModal from '../components/AssignTrainerModal.vue'

const route = useRoute()
const authStore = useAuthStore()

const studentId = computed(() => route.params.id as string)
const student = ref<StudentDetail | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const activeTab = ref('visao-geral')

const studentPlans = ref<StudentWorkoutPlanSummary[]>([])
const loadingPlans = ref(false)

const isAssignModalOpen = ref(false)
const isEditingNotes = ref(false)
const notesInput = ref('')
const savingNotes = ref(false)

const canManage = computed(() => {
  const role = authStore.activeRole
  return role === 'OWNER' || role === 'ADMIN' || role === 'MANAGER'
})

const tabs = [
  { id: 'visao-geral', label: 'Visão Geral', icon: '👤' },
  { id: 'treinos', label: 'Treinos', icon: '🏋️' },
  { id: 'evolucao', label: 'Evolução', icon: '📈' },
  { id: 'avaliacoes', label: 'Avaliações', icon: '📊' },
  { id: 'anamnese', label: 'Anamnese', icon: '📋' },
  { id: 'metas', label: 'Metas', icon: '🎯' },
  { id: 'acompanhamento', label: 'Acompanhamento', icon: '📅' },
  { id: 'financeiro', label: 'Financeiro', icon: '💰' },
  { id: 'timeline', label: 'Timeline', icon: '⏳' },
  { id: 'arquivos', label: 'Arquivos', icon: '📁' },
]

async function loadStudent() {
  loading.value = true
  errorMessage.value = ''
  try {
    student.value = await studentService.getById(studentId.value)
    notesInput.value = student.value.notes || ''
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar detalhes do aluno.'
  } finally {
    loading.value = false
  }
}

async function loadPlans() {
  loadingPlans.value = true
  try {
    studentPlans.value = await workoutPlanService.listByStudent(studentId.value)
  } catch (err) {
    console.error('Erro ao carregar planos de treino:', err)
  } finally {
    loadingPlans.value = false
  }
}

watch(activeTab, (newTab) => {
  if (newTab === 'treinos') {
    loadPlans()
  }
})

function startEditNotes() {
  notesInput.value = student.value?.notes || ''
  isEditingNotes.value = true
}

async function saveNotes() {
  savingNotes.value = true
  try {
    await studentService.update(studentId.value, { notes: notesInput.value })
    if (student.value) {
      student.value.notes = notesInput.value
    }
    isEditingNotes.value = false
  } catch (err: any) {
    alert(err.message || 'Erro ao salvar notas.')
  } finally {
    savingNotes.value = false
  }
}

function getInitials(name: string): string {
  if (!name) return 'A'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

function formatGender(gender?: string | null): string {
  if (gender === 'M') return 'Masculino'
  if (gender === 'F') return 'Feminino'
  return gender || 'Não informado'
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

function formatBirthDate(dateStr: string): string {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  const age = Math.floor((Date.now() - date.getTime()) / (365.25 * 24 * 60 * 60 * 1000))
  return `${date.toLocaleDateString('pt-BR')} (${age} anos)`
}

onMounted(() => {
  loadStudent()
})
</script>
