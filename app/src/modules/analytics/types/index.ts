export interface PortfolioDashboardData {
  total_students: number
  active_students: number
  students_without_active_plan: number
  students_at_risk: number
  workouts_last_7_days: number
  workouts_last_30_days: number
}

export interface StudentAnalyticsPeriod {
  start_date: string
  end_date: string
  total_sessions: number
  completed_sessions: number
  completion_rate: number
  avg_duration_minutes: number
  total_volume_kg: number
}

export interface StudentAnalyticsComparison {
  period_b: StudentAnalyticsPeriod
  diff_sessions: number
  diff_volume: number
  diff_completion_rate: number
}

export interface StudentAnalyticsResponse {
  period_a: StudentAnalyticsPeriod
  comparison?: StudentAnalyticsComparison | null
}

export interface PersonalRecord {
  exercise_name: string
  muscle_group: string
  max_weight: number
  reps_at_max?: number | null
  date: string
}
