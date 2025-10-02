#!/bin/bash

# Fix Laravel Permissions - One Shot Command
# Usage: curl -sSL https://raw.githubusercontent.com/dzikrirazzan/sigap_api/main/fix-permissions.sh | bash

set -e

echo "🔧 Fixing Laravel permissions and APP_KEY..."

cd /var/www/Emergency_API

# Fix all permissions in one go
echo "📁 Fixing file permissions..."
docker-compose exec -T --user root app chown -R www-data:www-data /var/www
docker-compose exec -T --user root app chmod 664 /var/www/.env
docker-compose exec -T --user root app chmod -R 775 /var/www/storage
docker-compose exec -T --user root app chmod -R 775 /var/www/bootstrap/cache

# Create and fix log file
echo "📝 Fixing log file..."
docker-compose exec -T --user root app rm -f /var/www/storage/logs/laravel.log
docker-compose exec -T --user root app touch /var/www/storage/logs/laravel.log
docker-compose exec -T --user root app chown www-data:www-data /var/www/storage/logs/laravel.log

# Generate APP_KEY and clear cache
echo "🔑 Generating APP_KEY..."
docker-compose exec -T app php artisan key:generate --force

echo "🗄️ Clearing cache..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear

# Test the fix
echo "🧪 Testing application..."
if curl -f http://localhost/ > /dev/null 2>&1; then
    echo "✅ Application is working!"
    echo "🌐 Access your app at: http://$(curl -s ifconfig.me)/"
    echo "🗃️ phpMyAdmin at: http://$(curl -s ifconfig.me):8080"
else
    echo "⚠️ Still having issues. Check logs with: docker-compose logs app"
fi

echo "🎉 Permission fix completed!"