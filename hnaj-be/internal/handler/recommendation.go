package handler

import (
	"encoding/json"
	"log"
	"net/http"

	"github.com/hnaj/hnaj-be/internal/model"
	"github.com/hnaj/hnaj-be/internal/service"
)

// RecommendationHandler handles HTTP requests for recommendations.
type RecommendationHandler struct {
	svc *service.RecommendationService
}

// NewRecommendationHandler creates a new handler.
func NewRecommendationHandler(svc *service.RecommendationService) *RecommendationHandler {
	return &RecommendationHandler{svc: svc}
}

// GetRecommendations handles POST /api/v1/recommendations.
func (h *RecommendationHandler) GetRecommendations(w http.ResponseWriter, r *http.Request) {
	var req model.RecommendationRequest

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "INVALID_JSON", "Invalid request body.")
		return
	}

	// Validate
	if errors := validateRequest(req); len(errors) > 0 {
		writeValidationError(w, errors)
		return
	}

	// Call service
	result, err := h.svc.GetRecommendations(r.Context(), req)
	if err != nil {
		log.Printf("[ERROR] GetRecommendations: %v", err)
		writeError(w, http.StatusInternalServerError, "INTERNAL_ERROR", "Đã xảy ra lỗi. Vui lòng thử lại sau.")
		return
	}

	writeJSON(w, http.StatusOK, model.APIResponse{
		Success: true,
		Data:    result,
	})
}

// GetTags handles GET /api/v1/tags.
func (h *RecommendationHandler) GetTags(w http.ResponseWriter, r *http.Request) {
	tags, err := h.svc.GetAvailableTags(r.Context())
	if err != nil {
		log.Printf("[ERROR] GetTags: %v", err)
		writeError(w, http.StatusInternalServerError, "INTERNAL_ERROR", "Không thể lấy danh sách tags.")
		return
	}

	writeJSON(w, http.StatusOK, model.APIResponse{
		Success: true,
		Data:    model.TagsResponse{Tags: tags},
	})
}

// --- Helpers ---

func writeJSON(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(data)
}

func writeError(w http.ResponseWriter, status int, code, message string) {
	writeJSON(w, status, model.APIResponse{
		Success: false,
		Error: &model.APIError{
			Code:    code,
			Message: message,
		},
	})
}

func writeValidationError(w http.ResponseWriter, details []model.ValidationErrorDetail) {
	writeJSON(w, http.StatusUnprocessableEntity, model.APIResponse{
		Success: false,
		Error: &model.APIError{
			Code:    "VALIDATION_ERROR",
			Message: "Invalid request parameters.",
			Details: details,
		},
	})
}

func validateRequest(req model.RecommendationRequest) []model.ValidationErrorDetail {
	var errs []model.ValidationErrorDetail

	if req.Location.Lat < -90 || req.Location.Lat > 90 {
		errs = append(errs, model.ValidationErrorDetail{
			Field: "location.lat", Message: "Vĩ độ không hợp lệ (phải từ -90 đến 90).",
		})
	}
	if req.Location.Lng < -180 || req.Location.Lng > 180 {
		errs = append(errs, model.ValidationErrorDetail{
			Field: "location.lng", Message: "Kinh độ không hợp lệ (phải từ -180 đến 180).",
		})
	}
	if req.RadiusKm < 0.5 || req.RadiusKm > 20.0 {
		errs = append(errs, model.ValidationErrorDetail{
			Field: "radius_km", Message: "Bán kính phải trong khoảng 0.5–20.0 km.",
		})
	}
	if req.PriceMax < 0 {
		errs = append(errs, model.ValidationErrorDetail{
			Field: "price_max", Message: "Ngân sách không được âm.",
		})
	}
	if req.Limit != 0 && req.Limit != 1 && req.Limit != 3 {
		errs = append(errs, model.ValidationErrorDetail{
			Field: "limit", Message: "Số lượng kết quả phải là 1 hoặc 3.",
		})
	}

	return errs
}
