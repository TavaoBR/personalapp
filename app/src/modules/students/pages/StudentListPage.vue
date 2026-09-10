<template>
  <div class="space-y-6">
    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
          <span>Alunos</span>
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            {{ filteredStudents.length }} cadastrados
          </span>
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Gerencie a base de alunos da sua consultoria, fichas ativas e atribuições de treinadores.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button
          v-if="canManageStudents"
          @click="isEnrollModalOpen = true"
          class="px-4 py-2.5 text-sm font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2"
        >
          <span class="text-base leading-none">+</span>
          Matricular Aluno
        </button>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm grid grid-cols-1 sm:grid-cols-12 gap-3">
      <!-- Search Input -->
      <div class="sm:col-span-6 relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Buscar por nome ou e-mail..."
          class="w-full pl-9 pr-4 py-2 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
        />
        <span class="absolute left-3 top-2.5 text-slate-500 text-sm">🔍</span>
      </div>

      <!-- Status Filter -->
      <div class="sm:col-span-3">
        <select 
          v-model="statusFilter" 
          class="w-full px-3 py-2 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
        >
          <option value="ALL">Todos os Status</option>
          <option value="ACTIVE">Ativos</option>
          <option value="INACTIVE">Inativos</option>
        </select>
      </div>

      <!-- Quick Reset -->
      <div class="sm:col-span-3 flex justify-end">
        <button 
          v-if="searchQuery || statusFilter !== 'ALL'"
          @click="resetFilters"
          class="px-3 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors flex items-center gap-1.5"
        >
          Limpar Filtros
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Carregando alunos...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
      <button @click="loadStudents" class="mt-3 px-4 py-2 text-xs bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 rounded-lg">
        Tentar Novamente
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredStudents.length === 0" class="p-12 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-800/80 text-slate-400 flex items-center justify-center text-2xl mb-4">
        👥
      </div>
      <h3 class="text-lg font-semibold text-white">Nenhum aluno encontrado</h3>
      <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
        {{ searchQuery ? 'Nenhum resultado corresponde à sua pesquisa.' : 'Sua lista de alunos está vazia no momento.' }}
      </p>
      <button 
        v-if="canManageStudents && !searchQuery"
        @click="isEnrollModalOpen = true" 
        class="mt-4 px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl"
      >
        Matricular Primeiro Aluno
      </button>
    </div>

    <!-- Students Table -->
    <div v-else class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 backdrop-blur-sm shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800/80 bg-slate-900/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4">Aluno</th>
              <th class="py-3.5 px-4">Contato</th>
              <th class="py-3.5 px-4">Treinador Responsável</th>
              <th class="py-3.5 px-4">Status</th>
              <th class="py-3.5 px-4 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/50 text-sm">
            <tr 
              v-for="student in filteredStudents" 
              :key="student.id"
              class="hover:bg-slate-800/30 transition-colors group"
            >
              <!-- Nome / Avatar -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold text-xs flex items-center justify-center shadow-md shadow-emerald-500/10 shrink-0">
                    {{ getInitials(student.name) }}
                  </div>
                  <div>
                    <RouterLink 
                      :to="`/students/${student.id}`" 
                      class="font-semibold text-white hover:text-emerald-400 transition-colors block"
                    >
                      {{ student.name }}
                    </RouterLink>
                    <span class="text-xs text-slate-400 block">{{ student.email }}</span>
                  </div>
                </div>
              </td>

              <!-- Telefone / Gênero -->
              <td class="py-3.5 px-4 text-slate-300">
                <div class="text-xs">
                  <span>{{ student.phone || 'Sem telefone' }}</span>
                  <span v-if="student.gender" class="block text-[11px] text-slate-500 uppercase">
                    {{ formatGender(student.gender) }}
                  </span>
                </div>
              </td>

              <!-- Treinador Atribuído -->
              <td class="py-3.5 px-4">
                <div v-if="student.trainer" class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                  <span class="text-xs text-slate-200 font-medium">{{ student.trainer.name }}</span>
                </div>
                <div v-else class="flex items-center gap-2 text-amber-400/80 text-xs">
                  <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                  <span>Não atribuído</span>
                </div>
              </td>

              <!-- Status Badge -->
              <td class="py-3.5 px-4">
                <span 
                  class="px-2.5 py-1 text-[11px] font-semibold rounded-full border"
                  :class="student.status === 'ACTIVE' 
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                    : 'bg-slate-500/10 text-slate-400 border-slate-500/20'"
                >
                  {{ student.status === 'ACTIVE' ? 'Ativo' : 'Inativo' }}
                </span>
              </td>

              <!-- Ações -->
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="canManageStudents"
                    @click="openAssignModal(student)"
                    title="Atribuir Treinador"
                    class="px-2.5 py-1.5 text-xs text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg transition-colors flex items-center gap-1.5"
                  >
                    <span>🤝</span>
                    <span class="hidden sm:inline">Vincular</span>
                  </button>

                  <RouterLink 
                    :to="`/students/${student.id}`"
                    class="px-3 py-1.5 text-xs font-semibold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 rounded-lg transition-colors"
                  >
                    Ver Perfil →
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modais -->
    <EnrollStudentModal 
      :is-open="isEnrollModalOpen" 
      @close="isEnrollModalOpen = false" 
      @enrolled="loadStudents" 
    />

    <AssignTrainerModal
      v-if="selectedStudentForAssign"
      :is-open="isAssignModalOpen"
      :student-id="selectedStudentForAssign.id"
      :student-name="selectedStudentForAssign.name"
      :current-trainer-id="selectedStudentForAssign.trainer?.id"
      @close="isAssignModalOpen = false"
      @assigned="loadStudents"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { studentService } from '../services/studentService'
import type { StudentSummary } from '../types'
import { useAuthStore } from '@/stores/auth'
import EnrollStudentModal from '../components/EnrollStudentModal.vue'
import AssignTrainerModal from '../components/AssignTrainerModal.vue'

const authStore = useAuthStore()

const students = ref<StudentSummary[]>([])
const loading = ref(true)
const errorMessage = ref('')

const searchQuery = ref('')
const statusFilter = ref('ALL')

const isEnrollModalOpen = ref(false)
const isAssignModalOpen = ref(false)
const selectedStudentForAssign = ref<StudentSummary | null>(null)

const canManageStudents = computed(() => {
  const role = authStore.activeRole
  return role === 'OWNER' || role === 'ADMIN' || role === 'MANAGER'
})

async function loadStudents() {
  loading.value = true
  errorMessage.value = ''
  try {
    students.value = await studentService.list()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar a lista de alunos.'
  } finally {
    loading.value = false
  }
}

const filteredStudents = computed(() => {
  return students.value.filter((s) => {
    const matchesSearch = 
      s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      s.email.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesStatus = 
      statusFilter.value === 'ALL' || s.status === statusFilter.value

    return matchesSearch && matchesStatus
  })
})

function resetFilters() {
  searchQuery.value = ''
  statusFilter.value = 'ALL'
}

function openAssignModal(student: StudentSummary) {
  selectedStudentForAssign.value = student
  isAssignModalOpen.value = true
}

function getInitials(name: string): string {
  if (!name) return 'A'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

function formatGender(gender?: string | null): string {
  if (gender === 'M') return 'Masc'
  if (gender === 'F') return 'Fem'
  return gender || ''
}

onMounted(() => {
  loadStudents()
})
</script>
