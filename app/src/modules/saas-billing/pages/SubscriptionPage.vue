<template>
  <div class="space-y-8 pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold font-display tracking-tight text-white flex items-center gap-2">
          <span>💎</span> Assinatura da Plataforma & Planos
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Gerencie o plano do seu espaço, limites de alunos ativos e recursos contratados.
        </p>
      </div>

      <div v-if="subscription" class="flex items-center gap-2 self-start">
        <span
          class="px-3 py-1.5 text-xs font-bold rounded-xl border flex items-center gap-1.5"
          :class="subscription.status === 'ACTIVE'
            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
            : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
        >
          <span class="w-2 h-2 rounded-full" :class="subscription.status === 'ACTIVE' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
          <span>{{ subscription.status === 'ACTIVE' ? 'Plano Ativo' : subscription.status }}</span>
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-16 text-center text-sm text-slate-400">
      Carregando dados da assinatura e planos...
    </div>

    <div v-else class="space-y-8">
      <!-- 1. Card da Assinatura Atual & Consumo de Cotas (Usage) -->
      <div class="p-6 rounded-3xl bg-gradient-to-br from-slate-900/90 via-slate-900/60 to-slate-950 border border-slate-800 shadow-xl space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-800/80">
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">
              Plano Ativo no Momento
            </span>
            <div class="flex items-center gap-3">
              <h2 class="text-2xl font-extrabold text-white tracking-tight">
                {{ usage?.plan_name || subscription?.plan.name || 'Personal Starter' }}
              </h2>
              <span class="px-2.5 py-0.5 text-[10px] font-mono font-bold rounded-md bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase">
                {{ usage?.plan_code || subscription?.plan.code || 'STARTER' }}
              </span>
            </div>
            <p v-if="subscription" class="text-xs text-slate-400 mt-1">
              Renovação do ciclo em <strong class="text-white">{{ formatDate(subscription.current_period_end) }}</strong>.
            </p>
          </div>

          <div v-if="subscription && subscription.status === 'ACTIVE'" class="flex items-center gap-3">
            <button
              @click="handleCancelSubscription"
              :disabled="cancelling"
              class="px-3.5 py-2 text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 border border-rose-500/20 rounded-xl transition-all"
            >
              {{ cancelling ? 'Cancelando...' : 'Cancelar Renovação' }}
            </button>
          </div>
        </div>

        <!-- 2 Barras de Consumo de Limites (Alunos e Treinadores) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Alunos -->
          <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-semibold text-slate-300 flex items-center gap-1.5">
                <span>👥</span> Alunos Cadastrados
              </span>
              <span class="font-mono font-bold text-white">
                {{ usage?.students.current ?? 0 }} / {{ usage?.students.max !== null && usage?.students.max !== undefined ? usage.students.max : '∞' }}
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="getQuotaBarClass(usage?.students.current ?? 0, usage?.students.max)"
                :style="{ width: `${getQuotaPercentage(usage?.students.current ?? 0, usage?.students.max)}%` }"
              ></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-500">
              <span>{{ getQuotaPercentage(usage?.students.current ?? 0, usage?.students.max) }}% utilizado</span>
              <span v-if="usage?.students.reached" class="text-rose-400 font-bold">Limite alcançado!</span>
            </div>
          </div>

          <!-- Treinadores -->
          <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-semibold text-slate-300 flex items-center gap-1.5">
                <span>🏋️</span> Treinadores na Equipe
              </span>
              <span class="font-mono font-bold text-white">
                {{ usage?.trainers.current ?? 0 }} / {{ usage?.trainers.max !== null && usage?.trainers.max !== undefined ? usage.trainers.max : '∞' }}
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="getQuotaBarClass(usage?.trainers.current ?? 0, usage?.trainers.max)"
                :style="{ width: `${getQuotaPercentage(usage?.trainers.current ?? 0, usage?.trainers.max)}%` }"
              ></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-500">
              <span>{{ getQuotaPercentage(usage?.trainers.current ?? 0, usage?.trainers.max) }}% utilizado</span>
              <span v-if="usage?.trainers.reached" class="text-rose-400 font-bold">Limite alcançado!</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Seletor de Ciclo de Pagamento (Mensal / Anual) -->
      <div class="text-center space-y-3">
        <h3 class="text-xl font-bold font-display text-white">Escolha o Plano Ideal para Sua Operação</h3>
        <p class="text-xs text-slate-400 max-w-lg mx-auto">
          Escale sua consultoria fitness com flexibilidade. Mude ou cancele seu plano quando desejar.
        </p>

        <div class="inline-flex items-center p-1 rounded-2xl bg-slate-900 border border-slate-800">
          <button
            @click="billingCycle = 'MONTHLY'"
            class="px-4 py-2 text-xs font-semibold rounded-xl transition-all"
            :class="billingCycle === 'MONTHLY'
              ? 'bg-slate-800 text-white shadow-sm'
              : 'text-slate-400 hover:text-white'"
          >
            Cobrança Mensal
          </button>
          <button
            @click="billingCycle = 'YEARLY'"
            class="px-4 py-2 text-xs font-semibold rounded-xl transition-all flex items-center gap-1.5"
            :class="billingCycle === 'YEARLY'
              ? 'bg-emerald-500 text-slate-950 font-bold shadow-sm'
              : 'text-slate-400 hover:text-white'"
          >
            <span>Cobrança Anual</span>
            <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-full bg-slate-950 text-emerald-400 uppercase">
              -20%
            </span>
          </button>
        </div>
      </div>

      <!-- 3. Cards de Planos SaaS Disponíveis -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="p-6 rounded-3xl border transition-all flex flex-col justify-between space-y-6 relative"
          :class="isCurrentPlan(plan)
            ? 'bg-slate-900/90 border-emerald-500/50 shadow-lg shadow-emerald-500/10'
            : plan.code === 'PRO'
              ? 'bg-gradient-to-b from-slate-900 to-slate-950 border-indigo-500/40 shadow-md'
              : 'bg-slate-900/60 border-slate-800 hover:border-slate-700'"
        >
          <!-- Badge de Destaque -->
          <div v-if="isCurrentPlan(plan)" class="absolute -top-3 left-6 px-3 py-0.5 rounded-full bg-emerald-500 text-slate-950 text-[10px] font-extrabold uppercase font-mono tracking-wider">
            Seu Plano Atual
          </div>
          <div v-else-if="plan.code === 'PRO'" class="absolute -top-3 left-6 px-3 py-0.5 rounded-full bg-indigo-500 text-white text-[10px] font-extrabold uppercase font-mono tracking-wider">
            Mais Popular
          </div>

          <div class="space-y-4">
            <div>
              <h4 class="text-lg font-bold text-white">{{ plan.name }}</h4>
              <p class="text-xs text-slate-400 mt-1 min-h-[32px]">{{ plan.description }}</p>
            </div>

            <!-- Preço -->
            <div class="pt-2">
              <div class="flex items-baseline gap-1">
                <span class="text-3xl font-extrabold text-white font-mono">
                  R$ {{ (billingCycle === 'YEARLY' ? plan.price_yearly : plan.price_monthly).toFixed(2) }}
                </span>
                <span class="text-xs text-slate-500">/ {{ billingCycle === 'YEARLY' ? 'ano' : 'mês' }}</span>
              </div>
            </div>

            <!-- Capacidades Principais -->
            <div class="space-y-2.5 pt-4 border-t border-slate-800 text-xs">
              <div class="flex items-center gap-2 text-slate-300">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>
                  <strong class="text-white">{{ plan.max_students !== null ? plan.max_students : 'Ilimitados' }}</strong> alunos ativos
                </span>
              </div>
              <div class="flex items-center gap-2 text-slate-300">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>
                  <strong class="text-white">{{ plan.max_trainers !== null ? plan.max_trainers : 'Ilimitados' }}</strong> treinadores / equipe
                </span>
              </div>
            </div>

            <!-- Recursos Inclusos -->
            <div v-if="plan.features?.length > 0" class="space-y-2 pt-2 text-xs">
              <div
                v-for="feat in plan.features"
                :key="feat.code"
                class="flex items-center gap-2 text-slate-400"
              >
                <span class="text-indigo-400 font-bold text-[11px]">✦</span>
                <span>{{ feat.name }}</span>
              </div>
            </div>
          </div>

          <!-- Botão de Ação -->
          <div class="pt-6 border-t border-slate-800/80">
            <button
              v-if="isCurrentPlan(plan)"
              disabled
              class="w-full py-2.5 px-4 text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-xl cursor-default text-center"
            >
              Plano Vigente
            </button>
            <button
              v-else
              @click="openUpgradeModal(plan)"
              class="w-full py-2.5 px-4 text-xs font-bold text-slate-950 bg-white hover:bg-slate-200 rounded-xl transition-all shadow-md text-center font-display"
            >
              Mudar para este Plano →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Upgrade / Troca de Plano -->
    <UpgradePlanModal
      :is-open="isUpgradeModalOpen"
      :plan="selectedPlanForUpgrade"
      :billing-cycle="billingCycle"
      @close="isUpgradeModalOpen = false"
      @upgraded="loadAllData"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { saasBillingService } from '../services/saasBillingService'
