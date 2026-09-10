<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-2xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 shrink-0">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📋</span> Novo Questionário de Anamnese
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Defina perguntas e campos de histórico de saúde</p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Scrollable Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-5 flex-1 overflow-y-auto pr-1">
          <!-- Título & Descrição -->
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Título do Questionário *
              </label>
              <input
                v-model="form.title"
                type="text"
                required
                placeholder="Ex: Anamnese Geral de Entrada, Histórico Cardíaco..."
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-amber-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Descrição / Instruções ao Aluno
              </label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Ex: Responda atentamente a todas as perguntas sobre histórico médico e limitações..."
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-amber-500 resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Perguntas -->
          <div class="space-y-3 pt-3 border-t border-slate-800">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-300">
                Perguntas do Questionário ({{ form.questions.length }})
              </span>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="loadDefaultQuestions"
                  class="px-2.5 py-1 text-[11px] font-semibold text-amber-400 hover:text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 rounded-lg transition-all"
                >
                  ⚡ Sugerir Padrão
                </button>
                <button
                  type="button"
                  @click="addQuestion"
                  class="px-2.5 py-1 text-[11px] font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-all"
                >
                  + Pergunta
                </button>
              </div>
            </div>

            <div v-if="form.questions.length === 0" class="py-6 text-center text-xs text-slate-500 border border-dashed border-slate-800 rounded-2xl">
              Nenhuma pergunta adicionada ainda. Clique em "Sugerir Padrão" ou "+ Pergunta".
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(q, index) in form.questions"
                :key="index"
                class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-3 relative group"
              >
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-bold text-amber-400 font-mono">
                    #{{ index + 1 }}
                  </span>

                  <div class="flex items-center gap-3">
                    <label class="flex items-center gap-1.5 text-xs text-slate-400 cursor-pointer">
                      <input
                        type="checkbox"
                        v-model="q.required"
                        class="rounded border-slate-700 text-amber-500 focus:ring-0 bg-slate-900"
                      />
                      <span>Obrigatória</span>
                    </label>

                    <button
                      type="button"
                      @click="removeQuestion(index)"
                      class="text-xs text-slate-500 hover:text-rose-400"
                      title="Remover pergunta"
                    >
                      ✕
                    </button>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                  <div class="sm:col-span-2">
                    <input
                      v-model="q.text"
                      type="text"
                      required
                      placeholder="Texto da pergunta (ex: Possui histórico de cirurgias?)"
                      class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500"
                    />
                  </div>

                  <div>
                    <select
                      v-model="q.type"
                      class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500"
                    >
                      <option value="BOOLEAN">Sim / Não</option>
                      <option value="TEXT">Texto Livre</option>
                      <option value="NUMBER">Numérico</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-3 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
            {{ errorMessage }}
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-3 border-t border-slate-800 shrink-0">
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
              class="px-5 py-2 text-xs font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 disabled:opacity-50 rounded-xl transition-all font-display"
            >
              {{ saving ? 'Salvando...' : 'Salvar Questionário' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { anamnesisService } from '../services/anamnesisService'
import type { AnamnesisQuestionType } from '../types'

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const saving = ref(false)
const errorMessage = ref('')

interface FormQuestion {
  text: string
  type: AnamnesisQuestionType
  required: boolean
}

const form = reactive({
  title: '',
  description: '',
  questions: [] as FormQuestion[]
})

function addQuestion() {
  form.questions.push({
    text: '',
    type: 'BOOLEAN',
    required: true
  })
}

function removeQuestion(index: number) {
  form.questions.splice(index, 1)
}

function loadDefaultQuestions() {
  form.title = 'Anamnese Geral de Saúde & Aptidão Física'
  form.description = 'Avaliação de histórico clínico, limitações articulares e uso de medicamentos.'
  form.questions = [
    { text: 'Possui diagnóstico médico de hipertensão ou problemas cardíacos?', type: 'BOOLEAN', required: true },
    { text: 'Já realizou cirurgias prévias? Quais?', type: 'TEXT', required: false },
    { text: 'Faz uso contínuo de algum medicamento? Quais?', type: 'TEXT', required: false },
    { text: 'Sente dores frequentes na coluna, joelhos ou ombros?', type: 'BOOLEAN', required: true },
    { text: 'Possui histórico de diabetes ou distúrbios na tireoide?', type: 'BOOLEAN', required: true },
    { text: 'Quantas horas você costuma dormir por noite?', type: 'NUMBER', required: false },
    { text: 'Observações adicionais ou recomendações do seu médico:', type: 'TEXT', required: false },
  ]
}

function close() {
  emit('close')
}

async function handleSubmit() {
  if (form.questions.length === 0) {
    errorMessage.value = 'Adicione ao menos uma pergunta ao questionário.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    await anamnesisService.createTemplate({
      title: form.title,
      description: form.description || undefined,
      questions: form.questions.map((q, idx) => ({
        text: q.text,
        type: q.type,
        required: q.required,
        order_index: idx
      }))
    })
    emit('created')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao criar questionário de anamnese.'
  } finally {
    saving.value = false
  }
}
</script>
