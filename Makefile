# Convenience commands. Run `make help` to list them.
# Database commands that run Laravel need backend/ to exist (Sprint 0 walking skeleton).
.DEFAULT_GOAL := help

db-up: ## Start local PostgreSQL
	docker compose up -d db

db-down: ## Stop local PostgreSQL (data kept)
	docker compose down

db-migrate: ## Apply pending Laravel migrations
	cd backend && php artisan migrate

db-status: ## Show which migrations have run
	cd backend && php artisan migrate:status

db-seed: ## Load fake seed data (dev only, never real data)
	cd backend && php artisan db:seed

db-reset: ## DESTROY the local database and rebuild it (migrate + seed)
	docker compose down -v
	docker compose up -d db
	@echo "Waiting for PostgreSQL..."
	@until docker compose exec -T db pg_isready -q; do sleep 1; done
	cd backend && php artisan migrate --seed

setup: db-up ## First-time database setup
	@until docker compose exec -T db pg_isready -q; do sleep 1; done
	cd backend && php artisan migrate --seed

help: ## List commands
	@grep -E '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-14s %s\n", $$1, $$2}'

.PHONY: db-up db-down db-migrate db-status db-seed db-reset setup help
