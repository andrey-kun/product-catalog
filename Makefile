.PHONY: help setup up down logs status clean migrate seed shell test test-unit test-integration test-feature test-coverage test-watch

help:
	@echo "Product Catalog API"
	@echo ""
	@echo "Available commands:"
	@echo " make setup     - Full project setup (first time)"
	@echo " make up        - Start all services"
	@echo " make down      - Stop all services"
	@echo " make logs      - View logs"
	@echo " make status    - Check container status"
	@echo " make migrate   - Run database migrations"
	@echo " make seed      - Seed database with test data"
	@echo " make shell     - Access PHP container"
	@echo " make test      - Run all tests"
	@echo " make test-unit - Run unit tests only"
	@echo " make test-integration - Run integration tests only"
	@echo " make test-feature - Run feature tests only"
	@echo " make test-coverage - Run tests with coverage report"
	@echo " make test-watch - Run tests in watch mode"
	@echo " make clean     - Clean up containers and volumes"
	@echo ""

setup: down clean
	@echo "Setting up Product Catalog API..."
	@docker compose -f compose.yaml build
	@docker compose -f compose.yaml up -d mysql
	@echo "Installing dependencies..."
	@docker compose -f compose.yaml run --rm cli composer install
	@echo "Waiting for MySQL to be ready..."
	@sleep 10
	@make migrate
	@make seed
	@docker compose -f compose.yaml up -d
	@echo "Setup complete. API available at http://localhost:8000"

up:
	@echo "Starting services..."
	@docker compose -f compose.yaml up -d
	@echo "Services started. API available at http://localhost:8000"

down:
	@echo "Stopping services..."
	@docker compose -f compose.yaml down

logs:
	@docker compose -f compose.yaml logs -f

status:
	@docker compose -f compose.yaml ps

migrate:
	@echo "Running migrations..."
	@docker compose -f compose.yaml run --rm cli composer migrate

seed:
	@echo "Seeding database..."
	@docker compose -f compose.yaml run --rm cli composer fixtures:load

shell:
	@docker compose -f compose.yaml exec app sh

test:
	@echo "Running all tests..."
	@docker compose -f compose.yaml run --rm cli vendor/bin/phpunit

test-unit:
	@echo "Running unit tests..."
	@docker compose -f compose.yaml run --rm cli vendor/bin/phpunit --testsuite=Unit

test-integration:
	@echo "Running integration tests..."
	@docker compose -f compose.yaml run --rm cli vendor/bin/phpunit --testsuite=Integration

test-feature:
	@echo "Running feature tests..."
	@docker compose -f compose.yaml run --rm cli vendor/bin/phpunit --testsuite=Feature

test-coverage:
	@echo "Running tests with coverage report..."
	@docker compose -f compose.yaml run --rm cli vendor/bin/phpunit --coverage-html coverage --coverage-text

test-watch:
	@echo "Running tests in watch mode..."
	@docker compose -f compose.yaml run --rm cli vendor/bin/phpunit --watch

clean:
	@echo "Cleaning up..."
	@docker compose -f compose.yaml down -v --remove-orphans
	@docker system prune -f
