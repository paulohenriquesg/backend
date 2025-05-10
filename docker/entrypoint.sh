#!/bin/sh
set -e

# Default values if environment variables are not set
: ${POST_MAX_SIZE:=128M}
: ${MEMORY_LIMIT:=128M}

# Export the variables so envsubst can use them
export POST_MAX_SIZE
export MEMORY_LIMIT

# Create custom PHP INI file with environment variable values
cat > /usr/local/etc/php/conf.d/custom.ini << EOF
; Custom PHP settings from environment variables
post_max_size = ${POST_MAX_SIZE}
upload_max_filesize = ${POST_MAX_SIZE}
memory_limit = ${MEMORY_LIMIT}
max_execution_time = 30

; OPcache settings
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
EOF

php artisan optimize
php artisan migrate --seed --force --no-interaction
php artisan migrate --database=queues-sqlite --path=database/migrations/queues --force --no-interaction

# Ensure the database files are writable by www-data
if [ -d "/app/database/mount" ]; then
    # Chown the directory and its contents to www-data
    chown -R www-data:www-data /app/database/mount
    # Ensure www-data has rwx on the directory and rw on files
    chmod -R u+rwX,g+rwX /app/database/mount
fi

exec "$@"
