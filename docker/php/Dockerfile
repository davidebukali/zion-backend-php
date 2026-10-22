# ==========================================
# Base Image: PHP 8.4.27 FPM with Extensions (Patched for CVE-2026-109618 & CVE-2026-109619)
# ==========================================
FROM php:8.4.27-fpm-alpine AS base

WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    postgresql-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        bcmath \
        pcntl \
        intl \
        zip \
        opcache

# Install Redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ==========================================
# Development Target
# ==========================================
FROM base AS development

ARG UID=1000
ARG GID=1000

# Setup matching user/group to avoid permission friction on macOS/Linux
RUN if ! getent group ${GID} >/dev/null; then \
        addgroup -g ${GID} devuser; \
    fi && \
    if ! getent passwd ${UID} >/dev/null; then \
        adduser -D -u ${UID} -G $(getent group ${GID} | cut -d: -f1) -s /bin/sh devuser; \
    fi

COPY docker/php/local.ini $PHP_INI_DIR/conf.d/local.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ==========================================
# Production Assets Builder
# ==========================================
FROM node:22-alpine AS production-assets

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# ==========================================
# Production Target
# ==========================================
FROM base AS production

# Copy configuration
COPY docker/php/local.ini $PHP_INI_DIR/conf.d/local.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy application source
COPY . /var/www

# Copy compiled assets from node stage
COPY --from=production-assets /app/public/build /var/www/public/build

# Install dependencies (production only)
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ==========================================
# Web Server Target (Nginx)
# ==========================================
FROM nginx:alpine AS web

# Copy production Nginx server configuration
COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf

# Copy public assets and compiled Vite frontend build
COPY public /var/www/public
COPY --from=production-assets /app/public/build /var/www/public/build

# Ensure required runtime directories exist and have proper ownership
RUN mkdir -p /var/cache/nginx /var/run /tmp \
    && chown -R nginx:nginx /var/cache/nginx /var/run /var/www/public

EXPOSE 8080

CMD ["nginx", "-g", "daemon off;"]
