\
.PHONY: generate-key
		create-user
		cleanup-file-entities
		dev/composer-install
		dev/migrate
		dev/generate-key
		dev/queue-listen
		dev/create-user

generate-key:
	@echo "Generating Laravel application key... Store it securely and update your docker-compose environment variable (APP_KEY)"
	@php artisan key:generate --show

cleanup-file-entities:
	@php artisan app:janitor:file-entity --now

create-user:
	@php artisan app:user:create

dev/composer-install:
	docker compose -f docker-compose.development.yml exec files-nest composer install

dev/migrate:
	docker compose -f docker-compose.development.yml exec files-nest php artisan migrate --seed
    docker compose -f docker-compose.development.yml exec files-nest php artisan migrate --database=queues-sqlite --path=database/migrations/queues

dev/generate-key:
	docker compose -f docker-compose.development.yml exec files-nest php artisan key:generate

dev/queue-listen:
	docker compose -f docker-compose.development.yml exec files-nest php artisan queue:listen

dev/create-user:
	docker compose -f docker-compose.development.yml exec files-nest php artisan app:user:create
