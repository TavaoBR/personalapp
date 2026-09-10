<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-2xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
              <span class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center text-sm">
                📊
              </span>
              Nova Avaliação Física
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Aluno: <span class="text-slate-200 font-semibold">{{ studentName }}</span>
            </p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <form @submit.prevent="submit" class="mt-5 space-y-5">
          <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
            {{ errorMessage }}
          </div>

          <!-- Data e Notas -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Data da Avaliação *</label>
              <input 
                v-model="assessmentDate" 
                type="date" 
                required
                class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-xs focus:outline-none focus:border-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Anotações / Contexto</label>
              <input 
                v-model="notes" 
                type="text" 
                placeholder="Ex: Reavaliação após 60 dias de consultoria..."
                class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-purple-500"
              />
            </div>
          </div>

          <!-- Seção Composição Corporal -->
          <div>
            <h4 class="text-xs font-bold text-purple-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
              <span>⚖️</span> Composição Corporal
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Peso Corporal (kg) *</label>
                <input 
                  v-model.number="metrics.WEIGHT" 
                  type="number" 
                  step="0.1" 
                  required
                  placeholder="Ex: 78.5"
                  class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500"
                />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Gordura Corporal (% BF)</label>
                <input 
                  v-model.number="metrics.BODY_FAT" 
                  type="number" 
                  step="0.1" 
                  placeholder="Ex: 15.2"
                  class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500"
                />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Massa Muscular (kg)</label>
                <input 
                  v-model.number="metrics.MUSCLE_MASS" 
                  type="number" 
                  step="0.1" 
                  placeholder="Ex: 38.0"
                  class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500"
                />
              </div>
            </div>
          </div>

          <!-- Seção Perímetros Corporais -->
          <div>
            <h4 class="text-xs font-bold text-purple-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
              <span>📏</span> Perímetros & Circunferências (cm)
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Cintura</label>
                <input v-model.number="metrics.WAIST" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Abdômen</label>
                <input v-model.number="metrics.ABDOMEN" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Quadril</label>
                <input v-model.number="metrics.HIP" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Tórax</label>
                <input v-model.number="metrics.CHEST" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Braço Direito</label>
                <input v-model.number="metrics.ARM_RIGHT" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Braço Esquerdo</label>
                <input v-model.number="metrics.ARM_LEFT" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Coxa Direita</label>
                <input v-model.number="metrics.THIGH_RIGHT" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Coxa Esquerda</label>
                <input v-model.number="metrics.THIGH_LEFT" type="number" step="0.5" placeholder="cm" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-mono text-xs focus:outline-none focus:border-purple-500" />
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
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
              :disabled="saving"
              class="px-5 py-2.5 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl shadow-lg shadow-purple-600/20 disabled:opacity-50 flex items-center gap-2"
            >
              <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ saving ? 'Salvando...' : 'Salvar Avaliação' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { assessmentService } from '../services/assessmentService'

const props = defineProps<{
  isOpen: boolean
  studentId: string
  studentName: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const assessmentDate = ref(new Date().toISOString().split('T')[0])
const notes = ref('')
const saving = ref(false)
const errorMessage = ref('')

const metrics = reactive<Record<string, number | null>>({
  WEIGHT: null,
  BODY_FAT: null,
  MUSCLE_MASS: null,
  WAIST: null,
  ABDOMEN: null,
  HIP: null,
  CHEST: null,
  ARM_RIGHT: null,
  ARM_LEFT: null,
  THIGH_RIGHT: null,
  THIGH_LEFT: null,
})

watch(() => props.isOpen, (open) => {
  if (open) {
    assessmentDate.value = new Date().toISOString().split('T')[0]
    notes.value = ''
    errorMessage.value = ''
    Object.keys(metrics).forEach(k => { metrics[k] = null })
  }
})

function close() {
  emit('close')
}

async function submit() {
  if (metrics.WEIGHT === null || metrics.WEIGHT <= 0) {
    errorMessage.value = 'O peso corporal é obrigatório.'
    return
  }

  saving.value = true
  errorMessage.value = ''

  try {
    const payloadMetrics: Array<{ metric_code: string; value: number }> = []
    for (const [code, val] of Object.entries(metrics)) {
      if (val !== null && val > 0) {
        payloadMetrics.push({ metric_code: code, value: Number(val) })
      }
    }

    await assessmentService.createAssessment(props.studentId, {
      date: assessmentDate.value,
      notes: notes.value || undefined,
      metrics: payloadMetrics,
    })

    emit('created')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao salvar avaliação física.'
  } finally {
    saving.value = false
  }
}
</script>
