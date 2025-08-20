# ---- Stage 1: install PHP deps without dev ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-ansi --no-progress --prefer-dist --no-dev

# ---- Stage 2: runtime with PHP 8.2 + GD (jpeg/webp) ----
FROM php:8.2-cli-alpine

# OS libs for gd (jpeg/png/webp) + opcache
RUN apk add --no-cache \
      libjpeg-turbo-dev libpng-dev freetype-dev libwebp-dev icu-libs \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j$(nproc) gd opcache

WORKDIR /app

# Copy app code and vendor
COPY . .
COPY --from=vendor /app/vendor ./vendor

# Defaults (override at runtime)
ENV APP_ENV=production \
    PORT=8080

EXPOSE 8080

# Serve Slim from /public for demos
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
