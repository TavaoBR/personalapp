import { apiClient } from '@/shared/api/apiClient'
import type {
  FollowUpTemplateSummary,
  FollowUpPlanSummary,
  FollowUpPlanDetail,
  FollowUpTask,
  StudentCheckIn
} from '../types'

export const followUpService = {
  async listTemplates(): Promise<FollowUpTemplateSummary[]> {
    const res = await apiClient.get<{ templates: FollowUpTemplateSummary[] }>('/follow-up-templates')
    return res.templates || []
  },

  async createTemplate(payload: {
    title: string
    duration_days?: number
    description?: string
    steps: Array<{
      title: string
      day_offset: number
      task_type: string
      description?: string
    }>
  }): Promise<any> {
    return await apiClient.post('/follow-up-templates', payload)
  },

  async listStudentPlans(studentId: string): Promise<FollowUpPlanSummary[]> {
    const res = await apiClient.get<{ plans: FollowUpPlanSummary[] }>(`/students/${studentId}/follow-up-plans`)
    return res.plans || []
  },

  async prescribePlan(
    studentId: string,
    payload: {
      version_id?: string
      title?: string
      start_date?: string
      tasks?: Array<{
        title: string
        due_date: string
      }>
    }
  ): Promise<FollowUpPlanDetail> {
    return await apiClient.post<FollowUpPlanDetail>(`/students/${studentId}/follow-up-plans`, payload)
  },

  async getPlanById(id: string): Promise<FollowUpPlanDetail> {
    return await apiClient.get<FollowUpPlanDetail>(`/follow-up-plans/${id}`)
  },

  async updateTask(
    taskId: string,
    status: 'COMPLETED' | 'PENDING' | 'CANCELLED',
    notes?: string
  ): Promise<FollowUpTask> {
    return await apiClient.patch<FollowUpTask>(`/follow-up-tasks/${taskId}`, {
      status,
      notes
    })
  },

  async listStudentCheckIns(studentId: string): Promise<StudentCheckIn[]> {
    const res = await apiClient.get<{ check_ins: StudentCheckIn[] }>(`/students/${studentId}/check-ins`)
    return res.check_ins || []
  },

  async createCheckIn(
    studentId: string,
    payload: {
      check_in_date?: string
      energy_level?: number
      sleep_quality?: number
      soreness_level?: number
      diet_adherence?: number
      notes?: string
    }
  ): Promise<StudentCheckIn> {
    return await apiClient.post<StudentCheckIn>(`/students/${studentId}/check-ins`, payload)
  },

  async feedbackCheckIn(checkInId: string, feedback: string): Promise<StudentCheckIn> {
    return await apiClient.post<StudentCheckIn>(`/check-ins/${checkInId}/feedback`, {
      feedback
    })
  },

  async getMyPlans(): Promise<FollowUpPlanSummary[]> {
    const res = await apiClient.get<{ plans: FollowUpPlanSummary[] }>('/me/follow-up-plans')
    return res.plans || []
  },

  async getMyCheckIns(): Promise<StudentCheckIn[]> {
    const res = await apiClient.get<{ check_ins: StudentCheckIn[] }>('/me/check-ins')
    return res.check_ins || []
  }
}
