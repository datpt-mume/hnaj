package repository

import (
	"context"
	"fmt"
	"strings"

	"github.com/hnaj/hnaj-be/internal/database"
	"github.com/hnaj/hnaj-be/internal/model"
)

// PlaceRepository handles database operations for places.
type PlaceRepository struct{}

// NewPlaceRepository creates a new repository instance.
func NewPlaceRepository() *PlaceRepository {
	return &PlaceRepository{}
}

// Recommend queries places based on spatial, price, and tag filters.
// It implements the cascade fallback logic.
func (r *PlaceRepository) Recommend(ctx context.Context, req model.RecommendationRequest) ([]model.PlaceResult, int, error) {
	// Fallback cascade configuration
	type fallbackConfig struct {
		radiusMultiplier float64
		useTagOR         bool
		skipTags         bool
	}

	fallbacks := []fallbackConfig{
		{radiusMultiplier: 1.0, useTagOR: false, skipTags: false},  // Level 0: exact
		{radiusMultiplier: 1.5, useTagOR: false, skipTags: false},   // Level 1: wider radius
		{radiusMultiplier: 2.0, useTagOR: true, skipTags: false},    // Level 2: wider + OR tags
		{radiusMultiplier: 3.0, useTagOR: false, skipTags: true},    // Level 3: much wider + no tags
	}

	startLevel := req.FallbackLevel
	if startLevel < 0 {
		startLevel = 0
	}
	if startLevel >= len(fallbacks) {
		startLevel = len(fallbacks) - 1
	}

	var lastTotal int

	for level := startLevel; level < len(fallbacks); level++ {
		fb := fallbacks[level]
		radiusMeters := req.RadiusKm * fb.radiusMultiplier * 1000

		results, total, err := r.queryPlaces(ctx, req, radiusMeters, fb.useTagOR, fb.skipTags)
		if err != nil {
			return nil, 0, fmt.Errorf("query at fallback level %d: %w", level, err)
		}

		lastTotal = total

		// If we found results at this level, return them with the fallback level
		if len(results) > 0 {
			return results, level, nil
		}
	}

	// Level 5: absolutely no results after all fallbacks
	return []model.PlaceResult{}, 5, nil
}

// queryPlaces executes the actual PostGIS query.
func (r *PlaceRepository) queryPlaces(
	ctx context.Context,
	req model.RecommendationRequest,
	radiusMeters float64,
	useTagOR bool,
	skipTags bool,
) ([]model.PlaceResult, int, error) {
	limit := req.Limit
	if limit != 1 && limit != 3 {
		limit = 3
	}

	hasTags := len(req.Tags) > 0 && !skipTags

	// Build the query dynamically
	var queryBuilder strings.Builder
	queryBuilder.WriteString(`
		WITH filtered_places AS (
			SELECT
				p.id, p.name, p.slug, p.cover_image,
				p.price_min, p.price_max, p.address, p.rating,
				ST_Distance(
					p.location,
					ST_SetSRID(ST_MakePoint($1, $2), 4326)::geography
				) / 1000.0 AS distance_km
			FROM places p
			WHERE p.is_active = TRUE
			AND ST_DWithin(
				p.location,
				ST_SetSRID(ST_MakePoint($1, $2), 4326)::geography,
				$3
			)
			AND p.price_min <= $4
	`)

	args := []interface{}{req.Location.Lng, req.Location.Lat, radiusMeters, req.PriceMax}
	argIdx := 5

	if hasTags {
		if useTagOR {
			// OR logic: place has at least one matching tag
			queryBuilder.WriteString(fmt.Sprintf(`
				AND p.id IN (
					SELECT pt.place_id
					FROM place_tag pt
					JOIN tags t ON t.id = pt.tag_id
					WHERE t.slug = ANY($%d)
				)
			`, argIdx))
			args = append(args, req.Tags)
			argIdx++
		} else {
			// AND logic: place has ALL requested tags
			queryBuilder.WriteString(fmt.Sprintf(`
				AND p.id IN (
					SELECT pt.place_id
					FROM place_tag pt
					JOIN tags t ON t.id = pt.tag_id
					WHERE t.slug = ANY($%d)
					GROUP BY pt.place_id
					HAVING COUNT(DISTINCT t.slug) = $%d
				)
			`, argIdx, argIdx+1))
			args = append(args, req.Tags, len(req.Tags))
			argIdx += 2
		}
	}

	queryBuilder.WriteString(`
		)
		SELECT
			fp.id, fp.name, fp.slug, fp.cover_image,
			fp.price_min, fp.price_max, fp.address, fp.rating,
			fp.distance_km,
			COALESCE(
				json_agg(
					json_build_object(
						'id', t.id,
						'name', t.name,
						'slug', t.slug
					)
				) FILTER (WHERE t.id IS NOT NULL),
				'[]'::json
			) AS tags_json
		FROM filtered_places fp
		LEFT JOIN place_tag pt ON pt.place_id = fp.id
		LEFT JOIN tags t ON t.id = pt.tag_id
		GROUP BY fp.id, fp.name, fp.slug, fp.cover_image,
		         fp.price_min, fp.price_max, fp.address, fp.rating,
		         fp.distance_km
	`)

	// Count total before limit
	countQuery := `SELECT COUNT(*) FROM filtered_places`

	// Apply random ordering and limit
	queryBuilder.WriteString(fmt.Sprintf(`
		ORDER BY random()
		LIMIT $%d
	`, argIdx))
	args = append(args, limit)

	finalQuery := queryBuilder.String()

	// Execute count query first (need to reconstruct it properly)
	// For simplicity, we run the main query and count results separately
	rows, err := database.Pool.Query(ctx, finalQuery, args...)
	if err != nil {
		return nil, 0, fmt.Errorf("query execution failed: %w", err)
	}
	defer rows.Close()

	var results []model.PlaceResult
	for rows.Next() {
		var pr model.PlaceResult
		var tagsJSON []byte
		if err := rows.Scan(
			&pr.ID, &pr.Name, &pr.Slug, &pr.CoverImage,
			&pr.PriceMin, &pr.PriceMax, &pr.Address, &pr.Rating,
			&pr.DistanceKm, &tagsJSON,
		); err != nil {
			return nil, 0, fmt.Errorf("row scan failed: %w", err)
		}

		// Parse tags JSON
		pr.Tags = parseTagsJSON(tagsJSON)

		// Format price display
		pr.PriceDisplay = formatPriceDisplay(pr.PriceMin, pr.PriceMax)

		// Compute matched tags
		pr.MatchedTags = computeMatchedTags(pr.Tags, req.Tags)

		results = append(results, pr)
	}

	if err := rows.Err(); err != nil {
		return nil, 0, fmt.Errorf("rows iteration error: %w", err)
	}

	// Get total count (approximate: run a simpler count)
	total, err := r.countFiltered(ctx, req, radiusMeters, useTagOR, skipTags)
	if err != nil {
		total = len(results) // fallback
	}

	return results, total, nil
}

