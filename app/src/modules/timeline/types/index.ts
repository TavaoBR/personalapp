export interface TimelineEvent {
  id: string
  student_id: string
  event_type: string
  title: string
  description?: string | null
  occurred_at: string
  entity_type?: string | null
  entity_id?: string | null
  metadata?: Record<string, any> | null
  created_at: string
}

export interface TimelineResponse {
  events: TimelineEvent[]
  total: number
  limit: number
  offset: number
}
