FROM php:8.4-cli

# Install system dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        zip \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip gd bcmath

# Install composer
RUN php -r "copy('https://getcomposer.org/installer','/tmp/composer-setup.php');" \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php

WORKDIR /var/www

# Copy composer files first to install dependencies (leverages layer cache)
COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts || true

# Copy application files
COPY . .

# Ensure storage and cache are writable
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

EXPOSE 8000

# Serve the application with artisan for simple platform builds
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
