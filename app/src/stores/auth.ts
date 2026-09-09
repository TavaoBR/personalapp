import { apiClient } from '@/shared/api/apiClient';
import type { TenantMembershipInfo, User } from '@/shared/types/user';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('access_token'));
  const memberships = ref<TenantMembershipInfo[]>([]);
  const activeTenantId = ref<string | null>(localStorage.getItem('active_tenant_id'));
  const isLoading = ref<boolean>(false);

  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const isPlatformAdmin = computed(() => user.value?.isPlatformAdmin === true);

  const activeMembership = computed(() => {
    if (!activeTenantId.value) return null;
    return memberships.value.find((m) => m.tenantId === activeTenantId.value) || null;
  });

  const isStudent = computed(() => activeMembership.value?.role === 'STUDENT');
  const isTrainerOrOwner = computed(() => ['OWNER', 'ADMIN', 'TRAINER'].includes(activeMembership.value?.role || ''));

  async function login(email: string, pass: string, explicitTenantId?: string) {
    isLoading.value = true;
    try {
      const payload: Record<string, string> = { email, password: pass };
      if (explicitTenantId) {
        payload['tenant_id'] = explicitTenantId;
      }

      const res = await apiClient.post<{
        access_token: string;
        user: User;
        active_tenant: { id: string; name: string; type: string } | null;
        membership: { role: string } | null;
        available_tenants: Array<{ id: string; name: string; type: 'PERSONAL' | 'ASSESSORIA'; role: string }>;
      }>('/auth/login', payload);

      token.value = res.access_token;
      apiClient.setTokens(res.access_token);

      user.value = res.user;

      if (res.available_tenants) {
        memberships.value = res.available_tenants.map((t) => ({
          id: t.id,
          tenantId: t.id,
          tenantName: t.name,
          tenantType: t.type,
          role: t.role as any,
          status: 'ACTIVE',
        }));
      }

      if (res.active_tenant) {
        activeTenantId.value = res.active_tenant.id;
        apiClient.setActiveTenantId(res.active_tenant.id);
      } else if (memberships.value.length > 0) {
        activeTenantId.value = memberships.value[0].tenantId;
        apiClient.setActiveTenantId(memberships.value[0].tenantId);
      }

      return res;
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchMe() {
    if (!token.value) return null;
    try {
      const res = await apiClient.get<{
        user: User;
        active_tenant: { id: string; name: string; type: string } | null;
        membership: { role: string; status: string } | null;
        available_tenants: Array<{ id: string; name: string; type: 'PERSONAL' | 'ASSESSORIA'; role: string }>;
      }>('/me');

      user.value = res.user;

      if (res.available_tenants) {
        memberships.value = res.available_tenants.map((t) => ({
          id: t.id,
          tenantId: t.id,
          tenantName: t.name,
          tenantType: t.type,
          role: t.role as any,
          status: 'ACTIVE',
        }));
      }

      if (res.active_tenant) {
        activeTenantId.value = res.active_tenant.id;
        apiClient.setActiveTenantId(res.active_tenant.id);
      }

      return res;
    } catch {
      logout();
      return null;
    }
  }

  async function switchTenant(newTenantId: string) {
    isLoading.value = true;
    try {
      const res = await apiClient.post<{ access_token: string; tenant_id: string }>('/auth/switch-tenant', {
        tenant_id: newTenantId,
      });

      token.value = res.access_token;
      apiClient.setTokens(res.access_token);

      activeTenantId.value = res.tenant_id;
      apiClient.setActiveTenantId(res.tenant_id);

      await fetchMe();
    } finally {
      isLoading.value = false;
    }
  }

  function logout() {
    apiClient.post('/auth/logout').catch(() => {});
    token.value = null;
    user.value = null;
    activeTenantId.value = null;
    memberships.value = [];
    apiClient.clearTokens();
  }

  // Listener para logout transparente disparado por erro 401 não renovável
  if (typeof window !== 'undefined') {
    window.addEventListener('auth:expired', () => {
      token.value = null;
      user.value = null;
      activeTenantId.value = null;
      memberships.value = [];
    });
  }

  return {
    user,
    token,
    memberships,
    activeTenantId,
    isLoading,
    isAuthenticated,
    isPlatformAdmin,
    activeMembership,
    isStudent,
    isTrainerOrOwner,
    login,
    fetchMe,
    switchTenant,
    logout,
  };
});
