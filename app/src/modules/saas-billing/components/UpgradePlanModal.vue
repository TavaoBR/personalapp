<template>
  <div v-if="isOpen && plan" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>🚀</span> Migrar para o Plano {{ plan.name }}
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Confirme os detalhes da sua assinatura.</p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Plan Details Card -->
        <div class="mt-4 p-5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3">
          <div class="flex items-baseline justify-between">
            <span class="text-xs text-slate-400">Valor da Assinatura:</span>
            <div class="text-right">
              <span class="text-2xl font-extrabold text-emerald-400 font-mono">
                R$ {{ (billingCycle === 'YEARLY' ? plan.price_yearly : plan.price_monthly).toFixed(2) }}
              </span>
              <span class="text-xs text-slate-500 ml-1">/ {{ billingCycle === 'YEARLY' ? 'ano' : 'mês' }}</span>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-800/80 space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-400">Capacidade de Alunos:</span>
              <strong class="text-white font-mono">
                {{ plan.max_students !== null ? `${plan.max_students} alunos ativos` : 'Ilimitado' }}
              </strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-400">Treinadores / Equipe:</span>
              <strong class="text-white font-mono">
                {{ plan.max_trainers !== null ? `${plan.max_trainers} treinadores` : 'Ilimitado' }}
              </strong>
            </div>
          </div>

          <div v-if="plan.description" class="pt-2 border-t border-slate-800/80 text-xs text-slate-400">
            {{ plan.description }}
          </div>
        </div>

        <!-- Alerta de alteração -->
        <div class="mt-4 p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs text-blue-300">
          ℹ️ Os novos limites e recursos serão liberados imediatamente para o seu espaço de trabalho.
        </div>

        <div v-if="errorMessage" class="mt-3 p-3 text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-xl">
          {{ errorMessage }}
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-800">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl transition-all"
          >
            Cancelar
          </button>
          <button
            type="button"
            @click="handleConfirm"
            :disabled="submitting"
            class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 disabled:opacity-50 rounded-xl shadow-lg shadow-emerald-500/20 transition-all font-display"
          >
            {{ submitting ? 'Processando...' : 'Confirmar Alteração' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { saasBillingService } from '../services/saasBillingService'
import type { SaaSPlan } from '../types'

const props = defineProps<{
  isOpen: boolean
  plan: SaaSPlan | null
  billingCycle: 'MONTHLY' | 'YEARLY'
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'upgraded'): void
}>()

const submitting = ref(false)
const errorMessage = ref('')

function close() {
  errorMessage.value = ''
  emit('close')
}

async function handleConfirm() {
  if (!props.plan) return

  submitting.value = true
  errorMessage.value = ''
  try {
    await saasBillingService.subscribeOrUpgrade(props.plan.code)
    emit('upgraded')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao alterar plano da plataforma.'
  } finally {
    submitting.value = false
  }
}
</script>
