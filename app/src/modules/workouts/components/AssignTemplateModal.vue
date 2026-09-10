<template>
  <div v-if="isOpen && template" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📋</span> Aplicar Template a Alunos
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Template: <span class="text-emerald-400 font-semibold">{{ template.name }}</span>
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <form @submit.prevent="submit" class="mt-5 space-y-4">
          <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
            {{ errorMessage }}
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Nome Personalizado da Ficha (Opcional)</label>
            <input 
              v-model="customPlanName" 
              type="text" 
              :placeholder="template.name"
              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500" 
            />
          </div>

          <!-- Seleção de Alunos -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-semibold text-slate-300">
                Selecione os Alunos ({{ selectedStudentIds.length }} selecionados)
              </label>
              <button 
                type="button" 
                @click="toggleSelectAll" 
                class="text-[11px] text-emerald-400 hover:underline"
              >
                {{ selectedStudentIds.length === students.length ? 'Desmarcar Todos' : 'Selecionar Todos' }}
              </button>
            </div>

            <div v-if="loadingStudents" class="py-6 text-center text-xs text-slate-400">
              Carregando lista de alunos...
            </div>

            <div v-else-if="students.length === 0" class="py-6 text-center text-xs text-slate-400">
              Nenhum aluno ativo encontrado para aplicar este treino.
            </div>

            <div v-else class="max-h-48 overflow-y-auto space-y-1.5 p-2 bg-slate-950/60 border border-slate-800 rounded-xl">
              <label 
                v-for="st in students" 
                :key="st.id" 
                class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-800/50 cursor-pointer text-xs"
              >
                <input 
                  type="checkbox" 
                  :value="st.id" 
                  v-model="selectedStudentIds" 
                  class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-900"
                />
                <span class="font-medium text-white">{{ st.name }}</span>
                <span class="text-slate-500 truncate text-[11px]">({{ st.email }})</span>
              </label>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button 
              type="button" 
              @click="close"
              class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-xl hover:bg-slate-800"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="submitting || selectedStudentIds.length === 0"
              class="px-5 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 disabled:opacity-50 flex items-center gap-2"
            >
              <span v-if="submitting" class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
              {{ submitting ? 'Aplicando...' : `Aplicar a ${selectedStudentIds.length} Aluno(s)` }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { WorkoutTemplateSummary } from '../types'
import { templateService } from '../services/templateService'
import { studentService } from '@/modules/students/services/studentService'
import type { StudentSummary } from '@/modules/students/types'

const props = defineProps<{
  isOpen: boolean
  template?: WorkoutTemplateSummary | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'assigned'): void
}>()

const students = ref<StudentSummary[]>([])
const loadingStudents = ref(false)
const selectedStudentIds = ref<string[]>([])
const customPlanName = ref('')
const submitting = ref(false)
const errorMessage = ref('')

async function fetchStudents() {
  loadingStudents.value = true
  try {
    const list = await studentService.list()
    students.value = list.filter(s => s.status === 'ACTIVE')
  } catch (err) {
    console.error('Erro ao carregar alunos:', err)
  } finally {
    loadingStudents.value = false
  }
}

watch(() => props.isOpen, (open) => {
  if (open) {
    selectedStudentIds.value = []
    customPlanName.value = props.template?.name || ''
    errorMessage.value = ''
    fetchStudents()
  }
})

function toggleSelectAll() {
  if (selectedStudentIds.value.length === students.value.length) {
    selectedStudentIds.value = []
  } else {
    selectedStudentIds.value = students.value.map(s => s.id)
  }
}

function close() {
  emit('close')
}

async function submit() {
  if (!props.template || selectedStudentIds.value.length === 0) return

  submitting.value = true
  errorMessage.value = ''

  try {
    await templateService.assignToStudents(
      props.template.id,
      selectedStudentIds.value,
      customPlanName.value || undefined
    )
    emit('assigned')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao aplicar template aos alunos.'
  } finally {
    submitting.value = false
  }
}
</script>
