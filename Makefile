\
.PHONY: generate-key

generate-key:
	@echo "Generating Laravel application key..."
	@php artisan key:generate --show
	@echo "Key generated successfully."

