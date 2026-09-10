<template>
  <div class="space-y-6 max-w-5xl">
    <!-- Top Header -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <RouterLink 
          to="/workouts" 
          class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors text-xs flex items-center gap-1.5"
        >
          <span>←</span>
          <span>Voltar para Templates</span>
        </RouterLink>
        <h1 class="text-2xl font-bold text-white tracking-tight">
          Construtor de Template de Treino
        </h1>
      </div>

      <button
        @click="saveTemplate"
        :disabled="saving"
        class="px-5 py-2.5 text-sm font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 disabled:opacity-50 transition-all flex items-center gap-2"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
        {{ saving ? 'Salvando...' : 'Salvar & Publicar Template' }}
      </button>
    </div>

    <!-- Error Banner -->
    <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
      {{ errorMessage }}
    </div>

    <!-- Form Section: Template General Info -->
    <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm space-y-4">
      <h2 class="text-base font-bold text-white flex items-center gap-2">
        <span>⚙️</span> Informações do Programa
      </h2>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-3">
          <label class="block text-xs font-medium text-slate-300 mb-1">Nome do Template *</label>
          <input 
            v-model="name" 
            type="text" 
            required 
            placeholder="Ex: Hipertrofia ABC - Foco em Superiores"
            class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500 font-semibold"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Objetivo Principal</label>
          <select 
            v-model="objective" 
            class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
          >
            <option value="Hipertrofia">Hipertrofia</option>
            <option value="Emagrecimento">Emagrecimento / Definição</option>
            <option value="Força">Ganho de Força</option>
            <option value="Resistência">Resistência / Condicionamento</option>
            <option value="Saúde & Postura">Saúde & Postura</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Nível de Dificuldade</label>
          <select 
            v-model="level" 
            class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
          >
            <option value="Iniciante">Iniciante</option>
            <option value="Intermediário">Intermediário</option>
            <option value="Avançado">Avançado</option>
            <option value="Atleta">Atleta / Competidor</option>
          </select>
        </div>

        <div class="sm:col-span-3">
          <label class="block text-xs font-medium text-slate-300 mb-1">Descrição / Metodologia do Treino</label>
          <textarea 
            v-model="description" 
            rows="2" 
            placeholder="Orientações sobre aquecimento, cadência, progressão de carga semanal..."
            class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Section: Workout Days & Exercises -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
          <span>📅</span> Divisão de Dias ({{ days.length }} dias configurados)
        </h2>
        <button
          type="button"
          @click="addDay"
          class="px-3.5 py-2 text-xs font-semibold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 rounded-xl transition-all flex items-center gap-1.5"
        >
          <span>+</span>
          <span>Adicionar Dia de Treino</span>
        </button>
      </div>

      <!-- Editors for each Day -->
      <div class="space-y-4">
        <WorkoutDayEditor
          v-for="(day, dIdx) in days"
          :key="dIdx"
          :day="day"
          :day-index="dIdx"
          @remove-day="removeDay(dIdx)"
          @open-exercise-picker="handleOpenExercisePicker(dIdx)"
        />
      </div>
    </div>

    <!-- Modal Picker de Exercícios da Biblioteca -->
    <div v-if="isPickerOpen" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="isPickerOpen = false"></div>

        <div class="relative inline-block w-full max-w-2xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
          <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>🏋️</span> Selecionar Exercício da Biblioteca
            </h3>
            <button @click="isPickerOpen = false" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">✕</button>
          </div>

          <!-- Busca e Filtro de Grupo Muscular -->
          <div class="mt-4 space-y-3">
            <input 
              v-model="pickerSearch" 
              type="text" 
              placeholder="Pesquisar exercício por nome..."
              class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
            />

            <!-- Lista de Exercícios -->
            <div class="max-h-72 overflow-y-auto divide-y divide-slate-800/60 border border-slate-800 rounded-xl bg-slate-950/60">
              <div 
                v-for="ex in availableExercises" 
                :key="ex.id"
                @click="selectExercise(ex)"
                class="p-3.5 hover:bg-slate-800/60 transition-colors cursor-pointer flex items-center justify-between group"
              >
                <div>
                  <h4 class="font-semibold text-white text-sm group-hover:text-emerald-400 transition-colors">
                    {{ ex.name }}
                  </h4>
                  <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-400">
                    <span class="text-emerald-400">{{ ex.muscle_group }}</span>
                    <span>•</span>
                    <span>{{ ex.equipment || 'Livre' }}</span>
                  </div>
                </div>

                <button class="px-3 py-1.5 text-xs font-semibold text-emerald-400 bg-emerald-500/10 rounded-lg group-hover:bg-emerald-500/20">
                  + Escolher
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { templateService } from '../services/templateService'
import { exerciseService } from '@/modules/exercises/services/exerciseService'
import type { TemplateDayItem } from '../types'
import type { Exercise } from '@/modules/exercises/types'
import WorkoutDayEditor from '../components/WorkoutDayEditor.vue'

