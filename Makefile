.PHONY: up down build install migrate seed fresh test shell logs frontend frontend-clean rebuild clear restart swagger

# Start all containers
up:
	docker-compose up -d

# Stop all containers
down:
	docker-compose down

# Restart all containers
restart:
	docker-compose restart

# Build containers
build:
	docker-compose build --no-cache

# Install dependencies and setup
install:
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate
	docker-compose exec app php artisan db:seed

# Run migrations
migrate:
	docker-compose exec app php artisan migrate

# Run seeders
seed:
	docker-compose exec app php artisan db:seed

# Fresh migration with seed
fresh:
	docker-compose exec app php artisan migrate:fresh --seed

# Run tests
test:
	docker-compose exec app php artisan test

# Access app shell
shell:
	docker-compose exec app bash

# View logs
logs:
	docker-compose logs -f

# Quick frontend rebuild (no npm install)
frontend:
	docker-compose run --rm node sh -c "npm run build"

# Full frontend rebuild (clean install)
frontend-clean:
	rm -rf frontend/node_modules
	docker-compose rm -f node
	docker-compose up node

# Clear Laravel cache
clear:
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

# Generate Swagger documentation
swagger:
	docker-compose exec app php artisan l5-swagger:generate
