export type ManagerApplicationStatus = 'pending' | 'approved' | 'rejected'

export type ManagerApplication = {
  id: number
  place_id: number | null
  user_id: number | null
  email: string
  representative_name: string
  proof_reference: string | null
  status: ManagerApplicationStatus
  review_reason: string | null
  place?: { id: number; name: string; district?: { id: number; name: string } | null } | null
  created_at?: string | null
}
