export type BillingPeriod = 'MONTHLY' | 'QUARTERLY' | 'SEMIANNUAL' | 'ANNUAL'
export type EnrollmentStatus = 'ACTIVE' | 'CANCELLED' | 'EXPIRED'
export type PaymentStatus = 'PENDING' | 'PAID' | 'CANCELLED' | 'OVERDUE'
export type PaymentMethod = 'PIX' | 'CREDIT_CARD' | 'BOLETO' | 'CASH' | 'OTHER'

export interface CoachingPlan {
  id: string
  name: string
  description?: string | null
  price: number
  billing_period: BillingPeriod
  duration_months: number
  status: string
  created_at: string
}

export interface StudentEnrollment {
  id: string
  student_id: string
  plan_name: string
  price: number
  start_date: string
  end_date?: string | null
  billing_day: number
  status: EnrollmentStatus
  notes?: string | null
  created_at: string
}

export interface StudentPayment {
  id: string
  student_id: string
  student_name?: string
  amount: number
  due_date: string
  paid_at?: string | null
  payment_method?: PaymentMethod | null
  status: PaymentStatus
  reference_period?: string | null
  notes?: string | null
  created_at: string
}

export interface OverduePaymentItem {
  id: string
  student_id: string
  student_name: string
  amount: number
  due_date: string
  days_overdue: number
}

export interface UpcomingPaymentItem {
  id: string
  student_id: string
  student_name: string
  amount: number
  due_date: string
}

export interface FinancialDashboard {
  month: string
  active_students_count: number
  total_revenue_month: number
  pending_revenue_month: number
  overdue_revenue: number
  default_rate_percentage: number
  upcoming_payments: UpcomingPaymentItem[]
  overdue_payments: OverduePaymentItem[]
}

export interface CreateCoachingPlanPayload {
  name: string
  price: number
  billing_period?: BillingPeriod
  duration_months?: number
  description?: string
}

export interface EnrollStudentPayload {
  coaching_plan_id: string
  price?: number
  start_date?: string
  billing_day?: number
  notes?: string
  generate_first_payment?: boolean
}

export interface CreatePaymentPayload {
  student_id: string
  amount: number
  due_date: string
  enrollment_id?: string
  reference_period?: string
  notes?: string
}

export interface MarkAsPaidPayload {
  payment_method: PaymentMethod
  paid_at?: string
}
