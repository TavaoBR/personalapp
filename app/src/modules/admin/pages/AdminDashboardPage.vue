<template>
  <AppLayout>
    <div class="space-y-8 pb-16">
      <!-- Cabeçalho Superadmin -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase font-mono">
              ⚡ Superadmin Console
            </span>
          </div>
          <h1 class="text-3xl font-bold font-display text-white tracking-tight">
            Gestão Global da Plataforma
          </h1>
          <p class="text-sm text-slate-400 mt-1">
            Métricas de infraestrutura, controle de tenants e auditoria de segurança do SaaS.
          </p>
        </div>

        <button
          @click="loadAdminData"
          :disabled="isLoading"
          class="px-4 py-2 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition-all flex items-center gap-2 self-start"
        >
          <span>🔄</span>
          <span>{{ isLoading ? 'Atualizando...' : 'Atualizar Dados' }}</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading && !metrics" class="py-16 text-center text-sm text-slate-400">
        Carregando console administrativo...
      </div>

      <div v-else class="space-y-8">
        <!-- Grid de 4 Métricas Globais -->
        <div v-if="metrics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Total Tenants -->
          <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800 relative overflow-hidden">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
              <span>TENANTS ATIVOS</span>
              <span class="text-base">🏢</span>
            </div>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-extrabold text-white font-mono">
                {{ metrics.tenants.active }}
              </span>
              <span class="text-xs text-slate-500">/ {{ metrics.tenants.total }} total</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">
              {{ metrics.tenants.personal }} Personais • {{ metrics.tenants.assessoria }} Assessorias
            </p>
          </div>

          <!-- Usuários na Base -->
          <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800 relative overflow-hidden">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
              <span>USUÁRIOS ATIVOS</span>
              <span class="text-base">👥</span>
            </div>
            <div class="text-3xl font-extrabold text-indigo-400 font-mono">
              {{ metrics.users.total_active }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">
              {{ metrics.students.total_active }} perfis de alunos cadastrados
            </p>
          </div>

          <!-- Receita Estimada MRR -->
          <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800 relative overflow-hidden">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
              <span>MRR SAAS ESTIMADO</span>
              <span class="text-base">💰</span>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400 font-mono">
              R$ {{ metrics.financial.estimated_mrr.toFixed(2) }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">
              {{ metrics.financial.active_subscriptions }} assinaturas ativas
            </p>
          </div>

          <!-- Tenants Bloqueados / Suspensos -->
          <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/80 to-slate-950/80 border border-slate-800 relative overflow-hidden">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-2">
              <span>BLOQUEADOS</span>
              <span class="text-base">🚫</span>
            </div>
            <div class="text-3xl font-extrabold text-rose-400 font-mono">
              {{ metrics.tenants.suspended }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Por inadimplência ou moderação</p>
          </div>
        </div>

        <!-- Seção de Tenants e Auditoria -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Coluna 1 e 2: Tabela de Tenants -->
          <div class="lg:col-span-2 rounded-3xl bg-slate-900/60 border border-slate-800 p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>🏢</span>
                <span>Organizações & Tenants da Plataforma</span>
              </h2>

              <div class="flex items-center gap-2">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Buscar tenant..."
                  @input="handleSearch"
                  class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500 w-44"
                />
                <select
                  v-model="statusFilter"
                  @change="handleSearch"
                  class="px-2.5 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-500"
                >
                  <option value="">Todos</option>
                  <option value="ACTIVE">Ativos</option>
                  <option value="SUSPENDED">Suspensos</option>
                </select>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="text-[10px] uppercase tracking-wider text-slate-400 border-b border-slate-800 bg-slate-950/40">
                  <tr>
                    <th class="py-3 px-3">Organização</th>
                    <th class="py-3 px-3">Tipo</th>
                    <th class="py-3 px-3">Alunos</th>
                    <th class="py-3 px-3">Plano</th>
                    <th class="py-3 px-3">Status</th>
                    <th class="py-3 px-3 text-right">Ações</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                  <tr
                    v-for="t in tenantsList"
                    :key="t.id"
                    class="hover:bg-slate-800/40 transition-colors"
                  >
                    <td class="py-3.5 px-3 font-semibold text-white">
                      {{ t.name }}
                      <span v-if="t.document" class="text-[10px] text-slate-500 block font-mono">
                        {{ t.document }}
                      </span>
                    </td>
                    <td class="py-3.5 px-3 text-slate-400">
                      {{ t.type }}
                    </td>
                    <td class="py-3.5 px-3 font-mono font-semibold text-slate-300">
                      {{ t.active_students }}
                    </td>
                    <td class="py-3.5 px-3">
                      <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-mono">
                        {{ t.current_plan?.code || 'FREE' }}
                      </span>
                    </td>
                    <td class="py-3.5 px-3">
                      <span
                        class="px-2 py-0.5 text-[10px] font-bold rounded-full border"
                        :class="t.status === 'ACTIVE'
                          ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                          : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
                      >
                        {{ t.status }}
                      </span>
                    </td>
                    <td class="py-3.5 px-3 text-right space-x-1 whitespace-nowrap">
                      <button
                        @click="openTenantDetail(t)"
                        class="px-2 py-1 text-[10px] font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-all"
                      >
                        Ver
                      </button>
                      <button
                        @click="openOverridePlan(t)"
                        class="px-2 py-1 text-[10px] font-semibold text-amber-400 hover:text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 rounded-lg transition-all"
                      >
                        Plano
                      </button>
                      <button
                        @click="toggleTenantStatus(t.id, t.status)"
                        class="px-2 py-1 text-[10px] font-semibold rounded-lg transition-all"
                        :class="t.status === 'ACTIVE'
                          ? 'text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20'
                          : 'text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20'"
                      >
                        {{ t.status === 'ACTIVE' ? 'Bloquear' : 'Reativar' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Coluna 3: Trilha de Auditoria -->
          <div class="rounded-3xl bg-slate-900/60 border border-slate-800 p-6 space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>🛡️</span>
              <span>Trilha de Auditoria</span>
            </h3>

            <div v-if="auditLogs.length === 0" class="py-8 text-center text-xs text-slate-500">
              Nenhum log registrado recentemente.
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="log in auditLogs"
                :key="log.id"
                class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-xs space-y-1"
              >
                <div class="flex items-center justify-between font-bold text-white">
                  <span>{{ log.action }}</span>
                  <span class="text-[10px] text-amber-400 font-mono">{{ log.target_type }}</span>
                </div>
                <p class="text-[11px] text-slate-400">
                  Por: <strong class="text-slate-300">{{ log.admin_name }}</strong>
                </p>
                <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 font-mono">
                  <span>IP: {{ log.ip_address || '127.0.0.1' }}</span>
                  <span>{{ formatDate(log.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modais -->
    <TenantDetailModal
      :is-open="isDetailModalOpen"
      :tenant="selectedTenantForDetail"
      @close="isDetailModalOpen = false"
    />

    <OverrideSubscriptionModal
      :is-open="isOverrideModalOpen"
      :tenant="selectedTenantForOverride"
      @close="isOverrideModalOpen = false"
      @updated="loadAdminData"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '@/shared/layouts/AppLayout.vue'
import { adminService } from '../services/adminService'
import type {
  AdminMetrics,
  AdminTenantItem,
  AdminAuditLog
} from '../types'
import TenantDetailModal from '../components/TenantDetailModal.vue'
import OverrideSubscriptionModal from '../components/OverrideSubscriptionModal.vue'

const metrics = ref<AdminMetrics | null>(null)
const tenantsList = ref<AdminTenantItem[]>([])
const auditLogs = ref<AdminAuditLog[]>([])
const isLoading = ref(true)

const searchQuery = ref('')
const statusFilter = ref('')

const isDetailModalOpen = ref(false)
const selectedTenantForDetail = ref<AdminTenantItem | null>(null)

const isOverrideModalOpen = ref(false)
const selectedTenantForOverride = ref<AdminTenantItem | null>(null)

async function loadAdminData() {
  isLoading.value = true
  try {
    const [metricsRes, tenantsRes, logsRes] = await Promise.all([
      adminService.getMetrics(),
      adminService.listTenants({
        status: statusFilter.value || undefined,
        search: searchQuery.value.trim() || undefined,
        limit: 25
      }),
      adminService.listAuditLogs({ limit: 8 }),
    ])
    metrics.value = metricsRes
    tenantsList.value = tenantsRes.items || []
    auditLogs.value = logsRes.logs || []
  } catch (err) {
    console.error('Erro ao carregar dados administrativos:', err)
  } finally {
    isLoading.value = false
  }
}

function handleSearch() {
  loadAdminData()
}

function openTenantDetail(tenant: AdminTenantItem) {
  selectedTenantForDetail.value = tenant
  isDetailModalOpen.value = true
}

function openOverridePlan(tenant: AdminTenantItem) {
  selectedTenantForOverride.value = tenant
  isOverrideModalOpen.value = true
}

async function toggleTenantStatus(tenantId: string, currentStatus: string) {
  const newStatus = currentStatus === 'ACTIVE' ? 'SUSPENDED' : 'ACTIVE'
  const reason = prompt(`Motivo para alterar status para ${newStatus}:`) || 'Alteração manual pelo Superadmin'

  try {
    await adminService.updateTenantStatus(tenantId, newStatus, reason)
    await loadAdminData()
  } catch (err: any) {
    alert(err.message || 'Erro ao alterar status do tenant.')
  }
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

onMounted(() => {
  loadAdminData()
})
</script>