const router = useRouter()

const name = ref('')
const objective = ref('Hipertrofia')
const level = ref('Intermediário')
const description = ref('')
const saving = ref(false)
const errorMessage = ref('')

const days = reactive<TemplateDayItem[]>([
  {
    name: 'Treino A - Peito e Tríceps',
    order_index: 0,
    exercises: [],
  },
  {
    name: 'Treino B - Costas e Bíceps',
    order_index: 1,
    exercises: [],
  },
  {
    name: 'Treino C - Pernas Completo',
    order_index: 2,
    exercises: [],
  },
])

// Exercícios da Biblioteca para seleção
const allExercises = ref<Exercise[]>([])
const isPickerOpen = ref(false)
const targetDayIndex = ref<number>(0)
const pickerSearch = ref('')

async function loadExercises() {
  try {
    allExercises.value = await exerciseService.search()
  } catch (err) {
    console.error('Erro ao carregar exercícios:', err)
  }
}

const availableExercises = computed(() => {
  if (!pickerSearch.value) return allExercises.value
  const q = pickerSearch.value.toLowerCase()
  return allExercises.value.filter(e => e.name.toLowerCase().includes(q) || e.muscle_group.toLowerCase().includes(q))
})

function addDay() {
  const letters = ['A', 'B', 'C', 'D', 'E', 'F']
  const nextLetter = letters[days.length] || `${days.length + 1}`
  days.push({
    name: `Treino ${nextLetter}`,
    order_index: days.length,
    exercises: [],
  })
}

function removeDay(index: number) {
  if (days.length <= 1) {
    alert('O template precisa de pelo menos 1 dia de treino.')
    return
  }
  days.splice(index, 1)
  days.forEach((d, idx) => { d.order_index = idx })
}

function handleOpenExercisePicker(dayIdx: number) {
  targetDayIndex.value = dayIdx
  pickerSearch.value = ''
  isPickerOpen.value = true
}

function selectExercise(exercise: Exercise) {
  const targetDay = days[targetDayIndex.value]
  if (targetDay) {
    targetDay.exercises.push({
      exercise_id: exercise.id,
      name: exercise.name,
      muscle_group: exercise.muscle_group,
      order_index: targetDay.exercises.length,
      sets: 3,
      reps: '10-12',
      weight: '',
      rest_seconds: 60,
      rpe: '',
      notes: '',
    })
  }
  isPickerOpen.value = false
}

async function saveTemplate() {
  if (!name.value.trim()) {
    errorMessage.value = 'O nome do template é obrigatório.'
    return
  }

  saving.value = true
  errorMessage.value = ''

  try {
    const payloadDays = days.map((d, dIdx) => ({
      name: d.name,
      order_index: dIdx,
      exercises: d.exercises.map((ex, exIdx) => ({
        exercise_id: ex.exercise_id,
        order_index: exIdx,
        sets: Number(ex.sets) || 3,
        reps: String(ex.reps || '10-12'),
        weight: ex.weight || undefined,
        rest_seconds: Number(ex.rest_seconds) || 60,
        rpe: ex.rpe || undefined,
        notes: ex.notes || undefined,
      })),
    }))

    await templateService.create({
      name: name.value.trim(),
      description: description.value.trim() || undefined,
      objective: objective.value,
      level: level.value,
      days: payloadDays,
    })

    router.push('/workouts')
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao salvar template de treino.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadExercises()
})
</script>
