import { apiClient } from '@/shared/api/apiClient'
import type {
  GoalStatus,
  StudentGoalSummary,
  StudentGoalDetail,
  GoalProgressUpdate
} from '../types'

export const goalService = {
  async listByStudent(studentId: string, status?: GoalStatus): Promise<StudentGoalSummary[]> {
    const url = `/students/${studentId}/goals${status ? `?status=${status}` : ''}`
    const res = await apiClient.get<{ goals: StudentGoalSummary[] }>(url)
    return res.goals || []
  },

  async create(
    studentId: string,
    payload: {
      title: string
      description?: string
      metric_code?: string
      initial_value?: number
      target_value?: number
      unit?: string
      start_date?: string
      target_date?: string
    }
  ): Promise<StudentGoalDetail> {
    return await apiClient.post<StudentGoalDetail>(`/students/${studentId}/goals`, payload)
  },

  async getById(id: string): Promise<StudentGoalDetail> {
    return await apiClient.get<StudentGoalDetail>(`/goals/${id}`)
  },

  async update(
    id: string,
    payload: {
      title?: string
      description?: string
      target_value?: number
      unit?: string
      target_date?: string
      status?: GoalStatus
    }
  ): Promise<StudentGoalDetail> {
    return await apiClient.patch<StudentGoalDetail>(`/goals/${id}`, payload)
  },

  async recordProgress(
    id: string,
    payload: {
      value: number
      notes?: string
      recorded_at?: string
    }
  ): Promise<GoalProgressUpdate & { goal_status: string; goal_progress_percentage: number }> {
    return await apiClient.post(`/goals/${id}/progress`, payload)
  },

  async getMyGoals(): Promise<StudentGoalSummary[]> {
    const res = await apiClient.get<{ goals: StudentGoalSummary[] }>('/me/goals')
    return res.goals || []
  }
}
