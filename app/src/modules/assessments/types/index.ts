export interface MetricDefinition {
  id: string
  name: string
  code: string
  unit: string
  category: string
  is_custom: boolean
  status: string
}

export interface AssessmentMetricValue {
  metric_id: string
  metric_name: string
  metric_code: string
  unit: string
  category: string
  value: number
}

export interface PhysicalAssessmentSummary {
  id: string
  student_id: string
  trainer_id?: string | null
  trainer_name?: string | null
  date: string
  notes?: string | null
  metrics_count: number
  metrics?: AssessmentMetricValue[]
}

export interface PhysicalAssessmentDetail {
  id: string
  student_id: string
  trainer_id?: string | null
  trainer_name?: string | null
  date: string
  notes?: string | null
  metrics: AssessmentMetricValue[]
}

export interface MetricProgressPoint {
  date: string
  value: number
  source: string
}

export interface StudentProgressSummary {
  metric_code: string
  metric_name: string
  unit: string
  data_points: MetricProgressPoint[]
  min_value: number | null
  max_value: number | null
  latest_value: number | null
  initial_value: number | null
  delta: number | null
  delta_percentage: number | null
}

export type Assessment = PhysicalAssessmentSummary
export type AssessmentDetail = PhysicalAssessmentDetail
export type StudentProgressData = StudentProgressSummary

