#!/bin/bash

# Fix deployment script for Emergency API
# Usage: curl -sSL https://raw.githubusercontent.com/dzikrirazzan/sigap_api/main/fix-deployment.sh | bash

set -e

echo "🔧 Fixing Emergency API Deployment Issues..."

# Check if docker-compose is running
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Containers not running. Run main deployment first!"
    exit 1
fi

echo "[INFO] Fixing git ownership..."
docker-compose exec -T --user root app git config --global --add safe.directory /var/www

echo "[INFO] Creating Laravel directories..."
docker-compose exec -T --user root app sh -c "
    mkdir -p /var/www/storage/logs /var/www/storage/app/public /var/www/storage/framework/{cache,sessions,testing,views} /var/www/bootstrap/cache
"

echo "[INFO] Fixing permissions..."
docker-compose exec -T --user root app sh -c "
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chmod 664 /var/www/storage/logs/laravel.log
"

echo "[INFO] Running composer install..."
docker-compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction

echo "[INFO] Running Laravel setup..."
docker-compose exec -T app php artisan key:generate --force
docker-compose exec -T app php artisan migrate --force
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan storage:link

echo "[INFO] Testing application..."
sleep 5
if curl -f http://localhost/ > /dev/null 2>&1; then
    echo "✅ Deployment fix successful!"
    echo "🌐 Your API is running at: http://$(hostname -I | awk '{print $1}')"
    echo "📊 phpMyAdmin: http://$(hostname -I | awk '{print $1}'):8080"
else
    echo "⚠️ Application still having issues. Check logs:"
    echo "docker-compose logs app"
fi