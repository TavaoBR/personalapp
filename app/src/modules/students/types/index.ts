export interface StudentSummary {
  id: string
  name: string
  email: string
  phone?: string | null
  gender?: string | null
  birth_date?: string | null
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED'
  trainer?: {
    id: string
    name: string
    email: string
  } | null
  created_at: string
}

export interface StudentDetail {
  id: string
  user: {
    id: string
    name: string
    email: string
    phone?: string | null
  }
  birth_date?: string | null
  gender?: string | null
  emergency_contact_name?: string | null
  emergency_contact_phone?: string | null
  notes?: string | null
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED'
  trainer?: {
    id: string
    name: string
    email: string
  } | null
  created_at: string
}

export interface EnrollStudentPayload {
  name: string
  email: string
  password?: string
  gender?: string
  birth_date?: string
  phone?: string
  emergency_contact_name?: string
  emergency_contact_phone?: string
  notes?: string
  trainer_id?: string
}

export interface UpdateStudentPayload {
  gender?: string | null
  emergencyContactName?: string | null
  emergencyContactPhone?: string | null
  notes?: string | null
}
