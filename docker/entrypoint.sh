#!/bin/sh
set -e

# Default values if environment variables are not set
: ${POST_MAX_SIZE:=128M}
: ${MEMORY_LIMIT:=128M}
: ${MAX_EXECUTION_TIME:=60}

# Export the variables so envsubst can use them
export POST_MAX_SIZE
export MEMORY_LIMIT

# Create custom PHP INI file with environment variable values
cat > /usr/local/etc/php/conf.d/custom.ini << EOF
; Custom PHP settings from environment variables
post_max_size = ${POST_MAX_SIZE}
upload_max_filesize = ${POST_MAX_SIZE}
memory_limit = ${MEMORY_LIMIT}
max_execution_time = ${MAX_EXECUTION_TIME}
expose_php = Off

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

# PUID/PGID Synchronization
# Environment variables PUID and PGID can be set to specify the desired UID/GID for www-data.
TARGET_UID="$PUID"
TARGET_GID="$PGID"

CURRENT_WWW_DATA_UID=$(id -u www-data)
CURRENT_WWW_DATA_GROUP_GID=$(getent group www-data | cut -d: -f3)

# Change GID of 'www-data' group if TARGET_GID is set and different
if [ -n "$TARGET_GID" ] && [ "$CURRENT_WWW_DATA_GROUP_GID" != "$TARGET_GID" ]; then
    echo "Changing GID of 'www-data' group from $CURRENT_WWW_DATA_GROUP_GID to $TARGET_GID"
    # Check if a group with TARGET_GID already exists and is not 'www-data'
    EXISTING_GROUP_WITH_TARGET_GID=$(getent group "$TARGET_GID" | cut -d: -f1)
    if [ -n "$EXISTING_GROUP_WITH_TARGET_GID" ] && [ "$EXISTING_GROUP_WITH_TARGET_GID" != "www-data" ]; then
        echo "Warning: GID $TARGET_GID is already in use by group '$EXISTING_GROUP_WITH_TARGET_GID'."
        echo "Attempting to change GID of 'www-data' group to $TARGET_GID (non-unique)."
        groupmod -o -g "$TARGET_GID" www-data
    else
        # GID is free, or already belongs to 'www-data' (but getent reported different - rare), or no group has this GID
        groupmod -g "$TARGET_GID" www-data
    fi
fi

# Change UID of 'www-data' user if TARGET_UID is set and different
if [ -n "$TARGET_UID" ] && [ "$CURRENT_WWW_DATA_UID" != "$TARGET_UID" ]; then
    echo "Changing UID of 'www-data' user from $CURRENT_WWW_DATA_UID to $TARGET_UID"
    usermod -o -u "$TARGET_UID" www-data
fi

# Run Laravel specific commands (migrations, cache, etc.)
# These are run as root; subsequent chown will fix permissions for www-data.
php artisan optimize
php artisan migrate --seed --force --no-interaction
php artisan migrate --database=queues-sqlite --path=database/migrations/queues --force --no-interaction

# Ensure key Laravel directories and database mount are writable by the (potentially new) www-data user/group.
echo "Ensuring www-data ownership and permissions for storage, bootstrap/cache, and database/mount..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache

if [ -d "/app/database/mount" ]; then
    chown -R www-data:www-data /app/database/mount
    chmod -R u+rwX,g+rwX /app/database/mount
fi

exec "$@"
