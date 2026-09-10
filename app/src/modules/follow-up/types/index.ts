export interface FollowUpStep {
  id?: string
  title: string
  day_offset: number
  task_type: string
  description?: string | null
}

export interface FollowUpTemplateSummary {
  id: string
  title: string
  description?: string | null
  duration_days: number
  status: string
  latest_version: number
  created_at: string
}

export interface FollowUpTask {
  id: string
  title: string
  due_date: string
  status: 'PENDING' | 'COMPLETED' | 'CANCELLED'
  completed_at?: string | null
  notes?: string | null
}

export interface FollowUpPlanSummary {
  id: string
  student_id: string
  title: string
  start_date: string
  end_date?: string | null
  status: string
  tasks_count: number
  completed_tasks_count: number
  progress_percentage: number
}

export interface FollowUpPlanDetail {
  id: string
  student_id: string
  trainer_id?: string | null
  title: string
  start_date: string
  end_date?: string | null
  status: string
  tasks: FollowUpTask[]
  progress_percentage: number
}

export interface StudentCheckIn {
  id: string
  student_id: string
  check_in_date: string
  energy_level?: number | null
  sleep_quality?: number | null
  soreness_level?: number | null
  diet_adherence?: number | null
  notes?: string | null
  trainer_feedback?: string | null
  reviewed_at?: string | null
  created_at: string
}