import type { SaaSPlan, TenantSubscription, TenantUsage } from '../types'
import UpgradePlanModal from '../components/UpgradePlanModal.vue'

const loading = ref(true)
const cancelling = ref(false)
const billingCycle = ref<'MONTHLY' | 'YEARLY'>('MONTHLY')

const subscription = ref<TenantSubscription | null>(null)
const usage = ref<TenantUsage | null>(null)
const plans = ref<SaaSPlan[]>([])

const isUpgradeModalOpen = ref(false)
const selectedPlanForUpgrade = ref<SaaSPlan | null>(null)

async function loadAllData() {
  loading.value = true
  try {
    const [fetchedSub, fetchedUsage, fetchedPlans] = await Promise.all([
      saasBillingService.getSubscription(),
      saasBillingService.getUsage(),
      saasBillingService.listPlans()
    ])
    subscription.value = fetchedSub
    usage.value = fetchedUsage
    plans.value = fetchedPlans
  } catch (err) {
    console.error('Erro ao carregar dados SaaS Billing:', err)
  } finally {
    loading.value = false
  }
}

function isCurrentPlan(plan: SaaSPlan): boolean {
  if (subscription.value?.plan?.id === plan.id || subscription.value?.plan?.code === plan.code) {
    return true
  }
  if (usage.value?.plan_code === plan.code) {
    return true
  }
  return false
}

function openUpgradeModal(plan: SaaSPlan) {
  selectedPlanForUpgrade.value = plan
  isUpgradeModalOpen.value = true
}

async function handleCancelSubscription() {
  if (!confirm('Tem certeza que deseja cancelar a renovação da sua assinatura?')) return
  cancelling.value = true
  try {
    subscription.value = await saasBillingService.cancelSubscription()
    await loadAllData()
  } catch (err: any) {
    alert(err.message || 'Erro ao cancelar assinatura.')
  } finally {
    cancelling.value = false
  }
}

function getQuotaPercentage(current: number, max?: number | null): number {
  if (!max || max <= 0) return 10
  return Math.min(100, Math.round((current / max) * 100))
}

function getQuotaBarClass(current: number, max?: number | null): string {
  const pct = getQuotaPercentage(current, max)
  if (pct >= 100) return 'bg-rose-500'
  if (pct >= 80) return 'bg-amber-500'
  return 'bg-emerald-500'
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

onMounted(() => {
  loadAllData()
})
</script>
