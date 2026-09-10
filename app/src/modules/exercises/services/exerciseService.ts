import { apiClient } from '@/shared/api/apiClient'
import type { Exercise, CreateExercisePayload, UpdateExercisePayload, MuscleGroup } from '../types'

export const exerciseService = {
  async search(query?: string, muscleGroup?: MuscleGroup): Promise<Exercise[]> {
    const params = new URLSearchParams()
    if (query && query.trim() !== '') {
      params.append('q', query.trim())
    }
    if (muscleGroup) {
      params.append('muscle_group', muscleGroup)
    }

    const queryString = params.toString() ? `?${params.toString()}` : ''
    const res = await apiClient.get<{ exercises: Exercise[] }>(`/api/exercises${queryString}`)
    return res.exercises
  },

  async getById(id: string): Promise<Exercise> {
    return apiClient.get<Exercise>(`/api/exercises/${id}`)
  },

  async create(payload: CreateExercisePayload): Promise<Exercise> {
    return apiClient.post<Exercise>('/api/exercises', payload)
  },

  async update(id: string, payload: UpdateExercisePayload): Promise<Exercise> {
    return apiClient.patch<Exercise>(`/api/exercises/${id}`, payload)
  },

  async archive(id: string): Promise<void> {
    return apiClient.delete<void>(`/api/exercises/${id}`)
  },
}
