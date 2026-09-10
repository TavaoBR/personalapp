<template>
  <div v-if="isOpen && payment" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>💰</span> Baixa de Pagamento
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Confirme o recebimento deste valor.</p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Info Card -->
        <div class="mt-4 p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400">Aluno</span>
            <span class="text-xs font-bold text-white">{{ payment.student_name || 'Aluno' }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400">Vencimento</span>
            <span class="text-xs font-mono text-slate-300">{{ formatDate(payment.due_date) }}</span>
          </div>
          <div class="flex items-center justify-between pt-2 border-t border-slate-800/80">
            <span class="text-xs font-bold text-slate-300">Valor a Receber</span>
            <span class="text-base font-extrabold text-emerald-400 font-mono">
              R$ {{ payment.amount.toFixed(2) }}
            </span>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <!-- Forma de Pagamento -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Meio de Pagamento *
            </label>
            <select
              v-model="form.payment_method"
              class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
            >
              <option value="PIX">⚡ PIX</option>
              <option value="CREDIT_CARD">💳 Cartão de Crédito</option>
              <option value="BOLETO">📄 Boleto Bancário</option>
              <option value="CASH">💵 Dinheiro / Espécie</option>
              <option value="OTHER">🔄 Outro</option>
            </select>
          </div>

          <!-- Data de Pagamento -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Data do Recebimento *
            </label>
            <input
              v-model="form.paid_at"
              type="date"
              required
              class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
            />
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
              {{ submitting ? 'Confirmando...' : 'Confirmar Recebimento' }}
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
import type { StudentPayment, PaymentMethod } from '../types'

const props = defineProps<{
  isOpen: boolean
  payment: StudentPayment | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'paid'): void
}>()

const submitting = ref(false)
const errorMessage = ref('')

const form = reactive({
  payment_method: 'PIX' as PaymentMethod,
  paid_at: new Date().toISOString().split('T')[0]
})

watch(() => props.isOpen, (open) => {
  if (open) {
    errorMessage.value = ''
    form.paid_at = new Date().toISOString().split('T')[0]
  }
})

function close() {
  errorMessage.value = ''
  emit('close')
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

async function handleSubmit() {
  if (!props.payment) return

  submitting.value = true
  errorMessage.value = ''
  try {
    await financialService.markAsPaid(props.payment.id, {
      payment_method: form.payment_method,
      paid_at: form.paid_at ? `${form.paid_at}T12:00:00Z` : undefined
    })

    emit('paid')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao registrar pagamento.'
  } finally {
    submitting.value = false
  }
}
</script>
