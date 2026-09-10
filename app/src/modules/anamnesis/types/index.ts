export type AnamnesisQuestionType = 'TEXT' | 'NUMBER' | 'BOOLEAN' | 'SINGLE_CHOICE' | 'MULTIPLE_CHOICE'

export interface AnamnesisQuestion {
  id?: string
  text: string
  type: AnamnesisQuestionType
  required: boolean
  order_index: number
  configuration?: {
    options?: string[]
    placeholder?: string
    min?: number
    max?: number
    unit?: string
  } | null
}

export interface AnamnesisTemplateVersion {
  id: string
  version_number: number
  status: string
  published_at?: string | null
  created_at: string
  questions: AnamnesisQuestion[]
}

export interface AnamnesisTemplateSummary {
  id: string
  title: string
  description?: string | null
  status: string
  latest_version: number
  created_at: string
  updated_at: string
}

export interface AnamnesisTemplateDetail {
  id: string
  title: string
  description?: string | null
  status: string
  created_at: string
  updated_at: string
  versions: AnamnesisTemplateVersion[]
}

export interface StudentAnamnesisAnswer {
  id?: string
  question_id?: string
  question_text: string
  answer: string | number | boolean | string[] | any
}

export interface StudentAnamnesisSummary {
  id: string
  student_id: string
  template_title: string
  version_number: number
  answered_at: string
  status: string
  answers_count: number
}

export interface StudentAnamnesisDetail {
  id: string
  student_id: string
  template_title: string
  version_number: number
  answered_at: string
  status: string
  notes?: string | null
  answers: StudentAnamnesisAnswer[]
}
