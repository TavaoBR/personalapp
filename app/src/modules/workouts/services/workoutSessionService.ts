import { apiClient } from '@/shared/api/apiClient'
import type { WorkoutSessionDetail, WorkoutSessionHistoryItem, RecordSetPayload } from '../types/session'

export const workoutSessionService = {
  async start(workoutDayId?: string, studentId?: string): Promise<WorkoutSessionDetail> {
    return apiClient.post<WorkoutSessionDetail>('/api/workout-sessions', {
      workout_day_id: workoutDayId,
      student_id: studentId,
    })
  },

  async getById(sessionId: string): Promise<WorkoutSessionDetail> {
    return apiClient.get<WorkoutSessionDetail>(`/api/workout-sessions/${sessionId}`)
  },

  async recordSet(
    sessionId: string,
    exerciseExecutionId: string,
    payload: RecordSetPayload
  ): Promise<any> {
    return apiClient.post(
      `/api/workout-sessions/${sessionId}/exercises/${exerciseExecutionId}/sets`,
      payload
    )
  },

  async complete(sessionId: string, feedbackNotes?: string): Promise<WorkoutSessionDetail> {
    return apiClient.post<WorkoutSessionDetail>(`/api/workout-sessions/${sessionId}/complete`, {
      feedback_notes: feedbackNotes,
    })
  },

  async getStudentHistory(studentId: string, period?: string, status?: string): Promise<WorkoutSessionHistoryItem[]> {
    const params = new URLSearchParams()
    if (period) params.append('period', period)
    if (status) params.append('status', status)
    const qs = params.toString() ? `?${params.toString()}` : ''
    const res = await apiClient.get<{ history: WorkoutSessionHistoryItem[] }>(
      `/api/students/${studentId}/workout-sessions${qs}`
    )
    return res.history
  },

  async getMyHistory(period?: string, status?: string): Promise<WorkoutSessionHistoryItem[]> {
    const params = new URLSearchParams()
    if (period) params.append('period', period)
    if (status) params.append('status', status)
    const qs = params.toString() ? `?${params.toString()}` : ''
    const res = await apiClient.get<{ history: WorkoutSessionHistoryItem[] }>(
      `/api/me/workout-history${qs}`
    )
    return res.history
  },
}
