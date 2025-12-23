FROM php:8.4-cli

# Install system dependencies and build tools required for PECL extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libssl-dev \
        libicu-dev \
        zlib1g-dev \
        libcurl4-openssl-dev \
        build-essential \
        autoconf \
        pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install common PHP extensions used by Laravel
RUN docker-php-ext-install pdo pdo_mysql mbstring xml zip gd bcmath intl || true

# Install Swoole via PECL and enable it
RUN pecl channel-update pecl.php.net \
    && pecl install swoole \
    && docker-php-ext-enable swoole

# Install composer from the official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first for better cache usage
COPY composer.json composer.lock ./

# Install PHP dependencies (skip scripts so build doesn't run runtime commands)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts || true

# Copy application source
COPY . .

# Ensure storage and cache directories are writable
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

EXPOSE 8000

# Start Laravel Octane with Swoole
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=auto"]
