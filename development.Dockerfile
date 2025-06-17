FROM dunglas/frankenphp AS frankenphp_runtime

ENV SERVER_NAME=:8080 \
    APP_ENV=local \
    APP_DEBUG=true \
    POST_MAX_SIZE=50M

WORKDIR /app
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && apt-get update && apt-get install -y supervisor \
    && mkdir -p /var/log/supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        pdo_sqlite \
        gd \
        intl \
        zip \
        xdebug

EXPOSE 8080

