import { apiClient } from '@/shared/api/apiClient'
import type {
  AdminMetrics,
  AdminTenantsResponse,
  AdminTenantDetail,
  AdminAuditLog
} from '../types'

export const adminService = {
  async getMetrics(): Promise<AdminMetrics> {
    return await apiClient.get<AdminMetrics>('/admin/metrics')
  },

  async listTenants(params?: {
    status?: string
    search?: string
    page?: number
    limit?: number
  }): Promise<AdminTenantsResponse> {
    const query = new URLSearchParams()
    if (params?.status) query.append('status', params.status)
    if (params?.search) query.append('search', params.search)
    if (params?.page) query.append('page', String(params.page))
    if (params?.limit) query.append('limit', String(params.limit))

    const url = `/admin/tenants${query.toString() ? `?${query.toString()}` : ''}`
    return await apiClient.get<AdminTenantsResponse>(url)
  },

  async getTenantDetail(id: string): Promise<AdminTenantDetail> {
    return await apiClient.get<AdminTenantDetail>(`/admin/tenants/${id}`)
  },

  async updateTenantStatus(id: string, status: string, reason?: string): Promise<{ id: string; status: string; message: string }> {
    return await apiClient.patch<{ id: string; status: string; message: string }>(`/admin/tenants/${id}/status`, {
      status,
      reason
    })
  },

  async overrideSubscription(
    tenantId: string,
    planCode: string,
    reason?: string
  ): Promise<any> {
    return await apiClient.put(`/admin/tenants/${tenantId}/subscription`, {
      plan_code: planCode,
      reason
    })
  },

  async listAuditLogs(params?: {
    action?: string
    target_type?: string
    limit?: number
    offset?: number
  }): Promise<{ logs: AdminAuditLog[]; count: number }> {
    const query = new URLSearchParams()
    if (params?.action) query.append('action', params.action)
    if (params?.target_type) query.append('target_type', params.target_type)
    if (params?.limit) query.append('limit', String(params.limit))
    if (params?.offset) query.append('offset', String(params.offset))

    const url = `/admin/audit-logs${query.toString() ? `?${query.toString()}` : ''}`
    return await apiClient.get<{ logs: AdminAuditLog[]; count: number }>(url)
  }
}
