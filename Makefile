\
.PHONY: generate-key
		cleanup-file-entities

generate-key:
	@echo "Generating Laravel application key..."
	@php artisan key:generate --show
	@echo "Key generated successfully."

cleanup-file-entities:
	@echo "Running file entity janitor with --now flag..."
	@php artisan app:janitor:file-entity --now
	@echo "File cleanup completed."
