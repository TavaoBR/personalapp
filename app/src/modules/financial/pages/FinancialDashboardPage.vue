<template>
  <div class="space-y-6 pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold font-display tracking-tight text-white flex items-center gap-2">
          <span>💰</span> Gestão Financeira & Consultoria
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Controle de mensalidades, cobranças recorrentes, planos de assessoria e inadimplência.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <button
          @click="isCreatePlanModalOpen = true"
          class="px-4 py-2.5 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition-all flex items-center gap-2"
        >
          <span>💼</span>
          <span>+ Novo Plano</span>
        </button>

        <button
          @click="isCreateInvoiceModalOpen = true"
          class="px-4 py-2.5 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition-all font-display flex items-center gap-2"
        >
          <span>🧾</span>
          <span>+ Emitir Cobrança</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loadingDashboard" class="py-16 text-center text-sm text-slate-400">
      Carregando indicadores financeiros...
    </div>

    <div v-else class="space-y-6">
      <!-- 4 KPIs Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Receita Recebida -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800/80 shadow-sm relative overflow-hidden">
          <div class="absolute -right-2 -bottom-2 w-20 h-20 bg-emerald-500/5 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
            <span>RECEBIDO NO MÊS</span>
            <span class="text-base">💵</span>
          </div>
          <div class="text-2xl font-extrabold text-emerald-400 font-mono tracking-tight">
            R$ {{ (dashboard?.total_revenue_month ?? 0).toFixed(2) }}
          </div>
          <div class="text-[11px] text-slate-500 mt-1">
            Referência: {{ dashboard?.month || 'Mês atual' }}
          </div>
        </div>

        <!-- 2. Previsão a Receber -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800/80 shadow-sm relative overflow-hidden">
          <div class="absolute -right-2 -bottom-2 w-20 h-20 bg-blue-500/5 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
            <span>A RECEBER NO MÊS</span>
            <span class="text-base">⏳</span>
          </div>
          <div class="text-2xl font-extrabold text-blue-400 font-mono tracking-tight">
            R$ {{ (dashboard?.pending_revenue_month ?? 0).toFixed(2) }}
          </div>
          <div class="text-[11px] text-slate-500 mt-1">
            Faturas com vencimento este mês
          </div>
        </div>

        <!-- 3. Inadimplência -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800/80 shadow-sm relative overflow-hidden">
          <div class="absolute -right-2 -bottom-2 w-20 h-20 bg-rose-500/5 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
            <span>TOTAL EM ATRASO</span>
            <span class="text-base">⚠️</span>
          </div>
          <div class="text-2xl font-extrabold text-rose-400 font-mono tracking-tight">
            R$ {{ (dashboard?.overdue_revenue ?? 0).toFixed(2) }}
          </div>
          <div class="flex items-center gap-1.5 text-[11px] mt-1">
            <span class="text-slate-400">Inadimplência:</span>
            <span class="font-bold text-rose-400 font-mono">{{ dashboard?.default_rate_percentage ?? 0 }}%</span>
          </div>
        </div>

        <!-- 4. Alunos com Matrícula Ativa -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800/80 shadow-sm relative overflow-hidden">
          <div class="absolute -right-2 -bottom-2 w-20 h-20 bg-amber-500/5 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
            <span>ALUNOS MATRICULADOS</span>
            <span class="text-base">👥</span>
          </div>
          <div class="text-2xl font-extrabold text-white font-mono tracking-tight">
            {{ dashboard?.active_students_count ?? 0 }}
          </div>
          <div class="text-[11px] text-slate-500 mt-1">
            Planos recorrentes ativos
          </div>
        </div>
      </div>

      <!-- Alertas de Inadimplência / Vencidos -->
      <div v-if="(dashboard?.overdue_payments?.length ?? 0) > 0" class="p-5 rounded-2xl bg-rose-500/5 border border-rose-500/20 space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2 text-rose-400 font-bold text-sm">
            <span>⚠️</span>
            <span>Mensalidades Vencidas Requerendo Atenção ({{ dashboard?.overdue_payments?.length }})</span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="op in dashboard?.overdue_payments"
            :key="op.id"
            class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center justify-between gap-3"
          >
            <div>
              <div class="font-semibold text-white text-xs">{{ op.student_name }}</div>
              <div class="text-[11px] text-rose-400 font-mono">
                Venceu em {{ formatDate(op.due_date) }} ({{ op.days_overdue }} dias)
              </div>
            </div>
            <div class="text-right">
              <div class="font-mono font-bold text-rose-300 text-xs">R$ {{ op.amount.toFixed(2) }}</div>
              <button
                @click="openSettlePayment(op.id, op.student_name, op.amount, op.due_date)"
                class="mt-1 px-2.5 py-1 text-[10px] font-bold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 rounded-lg"
              >
                Dar Baixa
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Navegação por Abas Principais -->
      <div class="border-b border-slate-800 flex items-center gap-4">
        <button
          @click="mainTab = 'payments'"
          class="pb-3 text-sm font-semibold transition-all border-b-2"
          :class="mainTab === 'payments' 
            ? 'border-emerald-400 text-white' 
            : 'border-transparent text-slate-400 hover:text-slate-200'"
        >
          🧾 Cobranças & Faturas
        </button>
        <button
          @click="mainTab = 'plans'"
          class="pb-3 text-sm font-semibold transition-all border-b-2"
          :class="mainTab === 'plans' 
            ? 'border-emerald-400 text-white' 
            : 'border-transparent text-slate-400 hover:text-slate-200'"
        >
          💼 Planos de Consultoria ({{ plans.length }})
        </button>
      </div>

      <!-- Conteúdo da Aba 1: Faturas & Cobranças -->
      <div v-if="mainTab === 'payments'" class="space-y-4">
        <!-- Filtros Rápidos -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 text-xs">
            <button
              v-for="st in paymentStatusFilters"
              :key="st.value"
              @click="changeStatusFilter(st.value)"
              class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap border"
              :class="selectedStatusFilter === st.value 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 font-semibold' 
                : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-white'"
            >
              {{ st.label }}
            </button>
          </div>

          <div class="text-xs text-slate-500">
            Mostrando <strong class="text-slate-300">{{ payments.length }}</strong> faturas
          </div>
        </div>

        <!-- Tabela de Cobranças -->
        <div v-if="loadingPayments" class="py-12 text-center text-xs text-slate-400">
          Carregando faturas...
        </div>

        <div v-else-if="payments.length === 0" class="p-12 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
          <div class="w-12 h-12 mx-auto rounded-xl bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3">
            🧾
          </div>
          <h4 class="text-base font-semibold text-white">Nenhuma fatura encontrada</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Emita uma nova cobrança avulsa ou matricule alunos em planos de consultoria.
          </p>
          <button
            @click="isCreateInvoiceModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl"
          >
            Emitir Primeira Cobrança
          </button>
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-800">
              <tr>
                <th class="px-4 py-3">Aluno</th>
                <th class="px-4 py-3">Vencimento</th>
                <th class="px-4 py-3">Referência</th>
                <th class="px-4 py-3">Valor</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Pagamento</th>
                <th class="px-4 py-3 text-right">Ação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
              <tr
                v-for="p in payments"
                :key="p.id"
                class="hover:bg-slate-800/40 transition-colors"
              >
                <!-- Aluno -->
                <td class="px-4 py-3.5">
                  <RouterLink
                    :to="`/students/${p.student_id}`"
                    class="font-semibold text-white hover:text-emerald-400 transition-colors block"
                  >
                    {{ p.student_name || 'Aluno #' + p.student_id.substring(0, 6) }}
                  </RouterLink>
                  <span v-if="p.notes" class="text-[11px] text-slate-500 truncate max-w-xs block">
                    {{ p.notes }}
                  </span>
                </td>

                <!-- Vencimento -->
                <td class="px-4 py-3.5 font-mono text-slate-300 whitespace-nowrap">
                  {{ formatDate(p.due_date) }}
                </td>

                <!-- Referência -->
                <td class="px-4 py-3.5 text-slate-400">
                  {{ p.reference_period || '-' }}
                </td>

                <!-- Valor -->
                <td class="px-4 py-3.5 font-mono font-bold text-white whitespace-nowrap">
                  R$ {{ p.amount.toFixed(2) }}
                </td>

                <!-- Status Badge -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span
                    class="px-2.5 py-1 text-[10px] font-bold rounded-full border"
                    :class="getStatusBadgeClass(p.status)"
                  >
                    {{ getStatusLabel(p.status) }}
                  </span>
                </td>

                <!-- Método e Data Pagamento -->
                <td class="px-4 py-3.5 text-[11px] text-slate-400 whitespace-nowrap">
                  <span v-if="p.status === 'PAID'">
                    {{ p.payment_method }} • {{ formatDate(p.paid_at || '') }}
                  </span>
                  <span v-else class="text-slate-600">-</span>
                </td>

                <!-- Ações -->
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                  <button
                    v-if="p.status !== 'PAID' && p.status !== 'CANCELLED'"
                    @click="openSettlePayment(p.id, p.student_name || 'Aluno', p.amount, p.due_date)"
                    class="px-3 py-1 text-[11px] font-bold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 rounded-lg transition-all"
                  >
                    Dar Baixa
                  </button>
                  <span v-else class="text-[11px] text-slate-500">Concluído</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Conteúdo da Aba 2: Planos de Consultoria -->
      <div v-else-if="mainTab === 'plans'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-white">Catálogo de Planos Comerciais</h3>
            <p class="text-xs text-slate-400">Planos predefinidos para inscrição rápida de novos alunos.</p>
          </div>

          <button
            @click="isCreatePlanModalOpen = true"
            class="px-4 py-2 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-all"
          >
            + Cadastrar Plano
          </button>
        </div>

        <div v-if="loadingPlans" class="py-12 text-center text-xs text-slate-400">
          Carregando catálogo de planos...
        </div>

        <div v-else-if="plans.length === 0" class="p-12 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
          <div class="w-12 h-12 mx-auto rounded-xl bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3">
            💼
          </div>
          <h4 class="text-base font-semibold text-white">Nenhum plano cadastrado</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Crie opções de mensalidade, planos trimestrais ou anuais para padronizar suas vendas.
          </p>
          <button
            @click="isCreatePlanModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl"
          >
            Criar Primeiro Plano
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between space-y-4"
          >
            <div class="space-y-2">
              <div class="flex items-start justify-between gap-2">
                <h4 class="text-sm font-bold text-white">{{ plan.name }}</h4>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase font-mono">
                  {{ formatPeriod(plan.billing_period) }}
                </span>
              </div>

              <div class="pt-1">
                <span class="text-2xl font-extrabold text-white font-mono">
                  R$ {{ plan.price.toFixed(2) }}
                </span>
                <span class="text-xs text-slate-500 ml-1">/ {{ plan.duration_months }} {{ plan.duration_months === 1 ? 'mês' : 'meses' }}</span>
              </div>

              <p v-if="plan.description" class="text-xs text-slate-400 line-clamp-3 pt-1">
                {{ plan.description }}
              </p>
            </div>

            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
              <span>Status: <strong class="text-emerald-400 font-semibold">{{ plan.status }}</strong></span>
              <span class="text-[11px] font-mono">{{ formatDate(plan.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modais -->
    <CreatePlanModal
      :is-open="isCreatePlanModalOpen"
      @close="isCreatePlanModalOpen = false"
      @created="loadPlans"
    />

    <CreateInvoiceModal
      :is-open="isCreateInvoiceModalOpen"
      @close="isCreateInvoiceModalOpen = false"
      @created="onInvoiceCreated"
    />

    <RecordPaymentModal
      :is-open="isRecordPaymentModalOpen"
      :payment="selectedPaymentToSettle"
      @close="isRecordPaymentModalOpen = false"
      @paid="onPaymentSettled"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { financialService } from '../services/financialService'
import type {
  FinancialDashboard,
  StudentPayment,
  CoachingPlan
} from '../types'
import CreatePlanModal from '../components/CreatePlanModal.vue'
import CreateInvoiceModal from '../components/CreateInvoiceModal.vue'
import RecordPaymentModal from '../components/RecordPaymentModal.vue'

const mainTab = ref<'payments' | 'plans'>('payments')

const dashboard = ref<FinancialDashboard | null>(null)
const loadingDashboard = ref(true)

const payments = ref<StudentPayment[]>([])
const loadingPayments = ref(false)
const selectedStatusFilter = ref('')

const plans = ref<CoachingPlan[]>([])
const loadingPlans = ref(false)

const isCreatePlanModalOpen = ref(false)
const isCreateInvoiceModalOpen = ref(false)
const isRecordPaymentModalOpen = ref(false)
const selectedPaymentToSettle = ref<StudentPayment | null>(null)

const paymentStatusFilters = [
  { value: '', label: 'Todas as Faturas' },
  { value: 'PENDING', label: '⏳ Pendentes' },
  { value: 'OVERDUE', label: '⚠️ Vencidas' },
  { value: 'PAID', label: '✅ Pagas' },
]

async function loadDashboard() {
  loadingDashboard.value = true
  try {
    dashboard.value = await financialService.getDashboard()
  } catch (err) {
    console.error('Erro ao carregar dashboard financeiro:', err)
  } finally {
    loadingDashboard.value = false
  }
}

async function loadPayments(status?: string) {
  loadingPayments.value = true
  try {
    payments.value = await financialService.listPayments({
      status: status || undefined
    })
  } catch (err) {
    console.error('Erro ao listar pagamentos:', err)
  } finally {
    loadingPayments.value = false
  }
}

function changeStatusFilter(status: string) {
  selectedStatusFilter.value = status
  loadPayments(status)
}

async function loadPlans() {
  loadingPlans.value = true
  try {
    plans.value = await financialService.listCoachingPlans()
  } catch (err) {
    console.error('Erro ao listar planos:', err)
  } finally {
    loadingPlans.value = false
  }
}

function openSettlePayment(id: string, studentName: string, amount: number, dueDate: string) {
  selectedPaymentToSettle.value = {
    id,
    student_id: '',
    student_name: studentName,
    amount,
    due_date: dueDate,
    status: 'PENDING',
    created_at: ''
  }
  isRecordPaymentModalOpen.value = true
}

async function onInvoiceCreated() {
  await Promise.all([loadPayments(selectedStatusFilter.value), loadDashboard()])
}

async function onPaymentSettled() {
  await Promise.all([loadPayments(selectedStatusFilter.value), loadDashboard()])
}

function formatPeriod(period: string): string {
  if (period === 'MONTHLY') return 'Mensal'
  if (period === 'QUARTERLY') return 'Trimestral'
  if (period === 'SEMIANNUAL') return 'Semestral'
  if (period === 'ANNUAL') return 'Anual'
  return period
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

function getStatusBadgeClass(status: string): string {
  if (status === 'PAID') return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
  if (status === 'OVERDUE') return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
  if (status === 'CANCELLED') return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
}

function getStatusLabel(status: string): string {
  if (status === 'PAID') return 'Pago'
  if (status === 'OVERDUE') return 'Vencido'
  if (status === 'CANCELLED') return 'Cancelado'
  return 'Pendente'
}

onMounted(() => {
  loadDashboard()
  loadPayments()
  loadPlans()
})
</script>
