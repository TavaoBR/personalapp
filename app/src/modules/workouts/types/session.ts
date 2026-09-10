export interface WorkoutSetExecutionItem {
  id: string
  set_number: number
  reps_prescribed?: string | null
  reps_performed?: number | null
  weight_prescribed?: string | null
  weight_performed?: number | null
  rest_seconds?: number | null
  rpe?: number | null
  completed: boolean
  notes?: string | null
}

export interface WorkoutExerciseExecutionItem {
  id: string
  order_index: number
  exercise_name: string
  muscle_group: string
  equipment?: string | null
  video_url?: string | null
  notes?: string | null
  sets: WorkoutSetExecutionItem[]
}

export interface WorkoutSessionDetail {
  id: string
  student_id: string
  workout_plan_id?: string | null
  workout_plan_name?: string | null
  workout_day_id?: string | null
  workout_day_name?: string | null
  started_at: string
  finished_at?: string | null
  duration_seconds?: number | null
  duration_minutes?: number | null
  status: 'IN_PROGRESS' | 'COMPLETED' | 'ABANDONED'
  completion_percentage: number
  feedback_notes?: string | null
  exercises: WorkoutExerciseExecutionItem[]
}

export interface WorkoutSessionHistoryItem {
  id: string
  workout_day_name?: string | null
  started_at: string
  finished_at?: string | null
  duration_seconds?: number | null
  duration_minutes?: number | null
  status: 'IN_PROGRESS' | 'COMPLETED' | 'ABANDONED'
  completion_percentage: number
  feedback_notes?: string | null
  exercises_count: number
}

export interface RecordSetPayload {
  set_number: number
  reps_performed?: number
  weight_performed?: number
  rpe?: number
  rest_seconds?: number
  completed?: boolean
  notes?: string
}
