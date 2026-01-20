.PHONY: up down restart backend frontend install test shell logs

# Start containers
up:
	docker-compose up -d

# Stop containers
down:
	docker-compose down

# Restart all containers
restart:
	docker-compose down && docker-compose up -d

# Refresh backend (clear cache + restart PHP)
backend:
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	docker-compose exec app php artisan l5-swagger:generate
	docker-compose restart app

# Refresh frontend (rebuild assets)
frontend:
	docker-compose run --rm node sh -c "npm run build"

# First time setup
install:
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate:fresh --seed

# Run tests
test:
	docker-compose exec app php artisan test

# Access shell
shell:
	docker-compose exec app bash

# View logs
logs:
	docker-compose logs -f
