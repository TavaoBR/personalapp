export type TenantType = 'PERSONAL' | 'ASSESSORIA';

export interface Tenant {
  id: string;
  name: string;
  type: TenantType;
  status: 'ACTIVE' | 'SUSPENDED' | 'CANCELLED';
  document?: string | null;
  createdAt: string;
}

export interface SaaSPlanSummary {
  id: string;
  code: string;
  name: string;
  description?: string | null;
  priceMonthly: number;
  priceYearly: number;
  maxStudents: number | null;
  maxTrainers: number | null;
  status: string;
  features?: Array<{
    code: string;
    name: string;
    limit: number | null;
  }>;
}

export interface TenantUsageSummary {
  active_students: number;
  active_trainers: number;
  max_students: number | null;
  max_trainers: number | null;
  is_students_limit_reached: boolean;
  is_trainers_limit_reached: boolean;
  plan_code: string;
  plan_name: string;
}
