#!/bin/sh
set -e

# If APP_KEY isn't set, generate one (will be baked into image if absent)
if [ -z "${APP_KEY}" ]; then
  echo "APP_KEY not set — generating key"
  php artisan key:generate --force
fi

# Ensure permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

exec "$@"
