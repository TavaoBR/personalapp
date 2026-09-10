<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 shrink-0">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📋</span> Preencher Anamnese
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Aluno: <span class="text-white font-semibold">{{ studentName }}</span></p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Content -->
        <div class="mt-4 flex-1 overflow-y-auto pr-1 space-y-4">
          <!-- 1. Seletor de Template -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Escolha o Questionário de Anamnese *
            </label>
            <select
              v-model="selectedTemplateId"
              @change="onTemplateChange"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-amber-500"
            >
              <option value="" disabled>Selecione um questionário...</option>
              <option 
                v-for="t in templates" 
                :key="t.id" 
                :value="t.id"
              >
                {{ t.title }} (v{{ t.latest_version }})
              </option>
            </select>
          </div>

          <!-- Loading Template Details -->
          <div v-if="loadingTemplate" class="py-12 text-center text-xs text-slate-400">
            <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
            Carregando perguntas do formulário...
          </div>

          <!-- Form de Respostas Dinâmico -->
          <div v-else-if="currentVersion" class="space-y-4 pt-2">
            <div 
              v-for="(q, idx) in currentVersion.questions" 
              :key="q.id || idx"
              class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-2.5"
            >
              <div class="flex items-start justify-between gap-2">
                <label class="text-xs font-semibold text-white leading-snug">
                  <span class="text-amber-400 font-mono mr-1">#{{ idx + 1 }}</span>
                  {{ q.text }}
                  <span v-if="q.required" class="text-rose-400 ml-0.5">*</span>
                </label>
              </div>

              <!-- Input Tipo BOOLEAN -->
              <div v-if="q.type === 'BOOLEAN'" class="flex items-center gap-3">
                <button
                  type="button"
                  @click="answers[q.id || ''] = true"
                  class="flex-1 py-2 rounded-xl text-xs font-semibold border transition-all"
                  :class="answers[q.id || ''] === true 
                    ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300 font-bold' 
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                >
                  Sim
                </button>
                <button
                  type="button"
                  @click="answers[q.id || ''] = false"
                  class="flex-1 py-2 rounded-xl text-xs font-semibold border transition-all"
                  :class="answers[q.id || ''] === false 
                    ? 'bg-rose-500/20 border-rose-500 text-rose-300 font-bold' 
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                >
                  Não
                </button>
              </div>

              <!-- Input Tipo NUMBER -->
              <div v-else-if="q.type === 'NUMBER'">
                <input
                  v-model.number="answers[q.id || '']"
                  type="number"
                  step="any"
                  :required="q.required"
                  placeholder="Informe o valor numérico..."
                  class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500 font-mono"
                />
              </div>

              <!-- Input Tipo TEXT (Padrão) -->
              <div v-else>
                <textarea
                  v-model="answers[q.id || '']"
                  rows="2"
                  :required="q.required"
                  placeholder="Escreva a resposta ou detalhes..."
                  class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500 resize-none"
                ></textarea>
              </div>
            </div>

            <!-- Notas Clínicas / Parecer do Personal -->
            <div class="pt-2">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Parecer Técnico & Notas Clínicas do Treinador
              </label>
              <textarea
                v-model="notes"
                rows="2"
                placeholder="Ex: Aluno liberado com cautela para exercícios axiais de coluna..."
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500 resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-3 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
            {{ errorMessage }}
          </div>
        </div>

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
            :disabled="submitting || !currentVersion"
            class="px-5 py-2 text-xs font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 disabled:opacity-50 rounded-xl transition-all font-display"
          >
            {{ submitting ? 'Registrando...' : 'Concluir & Registrar Anamnese' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { anamnesisService } from '../services/anamnesisService'
import type { 
  AnamnesisTemplateSummary, 
  AnamnesisTemplateDetail,
  AnamnesisTemplateVersion 
} from '../types'

const props = defineProps<{
  isOpen: boolean
  studentId: string
  studentName: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submitted'): void
}>()

const templates = ref<AnamnesisTemplateSummary[]>([])
const selectedTemplateId = ref('')
const loadingTemplate = ref(false)
const currentVersion = ref<AnamnesisTemplateVersion | null>(null)
const answers = reactive<Record<string, any>>({})
const notes = ref('')
const submitting = ref(false)
const errorMessage = ref('')

async function loadTemplatesList() {
  try {
    templates.value = await anamnesisService.listTemplates()
    if (templates.value.length > 0 && !selectedTemplateId.value) {
      selectedTemplateId.value = templates.value[0].id
      await onTemplateChange()
    }
  } catch (err) {
    console.error('Erro ao listar templates:', err)
  }
}

async function onTemplateChange() {
  if (!selectedTemplateId.value) return
  loadingTemplate.value = true
  errorMessage.value = ''
  try {
    const detail = await anamnesisService.getTemplate(selectedTemplateId.value)
    if (detail.versions && detail.versions.length > 0) {
      currentVersion.value = detail.versions[0]
      // Reseta respostas
      Object.keys(answers).forEach((k) => delete answers[k])
      currentVersion.value.questions.forEach((q) => {
        if (q.id) {
          answers[q.id] = q.type === 'BOOLEAN' ? false : ''
        }
      })
    } else {
      currentVersion.value = null
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar perguntas do questionário.'
  } finally {
    loadingTemplate.value = false
  }
}

function close() {
  emit('close')
}

async function handleSubmit() {
  if (!currentVersion.value) {
    errorMessage.value = 'Selecione um questionário válido com perguntas.'
    return
  }

  // Valida campos obrigatórios
  for (const q of currentVersion.value.questions) {
    if (q.required && q.id) {
      const val = answers[q.id]
      if (val === undefined || val === null || val === '') {
        errorMessage.value = `A pergunta "${q.text}" é obrigatória.`
        return
      }
    }
  }

  submitting.value = true
  errorMessage.value = ''
  try {
    const formattedAnswers = currentVersion.value.questions.map((q) => ({
      question_id: q.id,
      question_text: q.text,
      answer: answers[q.id || '']
    }))

    await anamnesisService.submitAnamnesis(props.studentId, {
      version_id: currentVersion.value.id,
      answers: formattedAnswers,
      notes: notes.value || undefined
    })

    emit('submitted')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao registrar anamnese.'
  } finally {
    submitting.value = false
  }
}

watch(() => props.isOpen, (open) => {
  if (open) {
    loadTemplatesList()
  }
})

onMounted(() => {
  if (props.isOpen) {
    loadTemplatesList()
  }
})
</script>
