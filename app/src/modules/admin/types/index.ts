export interface AdminMetrics {
  tenants: {
    total: number
    active: number
    suspended: number
    personal: number
    assessoria: number
  }
  users: {
    total_active: number
  }
  students: {
    total_active: number
  }
  workouts?: {
    total_completed: number
  }
  financial: {
    estimated_mrr: number
    active_subscriptions: number
  }
}

export interface AdminTenantItem {
  id: string
  name: string
  type: string
  status: 'ACTIVE' | 'SUSPENDED' | 'CANCELLED'
  document?: string | null
  active_students: number
  current_plan?: {
    code: string
    name: string
    status: string
  } | null
  created_at: string
}

export interface AdminAuditLog {
  id: string
  admin_id: string
  admin_name: string
  admin_email: string
  action: string
  target_type: string
  target_id?: string | null
  details?: Record<string, any> | null
  ip_address?: string | null
  created_at: string
}

export interface AdminTenantsResponse {
  items: AdminTenantItem[]
  total: number
  page: number
  limit: number
}

export interface AdminTenantDetail {
  id: string
  name: string
  type: string
  status: string
  document?: string | null
  created_at: string
  active_students_count: number
  trainers_count: number
  subscription?: {
    id: string
    plan_code: string
    plan_name: string
    status: string
    started_at: string
    current_period_end: string
  } | null
  owner?: {
    id: string
    name: string
    email: string
  } | null
}
