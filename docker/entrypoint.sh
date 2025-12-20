#!/bin/bash
set -e

echo "=========================================="
echo "  Francis Manage - Docker Entrypoint"
echo "=========================================="

cd /var/www/html/build_source

# Wait for MySQL to be ready
if [ -n "$DB_HOST" ]; then
    echo "Waiting for MySQL to be ready..."
    max_tries=30
    counter=0
    until php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'Connected!'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
        counter=$((counter + 1))
        if [ $counter -gt $max_tries ]; then
            echo "Error: MySQL is not available after $max_tries attempts"
            exit 1
        fi
        echo "MySQL is unavailable - sleeping (attempt $counter/$max_tries)"
        sleep 2
    done
    echo "MySQL is ready!"
fi

# Install Composer dependencies if vendor directory doesn't exist or is empty
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor 2>/dev/null)" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "Composer dependencies already installed."
fi

# Install npm dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
    echo "Installing npm dependencies..."
    npm install
else
    echo "npm dependencies already installed."
fi

# Build frontend assets
echo "Building frontend assets..."
npm run build

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear caches (for development - avoid route serialization issues)
echo "Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Set correct permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/build_source/storage
chown -R www-data:www-data /var/www/html/build_source/bootstrap/cache
chmod -R 775 /var/www/html/build_source/storage
chmod -R 775 /var/www/html/build_source/bootstrap/cache

echo "=========================================="
echo "  Application is ready!"
echo "=========================================="

# Execute the command passed to docker run
exec "$@"
