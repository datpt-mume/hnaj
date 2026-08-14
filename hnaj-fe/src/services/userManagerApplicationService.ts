import { apiRequest } from './httpClient'
import { userTokenStorage } from './tokenStorage'

export async function submitManagerApplication(payload: {
  place_id: number
  email?: string
  representative_name?: string
  proof_reference?: string
}) {
  return apiRequest<{ id: number; status: string }>('/manager-applications', {
    method: 'POST',
    token: userTokenStorage.get(),
    body: payload,
  })
}
