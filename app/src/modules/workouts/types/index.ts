export interface TemplateExerciseItem {
  id?: string
  exercise_id: string
  name?: string
  muscle_group?: string
  order_index: number
  sets: number
  reps: string
  weight?: string | null
  rest_seconds?: number | null
  rpe?: string | null
  notes?: string | null
}

export interface TemplateDayItem {
  id?: string
  name: string
  order_index: number
  exercises: TemplateExerciseItem[]
}

export interface WorkoutTemplateVersionSummary {
  id: string
  version_number: number
  status: string
  objective?: string | null
  level?: string | null
  notes?: string | null
  created_at: string
  days: TemplateDayItem[]
}

export interface WorkoutTemplateSummary {
  id: string
  name: string
  description?: string | null
  status: 'ACTIVE' | 'ARCHIVED'
  created_at: string
  current_version?: WorkoutTemplateVersionSummary | null
}

export interface CreateTemplatePayload {
  name: string
  description?: string
  objective?: string
  level?: string
  notes?: string
  days: Array<{
    name: string
    order_index: number
    exercises: Array<{
      exercise_id: string
      order_index: number
      sets: number
      reps: string
      weight?: string
      rest_seconds?: number
      rpe?: string
      notes?: string
    }>
  }>
}

export interface StudentWorkoutPlanSummary {
  id: string
  student_id: string
  name: string
  status: 'ACTIVE' | 'ARCHIVED' | 'DRAFT'
  template_id?: string | null
  template_name?: string | null
  created_at: string
  current_version?: {
    id: string
    version_number: number
    notes?: string | null
    days: TemplateDayItem[]
  } | null
}

export interface CreateStudentPlanPayload {
  name: string
  notes?: string
  days: Array<{
    name: string
    order_index: number
    exercises: Array<{
      exercise_id: string
      order_index: number
      sets: number
      reps: string
      weight?: string
      rest_seconds?: number
      rpe?: string
      notes?: string
    }>
  }>
}
