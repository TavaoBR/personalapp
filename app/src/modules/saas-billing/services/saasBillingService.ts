import { apiClient } from '@/shared/api/apiClient'
import type {
  SaaSPlan,
  TenantSubscription,
  TenantUsage
} from '../types'

export const saasBillingService = {
  async listPlans(): Promise<SaaSPlan[]> {
    const res = await apiClient.get<{ plans: SaaSPlan[] }>('/saas/plans')
    return res.plans
  },

  async getPlan(idOrCode: string): Promise<SaaSPlan> {
    return await apiClient.get<SaaSPlan>(`/saas/plans/${idOrCode}`)
  },

  async getSubscription(): Promise<TenantSubscription | null> {
    const res = await apiClient.get<{ subscription: TenantSubscription | null }>('/saas/subscription')
    return res.subscription
  },

  async subscribeOrUpgrade(planIdentifier: string): Promise<TenantSubscription> {
    return await apiClient.post<TenantSubscription>('/saas/subscription', {
      plan_code: planIdentifier
    })
  },

  async cancelSubscription(): Promise<TenantSubscription> {
    return await apiClient.post<TenantSubscription>('/saas/subscription/cancel', {})
  },

  async getUsage(): Promise<TenantUsage> {
    return await apiClient.get<TenantUsage>('/saas/usage')
  }
}
