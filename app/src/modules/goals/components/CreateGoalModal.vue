<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>🎯</span> Estabelecer Nova Meta
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
              Título da Meta *
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Ex: Atingir 75 kg com menos de 15% de gordura"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-rose-500"
            />
          </div>

          <!-- Métrica & Unidade -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Métrica Associada
              </label>
              <select
                v-model="form.metric_code"
                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500"
              >
                <option value="">Geral / Desafio</option>
                <option value="WEIGHT">Peso Corporal</option>
                <option value="BODY_FAT">% Gordura Corporal</option>
                <option value="WAIST">Circunferência Abdominal</option>
                <option value="BENCH_PRESS">Carga no Supino</option>
                <option value="SQUAT">Carga no Agachamento</option>
                <option value="DEADLIFT">Carga no Levantamento Terra</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Unidade *
              </label>
              <input
                v-model="form.unit"
                type="text"
                required
                placeholder="Ex: kg, %, cm, sessões"
                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500 font-mono"
              />
            </div>
          </div>

          <!-- Valores: Inicial & Alvo -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Valor Inicial
              </label>
              <input
                v-model.number="form.initial_value"
                type="number"
                step="any"
                placeholder="Ex: 85"
                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500 font-mono"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Valor Alvo (Meta) *
              </label>
              <input
                v-model.number="form.target_value"
                type="number"
                step="any"
                required
                placeholder="Ex: 75"
                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500 font-mono"
              />
            </div>
          </div>

          <!-- Datas: Início & Prazo Final -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Data de Início *
              </label>
              <input
                v-model="form.start_date"
                type="date"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Prazo Previsto (Alvo)
              </label>
              <input
                v-model="form.target_date"
                type="date"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500"
              />
            </div>
          </div>

          <!-- Descrição / Observações -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Estratégia & Recomendações
            </label>
            <textarea
              v-model="form.description"
              rows="2"
              placeholder="Ex: Foco em déficit calórico moderado com 4 treinos semanais de musculação..."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500 resize-none"
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
              class="px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 disabled:opacity-50 rounded-xl transition-all font-display shadow-lg shadow-rose-600/20"
            >
              {{ saving ? 'Criando...' : 'Criar Meta' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { goalService } from '../services/goalService'

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
  metric_code: '',
  unit: 'kg',
  initial_value: undefined as number | undefined,
  target_value: undefined as number | undefined,
  start_date: new Date().toISOString().split('T')[0],
  target_date: '',
  description: ''
})

watch(() => form.metric_code, (code) => {
  if (code === 'WEIGHT') form.unit = 'kg'
  else if (code === 'BODY_FAT') form.unit = '%'
  else if (code === 'WAIST') form.unit = 'cm'
  else if (code.includes('PRESS') || code.includes('SQUAT') || code.includes('DEADLIFT')) form.unit = 'kg'
})

function close() {
  emit('close')
}

async function handleSubmit() {
  saving.value = true
  errorMessage.value = ''
  try {
    await goalService.create(props.studentId, {
      title: form.title,
      metric_code: form.metric_code || undefined,
      unit: form.unit,
      initial_value: form.initial_value,
      target_value: form.target_value,
      start_date: form.start_date,
      target_date: form.target_date || undefined,
      description: form.description || undefined
    })
    emit('created')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao criar meta.'
  } finally {
    saving.value = false
  }
}
</script>
