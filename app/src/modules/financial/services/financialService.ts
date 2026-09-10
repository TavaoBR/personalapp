import { apiClient } from '@/shared/api/apiClient'
import type {
  CoachingPlan,
  StudentEnrollment,
  StudentPayment,
  FinancialDashboard,
  CreateCoachingPlanPayload,
  EnrollStudentPayload,
  CreatePaymentPayload,
  MarkAsPaidPayload
} from '../types'

export const financialService = {
  async getDashboard(): Promise<FinancialDashboard> {
    return await apiClient.get<FinancialDashboard>('/finances/dashboard')
  },

  async listCoachingPlans(): Promise<CoachingPlan[]> {
    const res = await apiClient.get<{ plans: CoachingPlan[] }>('/finances/coaching-plans')
    return res.plans
  },

  async createCoachingPlan(payload: CreateCoachingPlanPayload): Promise<CoachingPlan> {
    return await apiClient.post<CoachingPlan>('/finances/coaching-plans', payload)
  },

  async getCoachingPlan(id: string): Promise<CoachingPlan> {
    return await apiClient.get<CoachingPlan>(`/finances/coaching-plans/${id}`)
  },

  async listStudentEnrollments(studentId: string): Promise<StudentEnrollment[]> {
    const res = await apiClient.get<{ enrollments: StudentEnrollment[] }>(`/students/${studentId}/enrollments`)
    return res.enrollments
  },

  async enrollStudent(studentId: string, payload: EnrollStudentPayload): Promise<StudentEnrollment> {
    return await apiClient.post<StudentEnrollment>(`/students/${studentId}/enrollments`, payload)
  },

  async cancelEnrollment(enrollmentId: string): Promise<StudentEnrollment> {
    return await apiClient.patch<StudentEnrollment>(`/finances/enrollments/${enrollmentId}`, {})
  },

  async listPayments(params?: {
    status?: string
    student_id?: string
    start_date?: string
    end_date?: string
  }): Promise<StudentPayment[]> {
    const query = new URLSearchParams()
    if (params?.status) query.append('status', params.status)
    if (params?.student_id) query.append('student_id', params.student_id)
    if (params?.start_date) query.append('start_date', params.start_date)
    if (params?.end_date) query.append('end_date', params.end_date)

    const url = `/finances/payments${query.toString() ? `?${query.toString()}` : ''}`
    const res = await apiClient.get<{ payments: StudentPayment[] }>(url)
    return res.payments
  },

  async createPayment(payload: CreatePaymentPayload): Promise<StudentPayment> {
    return await apiClient.post<StudentPayment>('/finances/payments', payload)
  },

  async markAsPaid(paymentId: string, payload: MarkAsPaidPayload): Promise<StudentPayment> {
    return await apiClient.post<StudentPayment>(`/finances/payments/${paymentId}/pay`, payload)
  },

  async listStudentPayments(studentId: string): Promise<StudentPayment[]> {
    const res = await apiClient.get<{ payments: StudentPayment[] }>(`/students/${studentId}/payments`)
    return res.payments
  },

  async getMyPayments(): Promise<StudentPayment[]> {
    const res = await apiClient.get<{ payments: StudentPayment[] }>('/me/payments')
    return res.payments
  }
}
