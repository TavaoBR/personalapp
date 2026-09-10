import { apiClient } from '@/shared/api/apiClient'
import type { WorkoutTemplateSummary, CreateTemplatePayload } from '../types'

export const templateService = {
  async list(): Promise<WorkoutTemplateSummary[]> {
    const res = await apiClient.get<{ templates: WorkoutTemplateSummary[] }>('/api/workout-templates')
    return res.templates
  },

  async getById(id: string): Promise<WorkoutTemplateSummary> {
    return apiClient.get<WorkoutTemplateSummary>(`/api/workout-templates/${id}`)
  },

  async create(payload: CreateTemplatePayload): Promise<WorkoutTemplateSummary> {
    return apiClient.post<WorkoutTemplateSummary>('/api/workout-templates', payload)
  },

  async createVersion(id: string, payload: Partial<CreateTemplatePayload>): Promise<any> {
    return apiClient.post(`/api/workout-templates/${id}/versions`, payload)
  },

  async assignToStudents(
    templateId: string, 
    studentIds: string[], 
    planName?: string
  ): Promise<Array<{ student_id: string; plan_id: string; plan_name: string }>> {
    return apiClient.post(`/api/workout-templates/${templateId}/assign`, {
      student_ids: studentIds,
      plan_name: planName,
    })
  },
}
