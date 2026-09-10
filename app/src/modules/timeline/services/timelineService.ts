import { apiClient } from '@/shared/api/apiClient'
import type { TimelineEvent, TimelineResponse } from '../types'

export const timelineService = {
  async listByStudent(
    studentId: string,
    params?: {
      event_type?: string
      start_date?: string
      end_date?: string
      limit?: number
      offset?: number
    }
  ): Promise<TimelineResponse> {
    const query = new URLSearchParams()
    if (params?.event_type) query.append('event_type', params.event_type)
    if (params?.start_date) query.append('start_date', params.start_date)
    if (params?.end_date) query.append('end_date', params.end_date)
    if (params?.limit) query.append('limit', String(params.limit))
    if (params?.offset) query.append('offset', String(params.offset))

    const url = `/students/${studentId}/timeline${query.toString() ? `?${query.toString()}` : ''}`
    return await apiClient.get<TimelineResponse>(url)
  },

  async createEvent(
    studentId: string,
    payload: {
      title: string
      event_type?: string
      description?: string
      occurred_at?: string
    }
  ): Promise<TimelineEvent> {
    return await apiClient.post<TimelineEvent>(`/students/${studentId}/timeline`, payload)
  },

  async getMyTimeline(
    params?: {
      event_type?: string
      start_date?: string
      end_date?: string
      limit?: number
      offset?: number
    }
  ): Promise<TimelineResponse> {
    const query = new URLSearchParams()
    if (params?.event_type) query.append('event_type', params.event_type)
    if (params?.start_date) query.append('start_date', params.start_date)
    if (params?.end_date) query.append('end_date', params.end_date)
    if (params?.limit) query.append('limit', String(params.limit))
    if (params?.offset) query.append('offset', String(params.offset))

    const url = `/me/timeline${query.toString() ? `?${query.toString()}` : ''}`
    return await apiClient.get<TimelineResponse>(url)
  }
}
