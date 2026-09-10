import { apiClient } from '@/shared/api/apiClient'
import type { TenantUsage } from '../types'

export const tenantService = {
  async getUsage(): Promise<TenantUsage> {
    return apiClient.get<TenantUsage>('/api/saas/usage')
  },

  async switchTenant(tenantId: string): Promise<{ access_token: string; active_tenant: any }> {
    return apiClient.post('/api/auth/switch-tenant', { tenantId })
  },
}
