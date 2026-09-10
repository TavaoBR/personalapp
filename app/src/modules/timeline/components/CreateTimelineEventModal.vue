<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>⏳</span> Registrar Evento na Linha do Tempo
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Aluno: <span class="text-white font-semibold">{{ studentName }}</span></p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <!-- Título -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Título do Evento *
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Ex: Alinhamento de rotina alimentar, Feedback presencial..."
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500"
            />
          </div>

          <!-- Tipo de Evento & Data -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Tipo de Evento *
              </label>
              <select
                v-model="form.event_type"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
              >
                <option value="NOTE_ADDED">📝 Nota / Anotação</option>
                <option value="FEEDBACK_GIVEN">💬 Feedback / Ajuste</option>
                <option value="MILESTONE_REACHED">🏆 Marco Alcançado</option>
                <option value="MEDICAL_CLEARANCE">🩺 Liberação Médica</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Data do Ocorrido *
              </label>
              <input
                v-model="form.occurred_at"
                type="date"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
              />
            </div>
          </div>

          <!-- Descrição Detalhada -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Detalhes & Observações
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Descreva o contexto, orientações repassadas ou observações clínicas relevantes..."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 resize-none"
            ></textarea>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-3 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
            {{ errorMessage }}
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 rounded-xl transition-all font-display shadow-lg shadow-indigo-600/20"
            >
              {{ saving ? 'Registrando...' : 'Registrar Evento' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { timelineService } from '../services/timelineService'

const props = defineProps<{
  isOpen: boolean
  studentId: string
  studentName: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const saving = ref(false)
const errorMessage = ref('')

const form = reactive({
  title: '',
  event_type: 'NOTE_ADDED',
  occurred_at: new Date().toISOString().split('T')[0],
  description: ''
})

function close() {
  emit('close')
}

async function handleSubmit() {
  saving.value = true
  errorMessage.value = ''
  try {
    await timelineService.createEvent(props.studentId, {
      title: form.title,
      event_type: form.event_type,
      occurred_at: form.occurred_at,
      description: form.description || undefined
    })
    emit('created')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao registrar evento na linha do tempo.'
  } finally {
    saving.value = false
  }
}
</script>
