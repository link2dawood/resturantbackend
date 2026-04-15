SHELL := /bin/bash

DC := docker compose
APP_SERVICE := apache
DB_SERVICE := mysql
REDIS_SERVICE := redis
EXEC := $(DC) exec $(APP_SERVICE)
EXEC_T := $(DC) exec -T $(APP_SERVICE)

.DEFAULT_GOAL := help

.PHONY: help
help: ## Show available commands
	@awk 'BEGIN {FS = ":.*##"; printf "\nRestaurant Backend commands:\n\n"} /^[a-zA-Z0-9_.-]+:.*##/ {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@printf "\n"

.PHONY: up
up: ## Start containers, wait for DB, and run app setup steps
	$(DC) up -d
	$(MAKE) wait-db
	$(MAKE) post-up

.PHONY: down
down: ## Stop containers
	$(DC) down

.PHONY: build
build: ## Build containers, wait for DB, and run app setup steps
	$(DC) up -d --build
	$(MAKE) wait-db
	$(MAKE) post-up

.PHONY: rebuild
rebuild: ## Rebuild containers from scratch and start them
	$(DC) down
	$(DC) up -d --build

.PHONY: restart
restart: ## Restart containers
	$(DC) restart

.PHONY: ps
ps: ## Show container status
	$(DC) ps

.PHONY: logs
logs: ## Tail app container logs
	$(DC) logs -f $(APP_SERVICE)

.PHONY: logs-all
logs-all: ## Tail all container logs
	$(DC) logs -f

.PHONY: logs-db
logs-db: ## Tail database logs
	$(DC) logs -f $(DB_SERVICE)

.PHONY: shell
shell: ## Open bash shell in app container
	$(EXEC) bash

.PHONY: sh
sh: ## Open sh shell in app container
	$(EXEC) sh

.PHONY: setup
setup: ## Install dependencies, generate key, migrate, and build assets
	$(MAKE) composer-install
	$(MAKE) npm-install
	$(MAKE) key-generate
	$(MAKE) migrate
	$(MAKE) npm-dev

.PHONY: wait-db
wait-db: ## Wait until MySQL is ready to accept connections
	@echo "Waiting for MySQL to be ready..."
	@until $(DC) exec -T $(DB_SERVICE) mysqladmin ping -h localhost -uroot -p$${DB_ROOT_PASSWORD:-rootpassword} --silent > /dev/null 2>&1; do \
		sleep 2; \
	done
	@echo "MySQL is ready."

.PHONY: post-up
post-up: ## Run safe post-start Laravel setup inside the app container
	@echo "Running post-up application setup..."
	$(EXEC_T) bash -lc 'if [ ! -d vendor ]; then composer install --no-interaction; else echo "Composer dependencies already installed"; fi'
	$(EXEC_T) php artisan key:generate --force
	$(EXEC_T) php artisan migrate --force
	$(EXEC_T) chmod -R 775 storage bootstrap/cache
	$(EXEC_T) chown -R www-data:www-data storage bootstrap/cache

.PHONY: composer-install
composer-install: ## Run composer install in app container
	$(EXEC_T) composer install --no-interaction

.PHONY: composer-update
composer-update: ## Run composer update in app container
	$(EXEC_T) composer update --no-interaction

.PHONY: composer-dump
composer-dump: ## Rebuild Composer autoload files
	$(EXEC_T) composer dump-autoload

.PHONY: npm-install
npm-install: ## Install Node dependencies in app container
	$(EXEC_T) npm install

.PHONY: npm-dev
npm-dev: ## Build frontend assets for development
	$(EXEC_T) npm run dev

.PHONY: npm-watch
npm-watch: ## Watch frontend assets for changes
	$(EXEC) npm run watch

.PHONY: npm-prod
npm-prod: ## Build production frontend assets
	$(EXEC_T) npm run prod

.PHONY: artisan
artisan: ## Run any artisan command, e.g. make artisan CMD="cache:clear"
	$(EXEC_T) php artisan $(CMD)

.PHONY: tinker
tinker: ## Open Laravel tinker
	$(EXEC) php artisan tinker

.PHONY: key-generate
key-generate: ## Generate Laravel application key
	$(EXEC_T) php artisan key:generate --force

.PHONY: migrate
migrate: ## Run database migrations
	$(EXEC_T) php artisan migrate --force

.PHONY: migrate-fresh
migrate-fresh: ## Fresh migrate database (drops all tables)
	$(EXEC_T) php artisan migrate:fresh --force

.PHONY: seed
seed: ## Seed the database
	$(EXEC_T) php artisan db:seed --force

.PHONY: fresh
fresh: ## Fresh migrate and seed the database
	$(EXEC_T) php artisan migrate:fresh --seed --force

.PHONY: test
test: ## Run Laravel test suite
	$(EXEC_T) php artisan test

.PHONY: test-filter
test-filter: ## Run filtered Laravel tests, e.g. make test-filter FILTER=ProfitLossCalculationTest
	$(EXEC_T) php artisan test --filter="$(FILTER)"

.PHONY: pint
pint: ## Run Laravel Pint formatter
	$(EXEC_T) ./vendor/bin/pint

.PHONY: cache-clear
cache-clear: ## Clear route, config, app, and view cache
	$(EXEC_T) php artisan route:clear
	$(EXEC_T) php artisan config:clear
	$(EXEC_T) php artisan cache:clear
	$(EXEC_T) php artisan view:clear

.PHONY: optimize-clear
optimize-clear: ## Clear all Laravel optimized caches
	$(EXEC_T) php artisan optimize:clear

.PHONY: queue-restart
queue-restart: ## Restart Laravel queue workers
	$(EXEC_T) php artisan queue:restart

.PHONY: permissions
permissions: ## Fix storage and cache permissions inside app container
	$(EXEC_T) chmod -R 775 storage bootstrap/cache
	$(EXEC_T) chown -R www-data:www-data storage bootstrap/cache
