<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>💼</span> Novo Plano de Consultoria
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Defina as condições comerciais para os seus alunos.</p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <!-- Nome do Plano -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Nome do Plano *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Ex: Consultoria Premium Mensal, Trimestral Hipertrofia..."
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-emerald-500"
            />
          </div>

          <!-- Preço e Periodicidade -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Valor Total (R$) *
              </label>
              <div class="relative">
                <span class="absolute left-3.5 top-2.5 text-sm text-slate-500">R$</span>
                <input
                  v-model.number="form.price"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  placeholder="250.00"
                  class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-emerald-500"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Ciclo de Cobrança *
              </label>
              <select
                v-model="form.billing_period"
                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
              >
                <option value="MONTHLY">Mensal</option>
                <option value="QUARTERLY">Trimestral</option>
                <option value="SEMIANNUAL">Semestral</option>
                <option value="ANNUAL">Anual</option>
              </select>
            </div>
          </div>

          <!-- Duração em Meses -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Duração da Vigência (em meses) *
            </label>
            <input
              v-model.number="form.duration_months"
              type="number"
              min="1"
              max="36"
              required
              class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-emerald-500"
            />
            <p class="text-[11px] text-slate-500 mt-1">Geralmente: Mensal (1), Trimestral (3), Semestral (6), Anual (12).</p>
          </div>

          <!-- Descrição / Itens Inclusos -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Benefícios & Descrição
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Ex: Treino personalizado com periodização a cada 4 semanas, suporte via chat diário e 1 avaliação física mensal."
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
            ></textarea>
          </div>

          <div v-if="errorMessage" class="p-3 text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-xl">
            {{ errorMessage }}
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl transition-all"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 disabled:opacity-50 rounded-xl shadow-lg shadow-emerald-500/20 transition-all font-display"
            >
              {{ submitting ? 'Cadastrando...' : 'Criar Plano' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { financialService } from '../services/financialService'
import type { BillingPeriod } from '../types'

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const submitting = ref(false)
const errorMessage = ref('')

const form = reactive({
  name: '',
  price: 200,
  billing_period: 'MONTHLY' as BillingPeriod,
  duration_months: 1,
  description: ''
})

// Ajusta a duração default conforme a periodicidade selecionada
watch(() => form.billing_period, (val) => {
  if (val === 'MONTHLY') form.duration_months = 1
  else if (val === 'QUARTERLY') form.duration_months = 3
  else if (val === 'SEMIANNUAL') form.duration_months = 6
  else if (val === 'ANNUAL') form.duration_months = 12
})

function close() {
  errorMessage.value = ''
  emit('close')
}

async function handleSubmit() {
  submitting.value = true
  errorMessage.value = ''
  try {
    await financialService.createCoachingPlan({
      name: form.name.trim(),
      price: Number(form.price),
      billing_period: form.billing_period,
      duration_months: Number(form.duration_months),
      description: form.description.trim() || undefined
    })

    form.name = ''
    form.price = 200
    form.billing_period = 'MONTHLY'
    form.duration_months = 1
    form.description = ''

    emit('created')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao cadastrar plano.'
  } finally {
    submitting.value = false
  }
}
</script>
