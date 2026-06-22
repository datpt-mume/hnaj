package model

import (
	"time"

	"github.com/google/uuid"
)

// Tag represents a context tag (ngữ cảnh).
type Tag struct {
	ID        uuid.UUID `json:"id"`
	Name      string    `json:"name"`
	Slug      string    `json:"slug"`
	Emoji     string    `json:"emoji,omitempty"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

// Place represents a venue/location.
type Place struct {
	ID          uuid.UUID `json:"id"`
	Name        string    `json:"name"`
	Slug        string    `json:"slug"`
	CoverImage  string    `json:"cover_image,omitempty"`
	Address     string    `json:"address,omitempty"`
	LocationLng float64   `json:"-"` // stored as GEOGRAPHY, extracted on query
	LocationLat float64   `json:"-"` // stored as GEOGRAPHY, extracted on query
	PriceMin    int       `json:"price_min"`
	PriceMax    int       `json:"price_max"`
	Rating      float64   `json:"rating"`
	IsActive    bool      `json:"-"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`

	// Computed fields (from query)
	DistanceKm float64 `json:"distance_km,omitempty"`
	Tags       []Tag   `json:"tags,omitempty"`
}

// PlaceTag is the many-to-many join.
type PlaceTag struct {
	PlaceID uuid.UUID `json:"place_id"`
	TagID   uuid.UUID `json:"tag_id"`
}
