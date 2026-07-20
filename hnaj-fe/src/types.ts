export type RecommendationRequest = {
  location: { lat: number; lng: number }
  district_id: string
  radius_km: number
  category_slug: string
  price_min: number
  price_max: number | null
  tags?: string[]
  limit?: 1 | 3
}

export type UserLocation = { lat: number; lng: number }

export type Tag = { id: string; name: string; slug: string; group: string; icon: string | null; sort_order: number; is_related?: boolean }
export type Category = { id: string; name: string; slug: string; sort_order: number }
export type District = { id: string; name: string; slug: string; center: UserLocation | null }

export type Place = {
  id: string
  name: string
  slug: string
  distance_km?: number
  price_display: string
  address: string
  rating: number
  matched_tags?: string[]
  cover_image: string | null
  category: { name: string; slug: string } | null
  district: { name: string } | null
  tags: Tag[]
  description?: string | null
}

export type RecommendationData = {
  places: Place[]
  meta: { message_key: string; fallback_applied: boolean; query_radius_km: number }
}

export type AuthUser = { id: string; name: string; email: string; roles: string[] }
export type ImportRow = { row_number: number; external_id: string | null; status: string; normalized_data: Record<string, unknown>; errors: string[] }
export type ImportBatchSummary = { id: string; filename: string; status: string; created_at?: string | null; total_rows: number; valid_rows: number; duplicate_rows: number; invalid_rows: number; processed_rows: number; imported_rows: number; skipped_rows: number; failed_rows: number; progress_percent: number; error_message?: string | null; confirmed_at?: string | null; started_at?: string | null; paused_at?: string | null; progress_updated_at?: string | null; completed_at?: string | null }
export type ImportBatchDetail = ImportBatchSummary & { rows: ImportRow[] }
export type ImportHistory = { current_page: number; data: ImportBatchSummary[]; last_page: number; per_page: number; total: number }