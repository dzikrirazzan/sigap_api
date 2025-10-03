#!/bin/bash

# Emergency API Backend Deployment Script
# Usage: curl -sSL https://raw.githubusercontent.com/dzikrirazzan/sigap_api/main/deploy-backend.sh | bash

set -e  # Exit on any error

echo "🚀 Starting Emergency API Backend Deployment..."
echo "📅 $(date)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function untuk print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Variabel konfigurasi
PROJECT_DIR="/var/www/Emergency_API"
DOCKER_COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env.docker"
GITHUB_REPO="https://github.com/dzikrirazzan/sigap_api.git"

# 1. Update sistem
print_status "Updating system packages..."
apt update && apt upgrade -y

# 2. Install Docker jika belum ada
if ! command -v docker &> /dev/null; then
    print_status "Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    systemctl start docker
    systemctl enable docker
    rm get-docker.sh
else
    print_status "Docker already installed"
fi

# 3. Install Docker Compose jika belum ada
if ! command -v docker-compose &> /dev/null; then
    print_status "Installing Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
else
    print_status "Docker Compose already installed"
fi

# 4. Install Git jika belum ada
if ! command -v git &> /dev/null; then
    print_status "Installing Git..."
    apt install git -y
else
    print_status "Git already installed"
fi

# 5. Clone atau update repository
if [ ! -d "$PROJECT_DIR" ]; then
    print_status "Cloning Emergency API repository..."
    mkdir -p /var/www
    cd /var/www
    git clone $GITHUB_REPO Emergency_API
    cd Emergency_API
else
    print_status "Updating Emergency API repository..."
    cd $PROJECT_DIR
    git pull origin main
fi

# 6. Copy environment file
print_status "Setting up environment file..."
if [ -f "$ENV_FILE" ]; then
    cp $ENV_FILE .env
    print_status "Environment file copied from $ENV_FILE"
else
    print_warning "Environment file $ENV_FILE not found, using default .env"
fi

# 7. Create necessary directories
print_status "Creating necessary directories..."
mkdir -p storage/logs
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache
mkdir -p docker/nginx/conf.d
mkdir -p docker/mysql/init

# 8. Set permissions
print_status "Setting file permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# 9. Stop existing containers
print_status "Stopping existing containers..."
docker-compose -f $DOCKER_COMPOSE_FILE down || true

# 10. Build and start containers
print_status "Building and starting Docker containers..."
docker-compose -f $DOCKER_COMPOSE_FILE up -d --build

# 11. Wait for database to be ready
print_status "Waiting for containers to be fully ready..."
sleep 45

# 12. Install composer dependencies (needed because of volume mount)
print_status "Installing composer dependencies..."
timeout 120 docker-compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction || {
    print_warning "Composer install failed, retrying..."
    sleep 10
    timeout 120 docker-compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction
}

# 13. Fix permissions after composer install
print_status "Fixing file permissions..."
timeout 30 docker-compose exec -T --user root app sh -c "
    chown -R www-data:www-data /var/www && \
    chmod 664 /var/www/.env && \
    chmod -R 775 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    mkdir -p /var/www/storage/logs && \
    touch /var/www/storage/logs/laravel.log && \
    chown www-data:www-data /var/www/storage/logs/laravel.log
" || print_warning "Permission fix may have failed"

# 14. Generate APP_KEY if not set
print_status "Generating APP_KEY..."
timeout 30 docker-compose exec -T app php artisan key:generate --force --no-ansi || print_warning "Key generation may have failed"

# 15. Run database migrations
print_status "Running database migrations..."
timeout 60 docker-compose exec -T app php artisan migrate --force --no-ansi || print_warning "Migration may have failed"

# 16. Cache configurations
print_status "Caching configurations..."
timeout 30 docker-compose exec -T app php artisan config:cache --no-ansi || true
timeout 30 docker-compose exec -T app php artisan route:cache --no-ansi || true

# 18. Final status check
print_status "Checking final container status..."
docker-compose ps

# 17. Test API endpoint
print_status "Testing API endpoint..."
sleep 10

# Test with multiple attempts and timeout
for i in {1..5}; do
    print_status "Testing application... attempt $i/5"
    if timeout 10 curl -f http://localhost/ > /dev/null 2>&1; then
        print_status "✅ Application is working!"
        break
    elif [ $i -eq 5 ]; then
        print_warning "⚠️ Application test failed, but deployment completed"
        print_warning "Check manually with: curl http://localhost/"
        print_warning "Or check logs with: docker-compose logs app"
    else
        sleep 10
    fi
done

# 18. Final status check

print_status "🎉 Backend deployment completed!"
print_status "📊 Access phpMyAdmin at: http://$(curl -s ifconfig.me):8080"
print_status "🔧 API Base URL: http://$(curl -s ifconfig.me)/api"
print_status "🌐 Server IP: $(curl -s ifconfig.me)"

echo ""
echo "🔍 Useful commands:"
echo "  - View logs: docker-compose logs -f app"
echo "  - Restart services: docker-compose restart"
echo "  - Enter container: docker-compose exec app bash"
echo "  - Run artisan: docker-compose exec app php artisan [command]"
echo "  - Management: ./manage.sh"
echo ""
echo "📱 Frontend team can use API at: http://$(curl -s ifconfig.me)/api"