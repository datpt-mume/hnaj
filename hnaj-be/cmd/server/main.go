package main

import (
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	"github.com/go-chi/chi/v5"
	chimiddleware "github.com/go-chi/chi/v5/middleware"
	"github.com/go-chi/cors"
	"github.com/joho/godotenv"

	"github.com/hnaj/hnaj-be/internal/database"
	"github.com/hnaj/hnaj-be/internal/handler"
	"github.com/hnaj/hnaj-be/internal/repository"
	"github.com/hnaj/hnaj-be/internal/service"
)

func main() {
	// Load .env
	if err := godotenv.Load(); err != nil {
		log.Println("[WARN] .env file not found, using system environment variables")
	}

	// Connect to database
	if err := database.Connect(); err != nil {
		log.Fatalf("[FATAL] Database connection failed: %v", err)
	}
	defer database.Close()

	// Initialize dependencies
	placeRepo := repository.NewPlaceRepository()
	recommendationSvc := service.NewRecommendationService(placeRepo)
	recommendationHandler := handler.NewRecommendationHandler(recommendationSvc)

	// Setup router
	r := chi.NewRouter()

	// Middleware
	r.Use(chimiddleware.Logger)
	r.Use(chimiddleware.Recoverer)
	r.Use(chimiddleware.RequestID)
	r.Use(chimiddleware.RealIP)

	// CORS
	r.Use(cors.Handler(cors.Options{
		AllowedOrigins:   []string{os.Getenv("CORS_ALLOWED_ORIGINS")},
		AllowedMethods:   []string{"GET", "POST", "OPTIONS"},
		AllowedHeaders:   []string{"Content-Type", "Authorization"},
		AllowCredentials: true,
		MaxAge:           300,
	}))

	// Health check
	r.Get("/health", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		w.Write([]byte(`{"status":"ok"}`))
	})

	// API v1 routes
	r.Route("/api/v1", func(r chi.Router) {
		r.Post("/recommendations", recommendationHandler.GetRecommendations)
		r.Get("/tags", recommendationHandler.GetTags)
	})

	// Start server
	port := os.Getenv("SERVER_PORT")
	if port == "" {
		port = "8080"
	}

	// Graceful shutdown
	go func() {
		sigCh := make(chan os.Signal, 1)
		signal.Notify(sigCh, syscall.SIGINT, syscall.SIGTERM)
		<-sigCh
		log.Println("[SERVER] Shutting down gracefully...")
		database.Close()
		os.Exit(0)
	}()

	log.Printf("[SERVER] HNaj API starting on :%s", port)
	if err := http.ListenAndServe(":"+port, r); err != nil {
		log.Fatalf("[FATAL] Server failed: %v", err)
	}
}
