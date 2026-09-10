export type MuscleGroup = 
  | 'CHEST' 
  | 'BACK' 
  | 'LEGS' 
  | 'SHOULDERS' 
  | 'BICEPS' 
  | 'TRICEPS' 
  | 'ABDOMINALS' 
  | 'CORE' 
  | 'CARDIO' 
  | 'FULL_BODY'

export interface Exercise {
  id: string
  name: string
  muscle_group: MuscleGroup
  equipment?: string | null
  description?: string | null
  video_url?: string | null
  origin: 'GLOBAL' | 'TENANT'
  is_custom: boolean
  global_exercise_id?: string | null
  status: 'ACTIVE' | 'ARCHIVED'
  version?: number
  created_at?: string
}

export interface CreateExercisePayload {
  name: string
  muscle_group: MuscleGroup
  equipment?: string | null
  description?: string | null
  video_url?: string | null
  global_exercise_id?: string | null
}

export interface UpdateExercisePayload {
  name?: string | null
  muscle_group?: MuscleGroup | null
  equipment?: string | null
  description?: string | null
  video_url?: string | null
}
