import { apiClient } from '@/shared/api/apiClient'
import type { StudentWorkoutPlanSummary, CreateStudentPlanPayload } from '../types'

export const workoutPlanService = {
  async listByStudent(studentId: string): Promise<StudentWorkoutPlanSummary[]> {
    const res = await apiClient.get<{ plans: StudentWorkoutPlanSummary[] }>(`/api/students/${studentId}/workout-plans`)
    return res.plans
  },

  async createForStudent(studentId: string, payload: CreateStudentPlanPayload): Promise<StudentWorkoutPlanSummary> {
    return apiClient.post<StudentWorkoutPlanSummary>(`/api/students/${studentId}/workout-plans`, payload)
  },

  async getById(planId: string): Promise<StudentWorkoutPlanSummary> {
    return apiClient.get<StudentWorkoutPlanSummary>(`/api/workout-plans/${planId}`)
  },

  async activate(planId: string): Promise<{ status: string }> {
    return apiClient.post<{ status: string }>(`/api/workout-plans/${planId}/activate`)
  },

  async archive(planId: string): Promise<{ status: string }> {
    return apiClient.post<{ status: string }>(`/api/workout-plans/${planId}/archive`)
  },

  async getMyWorkouts(): Promise<any> {
    return apiClient.get('/api/me/workouts')
  },
}
