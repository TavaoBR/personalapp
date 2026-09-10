import { apiClient } from '@/shared/api/apiClient'
import type { 
  MetricDefinition, 
  PhysicalAssessmentSummary, 
  PhysicalAssessmentDetail, 
  StudentProgressSummary 
} from '../types'

export const assessmentService = {
  async listDefinitions(): Promise<MetricDefinition[]> {
    const res = await apiClient.get<{ metrics: MetricDefinition[] }>('/api/metrics/definitions')
    return res.metrics
  },

  async createDefinition(payload: { name: string; code: string; unit: string; category?: string }): Promise<MetricDefinition> {
    return apiClient.post<MetricDefinition>('/api/metrics/definitions', payload)
  },

  async listByStudent(studentId: string): Promise<PhysicalAssessmentSummary[]> {
    const res = await apiClient.get<{ assessments: PhysicalAssessmentSummary[] }>(`/api/students/${studentId}/assessments`)
    return res.assessments
  },

  async createAssessment(studentId: string, payload: {
    date?: string
    notes?: string
    metrics: Array<{ metric_id?: string; metric_code?: string; value: number }>
  }): Promise<PhysicalAssessmentDetail> {
    return apiClient.post<PhysicalAssessmentDetail>(`/api/students/${studentId}/assessments`, payload)
  },

  async getById(id: string): Promise<PhysicalAssessmentDetail> {
    return apiClient.get<PhysicalAssessmentDetail>(`/api/assessments/${id}`)
  },

  async recordMetric(studentId: string, payload: {
    metric_code: string
    value: number
    date?: string
    notes?: string
  }): Promise<any> {
    return apiClient.post(`/api/students/${studentId}/metrics`, payload)
  },

  async getProgress(studentId: string, metricCode?: string): Promise<StudentProgressSummary> {
    const qs = metricCode ? `?metric=${metricCode}` : ''
    return apiClient.get<StudentProgressSummary>(`/api/students/${studentId}/progress${qs}`)
  },

  async getMyAssessments(): Promise<PhysicalAssessmentSummary[]> {
    const res = await apiClient.get<{ assessments: PhysicalAssessmentSummary[] }>('/api/me/assessments')
    return res.assessments
  },

  async getMyProgress(metricCode?: string): Promise<StudentProgressSummary> {
    const qs = metricCode ? `?metric=${metricCode}` : ''
    return apiClient.get<StudentProgressSummary>(`/api/me/progress${qs}`)
  },
}