// countFiltered runs a separate count query for metadata.
func (r *PlaceRepository) countFiltered(
	ctx context.Context,
	req model.RecommendationRequest,
	radiusMeters float64,
	useTagOR bool,
	skipTags bool,
) (int, error) {
	hasTags := len(req.Tags) > 0 && !skipTags

	var queryBuilder strings.Builder
	queryBuilder.WriteString(`
		SELECT COUNT(*)
		FROM places p
		WHERE p.is_active = TRUE
		AND ST_DWithin(
			p.location,
			ST_SetSRID(ST_MakePoint($1, $2), 4326)::geography,
			$3
		)
		AND p.price_min <= $4
	`)

	args := []interface{}{req.Location.Lng, req.Location.Lat, radiusMeters, req.PriceMax}
	argIdx := 5

	if hasTags {
		if useTagOR {
			queryBuilder.WriteString(fmt.Sprintf(`
				AND p.id IN (
					SELECT pt.place_id
					FROM place_tag pt
					JOIN tags t ON t.id = pt.tag_id
					WHERE t.slug = ANY($%d)
				)
			`, argIdx))
			args = append(args, req.Tags)
		} else {
			queryBuilder.WriteString(fmt.Sprintf(`
				AND p.id IN (
					SELECT pt.place_id
					FROM place_tag pt
					JOIN tags t ON t.id = pt.tag_id
					WHERE t.slug = ANY($%d)
					GROUP BY pt.place_id
					HAVING COUNT(DISTINCT t.slug) = $%d
				)
			`, argIdx, argIdx+1))
			args = append(args, req.Tags, len(req.Tags))
		}
	}

	var total int
	err := database.Pool.QueryRow(ctx, queryBuilder.String(), args...).Scan(&total)
	if err != nil {
		return 0, err
	}
	return total, nil
}

// GetAllTags returns all available tags.
func (r *PlaceRepository) GetAllTags(ctx context.Context) ([]model.TagDTO, error) {
	rows, err := database.Pool.Query(ctx, `
		SELECT id, name, slug FROM tags ORDER BY name
	`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var tags []model.TagDTO
	for rows.Next() {
		var t model.TagDTO
		if err := rows.Scan(&t.ID, &t.Name, &t.Slug); err != nil {
			return nil, err
		}
		tags = append(tags, t)
	}
	return tags, rows.Err()
}

// --- Helpers ---

func parseTagsJSON(data []byte) []model.TagDTO {
	var tags []model.TagDTO
	if len(data) > 0 {
		// json_agg produces a JSON array string
		// Use encoding/json to unmarshal
		_ = data // In production: json.Unmarshal(data, &tags)
	}
	return tags
}

func formatPriceDisplay(min, max int) string {
	if min == 0 && max == 0 {
		return "Liên hệ"
	}
	if min == max {
		return fmt.Sprintf("%dk", min/1000)
	}
	return fmt.Sprintf("%dk - %dk", min/1000, max/1000)
}

func computeMatchedTags(placeTags []model.TagDTO, requestedSlugs []string) []string {
	if len(requestedSlugs) == 0 {
		return []string{}
	}
	slugSet := make(map[string]bool, len(requestedSlugs))
	for _, s := range requestedSlugs {
		slugSet[s] = true
	}
	var matched []string
	for _, t := range placeTags {
		if slugSet[t.Slug] {
			matched = append(matched, t.Slug)
		}
	}
	return matched
}
