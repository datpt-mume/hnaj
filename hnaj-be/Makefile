# HNaj Backend - Makefile

.PHONY: run build migrate-up migrate-down test

# Run development server
run:
	go run ./cmd/server/

# Build binary
build:
	go build -o bin/hnaj-server ./cmd/server/

# Run database migrations (up)
migrate-up:
	psql -h $(DB_HOST) -p $(DB_PORT) -U $(DB_USER) -d $(DB_NAME) -f migrations/001_init.sql

# Seed sample data
seed:
	go run ./cmd/seed/

# Run tests
test:
	go test ./... -v -cover

# Lint
lint:
	golangci-lint run ./...

# Generate mocks
mock:
	mockgen -source=internal/repository/place_repo.go -destination=internal/repository/mocks/place_repo_mock.go
