import type { DiscoveryOpeningHour, DiscoveryPlace } from './discoveryService'
import { apiRequest } from './httpClient'
import { userTokenStorage } from './tokenStorage'

/** Contract at docs/api-place-detail.md — GET /api/places/{id} (public, optional auth). */

export type PlaceImage = {
  image_url: string
  alt_text: string | null
}

export type PlaceDetail = DiscoveryPlace & {
  description: string | null
  phone: string | null
  website_url: string | null
  is_verified: boolean
  images: PlaceImage[]
  opening_hours: DiscoveryOpeningHour[]
}

export async function getPlaceDetail(placeId: number): Promise<PlaceDetail> {
  const token = userTokenStorage.get()

  const response = await apiRequest<PlaceDetail>(`/places/${placeId}`, {
    method: 'GET',
    token,
  })

  return response.data
}
