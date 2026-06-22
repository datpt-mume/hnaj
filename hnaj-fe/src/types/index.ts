// ============================================================
// HNaj Frontend — Type Definitions
// ============================================================

// --- API Request ---

export interface RecommendationRequest {
  location: {
    lat: number;
    lng: number;
  };
  radius_km: number;
  price_max: number;
  tags: string[];
  limit: 1 | 3;
}

// --- API Response Envelope ---

export interface APIResponse<T> {
  success: boolean;
  data: T;
  error?: APIError;
}

export interface APIError {
  code: string;
  message: string;
  details?: ValidationErrorDetail[];
}

export interface ValidationErrorDetail {
  field: string;
  message: string;
}

// --- Place Result ---

export interface PlaceResult {
  id: string;
  name: string;
  slug: string;
  cover_image: string | null;
  distance_km: number;
  price_min: number;
  price_max: number;
  price_display: string;
  tags: TagDTO[];
  matched_tags: string[];
  address: string | null;
  rating: number;
}

export interface TagDTO {
  id: string;
  name: string;
  slug: string;
}

// --- Result Meta ---

export interface ResultMeta {
  total_matched: number;
  fallback_applied: boolean;
  fallback_level: number;
  query_radius_km: number;
  relaxed_tags?: boolean;
  message: string;
}

// --- Recommendation Response ---

export interface RecommendationResponse {
  places: PlaceResult[];
  meta: ResultMeta;
}

// --- Tags ---

export interface TagsResponse {
  tags: TagDTO[];
}

// --- UI Tag (static data for the form) ---

export interface UITag {
  slug: string;
  name: string;
  emoji: string;
}
