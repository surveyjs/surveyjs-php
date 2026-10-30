# The app for docker-compose.yml: PHP's official CLI image with Composer, and Node to build the assets.

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY vite.config.js ./
COPY resources resources
COPY shared shared
RUN npm run build

FROM php:8.5-cli
# PHP 8.5.12 fixes CVE-2026-109618 and CVE-2026-109619 (remote code execution). A cached older
# php:8.5-cli stops the build here: rebuild with `docker compose build --pull`.
RUN php -r 'if (version_compare(PHP_VERSION, "8.5.12", "<")) { fwrite(STDERR, "PHP ".PHP_VERSION." is vulnerable: docker compose build --pull\n"); exit(1); }'
# pcntl: Workerman runs the relays with it on Linux. pdo_sqlite and fileinfo ship with the image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
COPY --from=assets /app/public/build public/build
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover

# Production settings: real environment variables win over the .env that CMD copies from .env.example.
# Logs go to `docker compose logs`. PHP_CLI_SERVER_WORKERS (read from the process environment, not .env)
# lets the built-in server answer other visitors while extract-from-paper (up to 120 s), server-pdf or ai-translation (60 s) waits.
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000 8081 8082
# Migrate and seed on start, then serve with upload limits for 5 MB files (see server.php).
# Laravel doesn't use PHP's session upload progress; switched off, it closes the CVE-2026-109619 path.
CMD ["sh", "-c", "[ -f .env ] || cp .env.example .env; grep -q '^APP_KEY=base64' .env || php artisan key:generate --force; touch \"${DB_DATABASE:-database/database.sqlite}\" && php artisan migrate --seed --force && exec php -d upload_max_filesize=10M -d post_max_size=12M -d session.upload_progress.enabled=0 -S 0.0.0.0:8000 -t public server.php"]
