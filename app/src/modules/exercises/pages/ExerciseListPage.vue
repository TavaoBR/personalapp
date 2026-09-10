<template>
  <div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
          <span>Biblioteca de Exercícios</span>
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            {{ filteredExercises.length }} disponíveis
          </span>
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Catálogo de exercícios com vídeos demonstrativos e orientações técnicas para prescrição.
        </p>
      </div>

      <button
        @click="openCreateModal"
        class="px-4 py-2.5 text-sm font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2"
      >
        <span class="text-base leading-none">+</span>
        Novo Exercício
      </button>
    </div>

    <!-- Search & Filters -->
    <div class="space-y-4">
      <!-- Search Input & Origin Filter -->
      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-8 relative">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar por nome do exercício ou equipamento..."
            class="w-full pl-9 pr-4 py-2 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
          />
          <span class="absolute left-3 top-2.5 text-slate-500 text-sm">🔍</span>
        </div>

        <div class="sm:col-span-4">
          <select 
            v-model="originFilter" 
            class="w-full px-3 py-2 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
          >
            <option value="ALL">Todas as Origens</option>
            <option value="GLOBAL">Padrão da Plataforma</option>
            <option value="CUSTOM">Meus Exercícios Customizados</option>
          </select>
        </div>
      </div>

      <!-- Muscle Groups Pills Bar -->
      <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 text-xs">
        <button
          @click="selectedMuscleGroup = null"
          class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap border"
          :class="selectedMuscleGroup === null 
            ? 'bg-emerald-400 text-slate-950 border-emerald-400 font-semibold shadow-md shadow-emerald-500/10' 
            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-white hover:border-slate-700'"
        >
          Todos os Músculos
        </button>
        <button
          v-for="group in muscleGroups"
          :key="group.value"
          @click="selectedMuscleGroup = group.value"
          class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap border"
          :class="selectedMuscleGroup === group.value 
            ? 'bg-emerald-400 text-slate-950 border-emerald-400 font-semibold shadow-md shadow-emerald-500/10' 
            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-white hover:border-slate-700'"
        >
          {{ group.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Carregando catálogo de exercícios...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
      <button @click="loadExercises" class="mt-3 px-4 py-2 text-xs bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 rounded-lg">
        Tentar Novamente
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredExercises.length === 0" class="p-12 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-800/80 text-slate-400 flex items-center justify-center text-2xl mb-4">
        🏋️
      </div>
      <h3 class="text-lg font-semibold text-white">Nenhum exercício encontrado</h3>
      <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
        Nenhum exercício corresponde aos filtros ou busca selecionada.
      </p>
      <button 
        @click="openCreateModal" 
        class="mt-4 px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl"
      >
        Cadastrar Este Exercício
      </button>
    </div>

    <!-- Exercises Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div 
        v-for="exercise in filteredExercises" 
        :key="exercise.id"
        @click="openDetailModal(exercise)"
        class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 hover:bg-slate-900/90 transition-all cursor-pointer shadow-lg hover:shadow-xl flex flex-col justify-between group"
      >
        <div>
          <!-- Tags Top -->
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              {{ formatMuscleGroup(exercise.muscle_group) }}
            </span>
            <span 
              class="px-2 py-0.5 text-[10px] font-bold rounded-md border"
              :class="exercise.is_custom 
                ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' 
                : 'bg-blue-500/10 text-blue-400 border-blue-500/20'"
            >
              {{ exercise.is_custom ? 'Personalizado' : 'Global' }}
            </span>
          </div>

          <h3 class="font-bold text-white text-base group-hover:text-emerald-400 transition-colors leading-snug">
            {{ exercise.name }}
          </h3>

          <p v-if="exercise.equipment" class="text-xs text-slate-400 mt-1.5 flex items-center gap-1.5">
            <span>⚙️</span>
            <span>{{ exercise.equipment }}</span>
          </p>

          <p v-if="exercise.description" class="text-xs text-slate-400 line-clamp-2 mt-2 leading-relaxed">
            {{ exercise.description }}
          </p>
        </div>

        <!-- Footer Card -->
        <div class="pt-4 mt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
          <span v-if="exercise.video_url" class="text-emerald-400 font-medium flex items-center gap-1">
            ▶️ Vídeo disponível
          </span>
          <span v-else class="text-slate-500">Sem vídeo</span>

          <span class="group-hover:text-emerald-400 group-hover:translate-x-1 transition-all text-slate-400 font-semibold">
            Detalhes →
          </span>
        </div>
      </div>
    </div>

    <!-- Modais -->
    <CreateExerciseModal 
      :is-open="isCreateModalOpen" 
      :editing-exercise="selectedExerciseForEdit"
      @close="isCreateModalOpen = false" 
      @saved="loadExercises" 
    />

    <ExerciseDetailModal 
      :is-open="isDetailModalOpen" 
      :exercise="selectedExerciseForDetail" 
      @close="isDetailModalOpen = false" 
      @edit="handleEditFromDetail"
      @archived="loadExercises" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { exerciseService } from '../services/exerciseService'
import type { Exercise, MuscleGroup } from '../types'

const exercises = ref<Exercise[]>([])
const loading = ref(true)
const errorMessage = ref('')

const searchQuery = ref('')
const selectedMuscleGroup = ref<MuscleGroup | null>(null)
const originFilter = ref('ALL')

const isCreateModalOpen = ref(false)
const isDetailModalOpen = ref(false)
const selectedExerciseForDetail = ref<Exercise | null>(null)
const selectedExerciseForEdit = ref<Exercise | null>(null)

const muscleGroups: Array<{ value: MuscleGroup; label: string }> = [
  { value: 'CHEST', label: 'Peito' },
  { value: 'BACK', label: 'Costas' },
  { value: 'LEGS', label: 'Pernas' },
  { value: 'SHOULDERS', label: 'Ombros' },
  { value: 'BICEPS', label: 'Bíceps' },
  { value: 'TRICEPS', label: 'Tríceps' },
  { value: 'ABDOMINALS', label: 'Abdômen' },
  { value: 'CORE', label: 'Core' },
  { value: 'CARDIO', label: 'Cardio' },
  { value: 'FULL_BODY', label: 'Corpo Inteiro' },
]

async function loadExercises() {
  loading.value = true
  errorMessage.value = ''
  try {
    exercises.value = await exerciseService.search(
      searchQuery.value || undefined,
      selectedMuscleGroup.value || undefined
    )
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar catálogo de exercícios.'
  } finally {
    loading.value = false
  }
}

// Debounce da busca
let debounceTimer: any = null
watch([searchQuery, selectedMuscleGroup], () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadExercises()
  }, 300)
})

const filteredExercises = computed(() => {
  return exercises.value.filter((ex) => {
    if (originFilter.value === 'GLOBAL' && ex.is_custom) return false
    if (originFilter.value === 'CUSTOM' && !ex.is_custom) return false
    return true
  })
})

function openCreateModal() {
  selectedExerciseForEdit.value = null
  isCreateModalOpen.value = true
}

function openDetailModal(exercise: Exercise) {
  selectedExerciseForDetail.value = exercise
  isDetailModalOpen.value = true
}

function handleEditFromDetail(exercise: Exercise) {
  isDetailModalOpen.value = false
  selectedExerciseForEdit.value = exercise
  isCreateModalOpen.value = true
}

function formatMuscleGroup(mg: string): string {
  const map: Record<string, string> = {
    CHEST: 'Peito',
    BACK: 'Costas',
    LEGS: 'Pernas',
    SHOULDERS: 'Ombros',
    BICEPS: 'Bíceps',
    TRICEPS: 'Tríceps',
    ABDOMINALS: 'Abdômen',
    CORE: 'Core',
    CARDIO: 'Cardio',
    FULL_BODY: 'Corpo Inteiro',
  }
  return map[mg] || mg
}

onMounted(() => {
  loadExercises()
})
</script>
