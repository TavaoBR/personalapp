<script setup lang="ts">
import { apiClient } from '@/shared/api/apiClient';
import Badge from '@/shared/components/ui/Badge.vue';
import Button from '@/shared/components/ui/Button.vue';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import {
  AlertOctagon,
  Building,
  CheckCircle,
  Clock,
  DollarSign,
  Shield,
  Users,
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface AdminMetrics {
  tenants: {
    total: number;
    active: number;
    suspended: number;
    personal: number;
    assessoria: number;
  };
  users: {
    total_active: number;
  };
  students: {
    total_active: number;
  };
  financial: {
    estimated_mrr: number;
    active_subscriptions: number;
  };
}

const metrics = ref<AdminMetrics | null>(null);
const tenantsList = ref<any[]>([]);
const auditLogs = ref<any[]>([]);
const isLoading = ref(true);

async function loadAdminData() {
  isLoading.value = true;
  try {
    const [metricsRes, tenantsRes, logsRes] = await Promise.all([
      apiClient.get<AdminMetrics>('/admin/metrics'),
      apiClient.get<any>('/admin/tenants?limit=5'),
      apiClient.get<any>('/admin/audit-logs?limit=5'),
    ]);
    metrics.value = metricsRes;
    tenantsList.value = tenantsRes.items || [];
    auditLogs.value = logsRes.logs || [];
  } catch (err) {
    console.error('Erro ao carregar dados administrativos:', err);
  } finally {
    isLoading.value = false;
  }
}

async function toggleTenantStatus(tenantId: string, currentStatus: string) {
  const newStatus = currentStatus === 'ACTIVE' ? 'SUSPENDED' : 'ACTIVE';
  const reason = prompt(`Motivo para alterar status para ${newStatus}:`) || 'Alteração manual pelo Admin';

  try {
    await apiClient.patch(`/admin/tenants/${tenantId}/status`, {
      status: newStatus,
      reason,
    });
    await loadAdminData();
  } catch (err: any) {
    alert(err.message || 'Erro ao alterar status do tenant.');
  }
}

onMounted(() => {
  loadAdminData();
});
</script>

<template>
  <AppLayout>
    <!-- Cabeçalho Superadmin -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <Shield class="w-5 h-5 text-amber-500" />
          <Badge variant="warning" size="sm">Superadmin Access</Badge>
        </div>
        <h1 class="text-3xl font-bold font-display text-slate-900 dark:text-white">
          Gestão Global da Plataforma
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          Métricas de infraestrutura, controle de tenants e auditoria do SaaS.
        </p>
      </div>

      <Button variant="outline" size="sm" @click="loadAdminData">
        Atualizar Dados
      </Button>
    </div>

    <!-- Grid de Métricas Globais -->
    <div v-if="metrics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Tenants -->
      <div class="p-6 rounded-3xl glass-card">
        <div class="flex items-center justify-between mb-3 text-slate-500">
          <span class="text-xs font-semibold uppercase">Total de Tenants</span>
          <Building class="w-5 h-5 text-brand-500" />
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold font-display text-slate-900 dark:text-white">
            {{ metrics.tenants.total }}
          </span>
          <span class="text-xs text-emerald-600 font-semibold">
            {{ metrics.tenants.active }} ativos
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-1">
          {{ metrics.tenants.personal }} Personais • {{ metrics.tenants.assessoria }} Assessorias
        </p>
      </div>

      <!-- Usuários Ativos -->
      <div class="p-6 rounded-3xl glass-card">
        <div class="flex items-center justify-between mb-3 text-slate-500">
          <span class="text-xs font-semibold uppercase">Usuários na Base</span>
          <Users class="w-5 h-5 text-indigo-500" />
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold font-display text-slate-900 dark:text-white">
            {{ metrics.users.total_active }}
          </span>
          <span class="text-xs text-slate-400">ativos</span>
        </div>
        <p class="text-xs text-slate-400 mt-1">
          {{ metrics.students.total_active }} perfis de alunos cadastrados
        </p>
      </div>

      <!-- Receita Estimada (MRR) -->
      <div class="p-6 rounded-3xl glass-card">
        <div class="flex items-center justify-between mb-3 text-slate-500">
          <span class="text-xs font-semibold uppercase">MRR SaaS Estimado</span>
          <DollarSign class="w-5 h-5 text-emerald-500" />
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold font-display text-slate-900 dark:text-white">
            R$ {{ metrics.financial.estimated_mrr.toFixed(2) }}
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-1">
          {{ metrics.financial.active_subscriptions }} assinaturas ativas
        </p>
      </div>

      <!-- Tenants Suspensos -->
      <div class="p-6 rounded-3xl glass-card">
        <div class="flex items-center justify-between mb-3 text-slate-500">
          <span class="text-xs font-semibold uppercase">Tenants Bloqueados</span>
          <AlertOctagon class="w-5 h-5 text-rose-500" />
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold font-display text-slate-900 dark:text-white">
            {{ metrics.tenants.suspended }}
          </span>
          <span class="text-xs text-rose-500 font-semibold">suspensos</span>
        </div>
        <p class="text-xs text-slate-400 mt-1">Por inadimplência ou suporte</p>
      </div>
    </div>

    <!-- Tabela de Tenants e Auditoria -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Tenants Recentes -->
      <div class="lg:col-span-2 rounded-3xl glass-card p-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
          <Building class="w-5 h-5 text-brand-500" />
          <span>Tenants da Plataforma</span>
        </h2>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100 dark:border-slate-800">
              <tr>
                <th class="py-3 px-2">Organização</th>
                <th class="py-3 px-2">Tipo</th>
                <th class="py-3 px-2">Alunos</th>
                <th class="py-3 px-2">Status</th>
                <th class="py-3 px-2 text-right">Ação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="t in tenantsList" :key="t.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                <td class="py-3 px-2 font-bold text-slate-900 dark:text-white">
                  {{ t.name }}
                </td>
                <td class="py-3 px-2 text-xs text-slate-500">
                  {{ t.type }}
                </td>
                <td class="py-3 px-2 text-xs">
                  {{ t.active_students }}
                </td>
                <td class="py-3 px-2">
                  <Badge :variant="t.status === 'ACTIVE' ? 'success' : 'danger'" size="sm">
                    {{ t.status }}
                  </Badge>
                </td>
                <td class="py-3 px-2 text-right">
                  <Button
                    size="sm"
                    :variant="t.status === 'ACTIVE' ? 'danger' : 'primary'"
                    @click="toggleTenantStatus(t.id, t.status)"
                  >
                    {{ t.status === 'ACTIVE' ? 'Suspender' : 'Reativar' }}
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Trilha de Auditoria -->
      <div class="rounded-3xl glass-card p-6">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
          <Clock class="w-4 h-4 text-brand-500" />
          <span>Logs de Auditoria</span>
        </h3>

        <div class="space-y-3">
          <div
            v-for="log in auditLogs"
            :key="log.id"
            class="p-3 rounded-2xl bg-slate-100/50 dark:bg-slate-800/40 text-xs"
          >
            <div class="flex items-center justify-between font-bold text-slate-800 dark:text-slate-200">
              <span>{{ log.action }}</span>
              <span class="text-[10px] text-slate-400">{{ log.target_type }}</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">
              Por: {{ log.admin_name }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
