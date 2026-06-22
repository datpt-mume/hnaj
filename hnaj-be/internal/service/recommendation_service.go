package service

import (
	"context"
	"fmt"

	"github.com/hnaj/hnaj-be/internal/model"
	"github.com/hnaj/hnaj-be/internal/repository"
)

// RecommendationService contains business logic for recommendations.
type RecommendationService struct {
	repo *repository.PlaceRepository
}

// NewRecommendationService creates a new service.
func NewRecommendationService(repo *repository.PlaceRepository) *RecommendationService {
	return &RecommendationService{repo: repo}
}

// GetRecommendations processes a recommendation request with full fallback cascade.
func (s *RecommendationService) GetRecommendations(ctx context.Context, req model.RecommendationRequest) (*model.RecommendationResponse, error) {
	// Normalize limit
	if req.Limit != 1 && req.Limit != 3 {
		req.Limit = 3
	}

	// Execute query with cascade fallback
	results, fallbackLevel, err := s.repo.Recommend(ctx, req)
	if err != nil {
		return nil, fmt.Errorf("recommendation query failed: %w", err)
	}

	// Build meta
	meta := s.buildMeta(results, fallbackLevel, req)

	return &model.RecommendationResponse{
		Places: results,
		Meta:   meta,
	}, nil
}

// GetAvailableTags returns all tags for the frontend.
func (s *RecommendationService) GetAvailableTags(ctx context.Context) ([]model.TagDTO, error) {
	return s.repo.GetAllTags(ctx)
}

// buildMeta constructs the metadata for the response.
func (s *RecommendationService) buildMeta(
	results []model.PlaceResult,
	fallbackLevel int,
	req model.RecommendationRequest,
) model.ResultMeta {
	// Determine effective radius
	radiusMultipliers := []float64{1.0, 1.5, 2.0, 3.0}
	effectiveRadius := req.RadiusKm
	if fallbackLevel > 0 && fallbackLevel < len(radiusMultipliers) {
		effectiveRadius = req.RadiusKm * radiusMultipliers[fallbackLevel]
	} else if fallbackLevel >= len(radiusMultipliers) {
		effectiveRadius = req.RadiusKm * radiusMultipliers[len(radiusMultipliers)-1]
	}

	fallbackApplied := fallbackLevel > 0
	relaxedTags := fallbackLevel >= 2
	totalMatched := len(results)

	var message string
	switch fallbackLevel {
	case 0:
		message = fmt.Sprintf("Tìm thấy %d địa điểm, hiển thị gợi ý ngẫu nhiên.", totalMatched)
	case 1:
		message = fmt.Sprintf("Đã mở rộng phạm vi tìm kiếm lên %.1fkm.", effectiveRadius)
	case 2:
		message = fmt.Sprintf("Đã mở rộng phạm vi lên %.1fkm và tìm kiếm linh hoạt hơn.", effectiveRadius)
	case 3:
		message = "Đang hiển thị các địa điểm gần bạn nhất."
	case 5:
		message = "Không tìm thấy địa điểm phù hợp. Hãy thử khu vực khác nhé!"
	default:
		message = "OK"
	}

	return model.ResultMeta{
		TotalMatched:    totalMatched,
		FallbackApplied: fallbackApplied,
		FallbackLevel:   fallbackLevel,
		QueryRadiusKm:   effectiveRadius,
		RelaxedTags:     relaxedTags,
		Message:         message,
	}
}
