<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>📝</span> Matricular Aluno em Plano
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Aluno: <strong class="text-white">{{ studentName }}</strong></p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <!-- Seleção do Plano -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Plano de Consultoria *
            </label>
            <div v-if="loadingPlans" class="py-2 text-xs text-slate-400">Carregando planos disponíveis...</div>
            <select
              v-else
              v-model="form.coaching_plan_id"
              required
              @change="onPlanChange"
              class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
            >
              <option value="" disabled>Selecione um plano...</option>
              <option v-for="p in plans" :key="p.id" :value="p.id">
                {{ p.name }} - R$ {{ p.price.toFixed(2) }} ({{ formatPeriod(p.billing_period) }})
              </option>
            </select>
          </div>

          <!-- Preço Personalizado e Dia de Vencimento -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Valor Cobrado (R$)
              </label>
              <div class="relative">
                <span class="absolute left-3.5 top-2.5 text-sm text-slate-500">R$</span>
                <input
                  v-model.number="form.price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-emerald-500"
                />
              </div>
              <span class="text-[10px] text-slate-500 mt-1 block">Deixe o valor padrão ou aplique desconto</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Melhor Dia de Vencimento *
              </label>
              <input
                v-model.number="form.billing_day"
                type="number"
                min="1"
                max="31"
                required
                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-emerald-500"
              />
              <span class="text-[10px] text-slate-500 mt-1 block">Dia do mês para faturamento (ex: 5, 10, 20)</span>
            </div>
          </div>

          <!-- Data de Início -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Data de Início do Contrato *
            </label>
            <input
              v-model="form.start_date"
              type="date"
              required
              class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
            />
          </div>

          <!-- Checkbox: Gerar 1ª cobrança imediata -->
          <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-start gap-3">
            <input
              id="first_payment"
              v-model="form.generate_first_payment"
              type="checkbox"
              class="mt-0.5 rounded text-emerald-500 focus:ring-0 border-slate-700 bg-slate-900"
            />
            <label for="first_payment" class="text-xs text-slate-300 cursor-pointer">
              <span class="font-bold text-white block">Gerar 1ª cobrança imediatamente</span>
              Cria automaticamente a primeira fatura com o valor acordado na data de hoje/vencimento.
            </label>
          </div>

          <!-- Observações -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Notas Contratuais
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Ex: Condição especial acordada via WhatsApp com 10% de fidelidade."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
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
              :disabled="submitting || !form.coaching_plan_id"
              class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 disabled:opacity-50 rounded-xl shadow-lg shadow-emerald-500/20 transition-all font-display"
            >
              {{ submitting ? 'Matriculando...' : 'Confirmar Matrícula' }}
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
import type { CoachingPlan } from '../types'

const props = defineProps<{
  isOpen: boolean
  studentId: string
  studentName: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'enrolled'): void
}>()

const submitting = ref(false)
const errorMessage = ref('')
const loadingPlans = ref(false)
const plans = ref<CoachingPlan[]>([])

const form = reactive({
  coaching_plan_id: '',
  price: 200,
  start_date: new Date().toISOString().split('T')[0],
  billing_day: 10,
  generate_first_payment: true,
  notes: ''
})

watch(() => props.isOpen, async (open) => {
  if (open) {
    errorMessage.value = ''
    await loadPlans()
  }
})

async function loadPlans() {
  loadingPlans.value = true
  try {
    plans.value = await financialService.listCoachingPlans()
    if (plans.value.length > 0 && !form.coaching_plan_id) {
      form.coaching_plan_id = plans.value[0].id
      form.price = plans.value[0].price
    }
  } catch (err) {
    console.error('Erro ao listar planos:', err)
  } finally {
    loadingPlans.value = false
  }
}

function onPlanChange() {
  const selected = plans.value.find(p => p.id === form.coaching_plan_id)
  if (selected) {
    form.price = selected.price
  }
}

function formatPeriod(period: string): string {
  if (period === 'MONTHLY') return 'Mensal'
  if (period === 'QUARTERLY') return 'Trimestral'
  if (period === 'SEMIANNUAL') return 'Semestral'
  if (period === 'ANNUAL') return 'Anual'
  return period
}

function close() {
  errorMessage.value = ''
  emit('close')
}

async function handleSubmit() {
  submitting.value = true
  errorMessage.value = ''
  try {
    await financialService.enrollStudent(props.studentId, {
      coaching_plan_id: form.coaching_plan_id,
      price: Number(form.price),
      start_date: form.start_date,
      billing_day: Number(form.billing_day),
      generate_first_payment: form.generate_first_payment,
      notes: form.notes.trim() || undefined
    })

    emit('enrolled')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao matricular aluno no plano.'
  } finally {
    submitting.value = false
  }
}
</script>
