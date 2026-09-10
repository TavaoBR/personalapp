export type GoalStatus = 'IN_PROGRESS' | 'COMPLETED' | 'ABANDONED'

export interface GoalProgressUpdate {
  id: string
  value: number
  previous_value?: number | null
  source: string
  notes?: string | null
  recorded_at: string
}

export interface StudentGoalSummary {
  id: string
  student_id: string
  title: string
  description?: string | null
  metric?: string | null
  initial_value?: number | null
  target_value?: number | null
  current_value?: number | null
  unit?: string | null
  progress_percentage: number
  start_date: string
  target_date?: string | null
  status: GoalStatus
  achieved_at?: string | null
  created_at: string
}

export interface StudentGoalDetail {
  id: string
  student_id: string
  trainer_id?: string | null
  title: string
  description?: string | null
  metric?: string | null
  initial_value?: number | null
  target_value?: number | null
  current_value?: number | null
  unit?: string | null
  progress_percentage: number
  start_date: string
  target_date?: string | null
  status: GoalStatus
  achieved_at?: string | null
  created_at: string
  updated_at: string
  progress_history: GoalProgressUpdate[]
}
