import { apiClient } from '@/shared/api/apiClient'
import type {
  AnamnesisTemplateSummary,
  AnamnesisTemplateDetail,
  AnamnesisTemplateVersion,
  AnamnesisQuestion,
  StudentAnamnesisSummary,
  StudentAnamnesisDetail
} from '../types'

export const anamnesisService = {
  async listTemplates(): Promise<AnamnesisTemplateSummary[]> {
    const res = await apiClient.get<{ templates: AnamnesisTemplateSummary[] }>('/anamnesis-templates')
    return res.templates || []
  },

  async createTemplate(payload: {
    title: string
    description?: string
    questions: Array<{
      text: string
      type: string
      required: boolean
      order_index: number
      configuration?: any
    }>
  }): Promise<AnamnesisTemplateDetail> {
    return await apiClient.post<AnamnesisTemplateDetail>('/anamnesis-templates', payload)
  },

  async getTemplate(id: string): Promise<AnamnesisTemplateDetail> {
    return await apiClient.get<AnamnesisTemplateDetail>(`/anamnesis-templates/${id}`)
  },

  async createVersion(
    templateId: string,
    payload: {
      questions: Array<{
        text: string
        type: string
        required: boolean
        order_index: number
        configuration?: any
      }>
    }
  ): Promise<AnamnesisTemplateVersion> {
    return await apiClient.post<AnamnesisTemplateVersion>(`/anamnesis-templates/${templateId}/versions`, payload)
  },

  async listStudentAnamneses(studentId: string): Promise<StudentAnamnesisSummary[]> {
    const res = await apiClient.get<{ anamneses: StudentAnamnesisSummary[] }>(`/students/${studentId}/anamneses`)
    return res.anamneses || []
  },

  async submitAnamnesis(
    studentId: string,
    payload: {
      version_id: string
      answers: Array<{
        question_id?: string
        question_text?: string
        answer: any
      }>
      notes?: string
      answered_at?: string
    }
  ): Promise<StudentAnamnesisDetail> {
    return await apiClient.post<StudentAnamnesisDetail>(`/students/${studentId}/anamneses`, payload)
  },

  async getAnamnesisDetail(id: string): Promise<StudentAnamnesisDetail> {
    return await apiClient.get<StudentAnamnesisDetail>(`/anamneses/${id}`)
  },

  async getMyAnamneses(): Promise<StudentAnamnesisSummary[]> {
    const res = await apiClient.get<{ anamneses: StudentAnamnesisSummary[] }>('/me/anamneses')
    return res.anamneses || []
  }
}
