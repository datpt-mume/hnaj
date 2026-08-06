import { apiRequest } from './httpClient'

/** Contract at docs/api-discovery.md — POST /api/discovery/random (public). */
export type DiscoveryOpeningHour = {
  day_of_week: number
  schedule_type: string
  opens_at: string | null
  closes_at: string | null
}

export type DiscoveryPlace = {
  id: number
  name: string
  address_text: string
  district: { id: number; name: string } | null
  category: { id: number; name: string; slug: string } | null
  tags: { id: number; name: string; slug: string }[]
  min_price: number | null
  max_price: number | null
  thumbnail: { image_url: string; alt_text: string } | null
  latitude: number
  longitude: number
  google_maps_url: string
  opening_hours: DiscoveryOpeningHour[]
}

export type DiscoveryFilters = {
  category_id?: number
  district_id?: number
  min_price?: number
  max_price?: number
  tag_ids?: number[]
  open_now?: boolean
  lat?: number
  lng?: number
  radius_km?: number
}

export type DiscoveryResult = DiscoveryPlace | null

export async function randomPlace(
  filters: DiscoveryFilters = {},
  excludedPlaceIds: number[] = [],
): Promise<DiscoveryResult> {
  const body: Record<string, unknown> = { ...filters }

  if (excludedPlaceIds.length > 0) {
    body.excluded_place_ids = excludedPlaceIds
  }

  const response = await apiRequest<DiscoveryResult>('/discovery/random', {
    method: 'POST',
    body,
  })

  return response.data
}