# Multi-stage Dockerfile for Laravel (PHP-FPM)
# - Uses official PHP-FPM image
# - Installs required PHP extensions (pgsql, gd, zip, etc.)
# - Runs Composer install

FROM php:8.2-fpm-alpine AS base

# Install system deps
RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    tzdata \
    bash \
    git \
    openssh-client \
    ca-certificates \
    postgresql-dev \
    openrc

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) gd pdo pdo_pgsql mbstring bcmath intl sockets zip exif

# Enable opcache
RUN docker-php-ext-enable opcache || true

# Install Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /var/www/html

# Copy composer files first and install dependencies (will leverage Docker cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress || true

# Copy the rest of the application
COPY . ./

# Finalize composer install and optimize autoloader
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
    && composer dump-autoload --optimize

# Set permissions for storage and bootstrap cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

# Expose php-fpm socket (used by nginx) or port
EXPOSE 9000

CMD ["php-fpm"]
