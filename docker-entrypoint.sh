#!/bin/sh
set -e

# Create .env from .env.example if it doesn't exist
if [ ! -f /var/www/.env ]; then
  echo ".env file not found — copying from .env.example"
  if [ -f /var/www/.env.example ]; then
    cp /var/www/.env.example /var/www/.env
  else
    echo "ERROR: .env.example not found. Cannot create .env file."
    exit 1
  fi
fi

# If APP_KEY isn't set, generate one
if ! grep -q "^APP_KEY=base64:" /var/www/.env 2>/dev/null; then
  echo "APP_KEY not set — generating key"
  php artisan key:generate --force
fi

# Ensure permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

exec "$@"
