export interface TrainerProfile {
  id: string
  user: {
    id: string
    name: string
    email: string
    phone?: string | null
  }
  cref?: string | null
  specialties: string[]
  bio?: string | null
  created_at: string
}

export interface CreateTrainerPayload {
  name: string
  email: string
  password?: string
  phone?: string
  cref?: string
  specialties?: string[]
  bio?: string
}
