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
print_status "Waiting for database to be ready..."
sleep 30

# 12. Run Laravel setup commands
print_status "Running Laravel setup commands..."

# Install/update composer dependencies
docker-compose exec -T app composer install --no-dev --optimize-autoloader

# Generate application key if not set
docker-compose exec -T app php artisan key:generate --force

# Run database migrations
docker-compose exec -T app php artisan migrate --force

# Clear and cache configurations
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# 13. Check container status
print_status "Checking container status..."
docker-compose ps

# 14. Test API endpoint
print_status "Testing API endpoint..."
sleep 10
if curl -f http://localhost/api/ > /dev/null 2>&1; then
    print_status "✅ API is responding!"
else
    print_warning "⚠️  API might not be ready yet, check logs with: docker-compose logs app"
fi

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