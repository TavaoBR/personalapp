<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 shrink-0">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📅</span> Prescrever Plano de Acompanhamento
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Aluno: <span class="text-white font-semibold">{{ studentName }}</span></p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 flex-1 overflow-y-auto pr-1 space-y-4">
          <!-- Título do Plano -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Título do Plano *
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Ex: Protocolo de Adaptação 30 Dias, Ciclo Hipertrofia..."
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"
            />
          </div>

          <!-- Data de Início -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Data de Início *
            </label>
            <input
              v-model="form.start_date"
              type="date"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
            />
          </div>

          <!-- Lista de Tarefas / Checkpoints -->
          <div class="space-y-3 pt-2 border-t border-slate-800">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-300">
                Tarefas & Checkpoints ({{ form.tasks.length }})
              </span>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="loadDefaultTasks"
                  class="px-2 py-1 text-[11px] font-semibold text-blue-400 hover:text-blue-300 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-lg transition-all"
                >
                  ⚡ Sugerir Cronograma 30d
                </button>
                <button
                  type="button"
                  @click="addTask"
                  class="px-2 py-1 text-[11px] font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-all"
                >
                  + Tarefa
                </button>
              </div>
            </div>

            <div v-if="form.tasks.length === 0" class="py-6 text-center text-xs text-slate-500 border border-dashed border-slate-800 rounded-2xl">
              Nenhuma tarefa adicionada. Clique em "Sugerir Cronograma" ou "+ Tarefa".
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="(t, idx) in form.tasks"
                :key="idx"
                class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800 flex items-center gap-2"
              >
                <div class="flex-1">
                  <input
                    v-model="t.title"
                    type="text"
                    required
                    placeholder="Título da tarefa..."
                    class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
                  />
                </div>
                <div class="w-36">
                  <input
                    v-model="t.due_date"
                    type="date"
                    required
                    class="w-full px-2 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
                  />
                </div>
                <button
                  type="button"
                  @click="removeTask(idx)"
                  class="text-xs text-slate-500 hover:text-rose-400 px-1"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-3 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
            {{ errorMessage }}
          </div>
        </form>

        <!-- Footer Actions -->
        <div class="flex justify-end gap-3 pt-3 border-t border-slate-800 shrink-0">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl"
          >
            Cancelar
          </button>
          <button
            type="button"
            @click="handleSubmit"
            :disabled="saving"
            class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 disabled:opacity-50 rounded-xl transition-all font-display shadow-lg shadow-blue-600/20"
          >
            {{ saving ? 'Prescrevendo...' : 'Prescrever Plano' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { followUpService } from '../services/followUpService'

const props = defineProps<{
  isOpen: boolean
  studentId: string
  studentName: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'prescribed'): void
}>()

const saving = ref(false)
const errorMessage = ref('')

const form = reactive({
  title: 'Protocolo de Acompanhamento 30 Dias',
  start_date: new Date().toISOString().split('T')[0],
  tasks: [] as Array<{ title: string; due_date: string }>
})

function addTask() {
  form.tasks.push({
    title: '',
    due_date: form.start_date
  })
}

function removeTask(index: number) {
  form.tasks.splice(index, 1)
}

function loadDefaultTasks() {
  const start = new Date(form.start_date)
  const addDays = (d: number) => {
    const next = new Date(start)
    next.setDate(start.getDate() + d)
    return next.toISOString().split('T')[0]
  }

  form.tasks = [
    { title: 'Check-in Inicial & Alinhamento de Metas', due_date: addDays(2) },
    { title: 'Revisão de Cargas & Adaptação aos Treinos', due_date: addDays(7) },
    { title: 'Check-in Semanal de Disposição & Sono', due_date: addDays(14) },
    { title: 'Ajuste Fino na Periodização & Dieta', due_date: addDays(21) },
    { title: 'Reavaliação Antropométrica de Ciclo (30d)', due_date: addDays(30) },
  ]
}

function close() {
  emit('close')
}

async function handleSubmit() {
  if (form.tasks.length === 0) {
    errorMessage.value = 'Adicione ao menos uma tarefa ao plano.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    await followUpService.prescribePlan(props.studentId, {
      title: form.title,
      start_date: form.start_date,
      tasks: form.tasks
    })
    emit('prescribed')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao prescrever plano.'
  } finally {
    saving.value = false
  }
}
</script>
