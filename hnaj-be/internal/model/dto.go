package model

// --- Request DTOs ---

// RecommendationRequest represents the input payload for POST /api/v1/recommendations.
type RecommendationRequest struct {
	Location      LocationDTO `json:"location" validate:"required"`
	RadiusKm      float64     `json:"radius_km" validate:"required,min=0.5,max=20.0"`
	PriceMax      int         `json:"price_max" validate:"required,min=0"`
	Tags          []string    `json:"tags,omitempty"`
	Limit         int         `json:"limit,omitempty"`         // 1 or 3, default 3
	FallbackLevel int         `json:"fallback_level,omitempty"` // internal use
}

// LocationDTO represents GPS coordinates.
type LocationDTO struct {
	Lat float64 `json:"lat" validate:"required,latitude"`
	Lng float64 `json:"lng" validate:"required,longitude"`
}

// --- Response DTOs ---

// APIResponse is the standard response envelope.
type APIResponse struct {
	Success bool        `json:"success"`
	Data    interface{} `json:"data,omitempty"`
	Error   *APIError   `json:"error,omitempty"`
}

// APIError represents an error response.
type APIError struct {
	Code    string              `json:"code"`
	Message string              `json:"message"`
	Details []ValidationErrorDetail `json:"details,omitempty"`
}

// ValidationErrorDetail provides per-field error detail.
type ValidationErrorDetail struct {
	Field   string `json:"field"`
	Message string `json:"message"`
}

// RecommendationResponse wraps the recommendation result.
type RecommendationResponse struct {
	Places []PlaceResult `json:"places"`
	Meta   ResultMeta    `json:"meta"`
}

// PlaceResult is the output DTO for a recommended place.
type PlaceResult struct {
	ID           string    `json:"id"`
	Name         string    `json:"name"`
	Slug         string    `json:"slug"`
	CoverImage   string    `json:"cover_image,omitempty"`
	DistanceKm   float64   `json:"distance_km"`
	PriceMin     int       `json:"price_min"`
	PriceMax     int       `json:"price_max"`
	PriceDisplay string    `json:"price_display"`
	Tags         []TagDTO  `json:"tags"`
	MatchedTags  []string  `json:"matched_tags"`
	Address      string    `json:"address,omitempty"`
	Rating       float64   `json:"rating"`
}

// TagDTO is the output DTO for a tag.
type TagDTO struct {
	ID   string `json:"id"`
	Name string `json:"name"`
	Slug string `json:"slug"`
}

// ResultMeta provides query execution metadata.
type ResultMeta struct {
	TotalMatched    int    `json:"total_matched"`
	FallbackApplied bool   `json:"fallback_applied"`
	FallbackLevel   int    `json:"fallback_level"`
	QueryRadiusKm   float64 `json:"query_radius_km"`
	RelaxedTags     bool   `json:"relaxed_tags,omitempty"`
	Message         string `json:"message"`
}

// TagsResponse is used for GET /api/v1/tags.
type TagsResponse struct {
	Tags []TagDTO `json:"tags"`
}
