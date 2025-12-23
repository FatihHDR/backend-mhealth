FROM ghcr.io/frankenphp/frankenphp:php-8.4-apache

# Install system dependencies and common PHP extensions used by Laravel/Octane
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip gd bcmath intl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install composer dependencies (skip scripts to avoid Octane runtime steps here)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts || true

# Copy application
COPY . .

# Ensure storage/cache writable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

EXPOSE 80

# Use the base image's default entrypoint (FrankenPHP with Apache). The image
# automatically serves the document root; ensure Laravel's public folder is used
# by the platform or map the container's document root to /var/www/html/public.