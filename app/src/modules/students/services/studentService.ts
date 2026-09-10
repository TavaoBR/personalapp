import { apiClient } from '@/shared/api/apiClient'
import type { StudentSummary, StudentDetail, EnrollStudentPayload, UpdateStudentPayload } from '../types'

export const studentService = {
  async list(): Promise<StudentSummary[]> {
    const res = await apiClient.get<{ students: StudentSummary[] }>('/api/students')
    return res.students
  },

  async getById(id: string): Promise<StudentDetail> {
    return apiClient.get<StudentDetail>(`/api/students/${id}`)
  },

  async enroll(payload: EnrollStudentPayload): Promise<StudentDetail> {
    return apiClient.post<StudentDetail>('/api/students', payload)
  },

  async update(id: string, payload: UpdateStudentPayload): Promise<Partial<StudentDetail>> {
    return apiClient.patch<Partial<StudentDetail>>(`/api/students/${id}`, payload)
  },

  async assignTrainer(studentId: string, trainerId: string, isPrimary = true): Promise<{ message: string }> {
    return apiClient.post<{ message: string }>(`/api/students/${studentId}/assign-trainer`, {
      trainer_id: trainerId,
      is_primary: isPrimary,
    })
  },
}
