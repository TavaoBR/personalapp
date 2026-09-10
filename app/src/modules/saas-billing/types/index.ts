export interface SaaSPlanFeature {
  code: string
  name: string
  limit?: number | null
}

export interface SaaSPlan {
  id: string
  code: string
  name: string
  description?: string | null
  price_monthly: number
  price_yearly: number
  max_students?: number | null
  max_trainers?: number | null
  status: string
  features: SaaSPlanFeature[]
}

export interface TenantSubscription {
  id: string
  tenant_id: string
  status: 'ACTIVE' | 'CANCELLED' | 'PAST_DUE' | 'TRIALING' | 'INCOMPLETE'
  plan: SaaSPlan
  started_at: string
  current_period_start: string
  current_period_end: string
  cancel_at_period_end: boolean
  canceled_at?: string | null
}

export interface UsageQuota {
  current: number
  max?: number | null
  reached: boolean
}

export interface TenantUsage {
  tenant_id: string
  subscription_status: string
  plan_code: string
  plan_name: string
  students: UsageQuota
  trainers: UsageQuota
  features: SaaSPlanFeature[]
}
