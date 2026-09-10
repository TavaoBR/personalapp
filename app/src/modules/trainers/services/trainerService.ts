import { apiClient } from '@/shared/api/apiClient'
import type { TrainerProfile, CreateTrainerPayload } from '../types'

export const trainerService = {
  async list(): Promise<TrainerProfile[]> {
    const res = await apiClient.get<{ trainers: TrainerProfile[] }>('/api/trainers')
    return res.trainers
  },

  async create(payload: CreateTrainerPayload): Promise<TrainerProfile> {
    return apiClient.post<TrainerProfile>('/api/trainers', payload)
  },
}
