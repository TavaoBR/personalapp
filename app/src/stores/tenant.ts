import { apiClient } from '@/shared/api/apiClient';
import type { Tenant, TenantUsageSummary } from '@/shared/types/tenant';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useTenantStore = defineStore('tenant', () => {
  const currentTenant = ref<Tenant | null>(null);
  const usage = ref<TenantUsageSummary | null>(null);
  const isLoading = ref<boolean>(false);

  async function loadUsage() {
    try {
      const res = await apiClient.get<TenantUsageSummary>('/saas/usage');
      usage.value = res;
      return res;
    } catch {
      usage.value = null;
      return null;
    }
  }

  return {
    currentTenant,
    usage,
    isLoading,
    loadUsage,
  };
});
