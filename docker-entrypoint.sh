#!/bin/sh
set -e

# Check if we're in production mode (DigitalOcean injects env vars, don't create .env file)
# If API_SECRET_KEY env var exists, we're in production - use env vars directly
if [ -n "$API_SECRET_KEY" ]; then
  echo "Production mode detected (API_SECRET_KEY env var exists)"
  echo "Using environment variables directly - not creating .env file"
  
  # Remove .env if it exists to force Laravel to use environment variables
  if [ -f /var/www/.env ]; then
    rm /var/www/.env
    echo "Removed .env file to use environment variables"
  fi
else
  # Development mode - create .env from .env.example
  if [ ! -f /var/www/.env ]; then
    echo ".env file not found — copying from .env.example"
    if [ -f /var/www/.env.example ]; then
      cp /var/www/.env.example /var/www/.env
    else
      echo "ERROR: .env.example not found. Cannot create .env file."
      exit 1
    fi
  fi
  
  # If APP_KEY isn't set in .env file, generate one
  if ! grep -q "^APP_KEY=base64:" /var/www/.env 2>/dev/null; then
    echo "APP_KEY not set — generating key"
    php artisan key:generate --force
  fi
fi

# Clear config cache to ensure environment variables are loaded fresh
echo "Clearing config cache to load environment variables..."
php artisan config:clear || true

# Ensure permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

exec "$@"
