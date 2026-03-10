# Docker environment commands for restaurant backend
# Usage: make <target>

# Default: show help
.PHONY: help
help:
	@echo "Docker environment commands:"
	@echo "  make up          - Start containers (apache, mysql, redis)"
	@echo "  make down        - Stop containers"
	@echo "  make build       - Build and start containers"
	@echo "  make shell       - Open shell in app container"
	@echo "  make migrate     - Run database migrations"
	@echo "  make migrate-fresh - Fresh migrate (drops all tables)"
	@echo "  make composer-install  - composer install"
	@echo "  make composer-update   - composer update"
	@echo "  make artisan     - Run artisan (e.g. make artisan CMD='cache:clear')"
	@echo "  make logs        - Tail apache container logs"

# Start containers
.PHONY: up
up:
	docker compose up -d

# Stop containers
.PHONY: down
down:
	docker compose down

# Build and start
.PHONY: build
build:
	docker compose up -d --build

# Shell into app container
.PHONY: shell
shell:
	docker compose exec apache bash

# Run migrations
.PHONY: migrate
migrate:
	docker compose exec apache php artisan migrate --force

# Fresh migrations (drops all tables)
.PHONY: migrate-fresh
migrate-fresh:
	docker compose exec apache php artisan migrate:fresh --force

# Composer install
.PHONY: composer-install
composer-install:
	docker compose exec apache composer install --no-interaction

# Composer update (e.g. for maatwebsite/excel)
.PHONY: composer-update
composer-update:
	docker compose exec apache composer update --no-interaction

# Run any artisan command: make artisan CMD="migrate"
.PHONY: artisan
artisan:
	docker compose exec apache php artisan $(CMD)

# View logs
.PHONY: logs
logs:
	docker compose logs -f apache

# Clear route/config cache (fix "Route not defined" after adding routes)
.PHONY: clear-cache
clear-cache:
	docker compose exec apache php artisan route:clear
	docker compose exec apache php artisan config:clear
	docker compose exec apache php artisan cache:clear
