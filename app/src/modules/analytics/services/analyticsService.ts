import { apiClient } from '@/shared/api/apiClient'
import type {
  PortfolioDashboardData,
  StudentAnalyticsResponse,
  PersonalRecord
} from '../types'

export const analyticsService = {
  async getPortfolioDashboard(): Promise<PortfolioDashboardData> {
    const res = await apiClient.get<{ dashboard: PortfolioDashboardData }>('/analytics/dashboard')
    return res.dashboard
  },

  async getStudentAnalytics(
    studentId: string,
    params?: {
      start_a?: string
      end_a?: string
      start_b?: string
      end_b?: string
    }
  ): Promise<StudentAnalyticsResponse> {
    const query = new URLSearchParams()
    if (params?.start_a) query.append('start_a', params.start_a)
    if (params?.end_a) query.append('end_a', params.end_a)
    if (params?.start_b) query.append('start_b', params.start_b)
    if (params?.end_b) query.append('end_b', params.end_b)

    const url = `/students/${studentId}/analytics${query.toString() ? `?${query.toString()}` : ''}`
    return await apiClient.get<StudentAnalyticsResponse>(url)
  },

  async getStudentPRs(studentId: string): Promise<PersonalRecord[]> {
    const res = await apiClient.get<{ personal_records: PersonalRecord[] }>(`/students/${studentId}/prs`)
    return res.personal_records || []
  },

  async getMyAnalytics(
    params?: {
      start_a?: string
      end_a?: string
      start_b?: string
      end_b?: string
    }
  ): Promise<StudentAnalyticsResponse> {
    const query = new URLSearchParams()
    if (params?.start_a) query.append('start_a', params.start_a)
    if (params?.end_a) query.append('end_a', params.end_a)
    if (params?.start_b) query.append('start_b', params.start_b)
    if (params?.end_b) query.append('end_b', params.end_b)

    const url = `/me/analytics${query.toString() ? `?${query.toString()}` : ''}`
    return await apiClient.get<StudentAnalyticsResponse>(url)
  },

  async getMyPRs(): Promise<PersonalRecord[]> {
    const res = await apiClient.get<{ personal_records: PersonalRecord[] }>('/me/prs')
    return res.personal_records || []
  }
}
