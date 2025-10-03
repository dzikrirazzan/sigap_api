#!/bin/bash

# Complete Laravel Setup - Manual Completion Script
# Use this if deploy-backend.sh hangs or fails
# Usage: curl -sSL https://raw.githubusercontent.com/dzikrirazzan/sigap_api/main/complete-setup.sh | bash

set -e

echo "🔧 Completing Laravel setup manually..."

cd /var/www/Emergency_API

# Check container status
echo "📊 Checking container status..."
docker-compose ps

# Fix permissions
echo "🔐 Fixing permissions..."
timeout 30 docker-compose exec -T --user root app sh -c "
    chown -R www-data:www-data /var/www && \
    chmod 664 /var/www/.env && \
    chmod -R 775 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    touch /var/www/storage/logs/laravel.log && \
    chown www-data:www-data /var/www/storage/logs/laravel.log
" || echo "⚠️ Permission fix may have failed"

# Generate APP_KEY
echo "🔑 Generating APP_KEY..."
timeout 30 docker-compose exec -T app php artisan key:generate --force || echo "⚠️ Key generation may have failed"

# Run migrations
echo "🗃️ Running migrations..."
timeout 60 docker-compose exec -T app php artisan migrate --force || echo "⚠️ Migration may have failed"

# Clear cache
echo "🧹 Clearing cache..."
timeout 30 docker-compose exec -T app php artisan config:clear || true
timeout 30 docker-compose exec -T app php artisan cache:clear || true

# Test application
echo "🧪 Testing application..."
if timeout 10 curl -f http://localhost/ > /dev/null 2>&1; then
    echo "✅ Application is working!"
    echo "🌐 Access your app at: http://$(curl -s ifconfig.me)/"
    echo "🗃️ phpMyAdmin at: http://$(curl -s ifconfig.me):8080"
    echo ""
    echo "📱 Frontend team can use API at: http://$(curl -s ifconfig.me)/api/"
else
    echo "⚠️ Application test failed. Try manually:"
    echo "   curl http://localhost/"
    echo "   docker-compose logs app"
fi

echo "🎉 Setup completion attempt finished!"