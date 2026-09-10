export type TenantType = 'PERSONAL' | 'ASSESSORIA' | 'CONSULTORIA'
export type TenantRole = 'OWNER' | 'ADMIN' | 'MANAGER' | 'TRAINER' | 'STUDENT'

export interface TenantInfo {
  id: string
  name: string
  type: TenantType
  role: TenantRole
}

export interface TenantUsage {
  tenant_id: string
  subscription_status: string
  plan_code: string
  plan_name: string
  students: {
    current: number
    max: number | null
    reached: boolean
  }
  trainers: {
    current: number
    max: number | null
    reached: boolean
  }
  features: Array<{
    code: string
    name: string
    limit: number | null
  }>
}
