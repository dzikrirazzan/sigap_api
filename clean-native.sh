#!/bin/bash

# Clean Emergency API Native Deployment
# Usage: curl -sSL https://raw.githubusercontent.com/dzikrirazzan/sigap_api/main/clean-native.sh | bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

echo "🧹 Cleaning Emergency API Native Deployment"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} Please run as root: sudo bash"
    exit 1
fi

# Stop services
print_status "Stopping services..."
systemctl stop emergency-api-worker || true
systemctl disable emergency-api-worker || true
systemctl stop nginx || true
systemctl stop php8.2-fpm || true

# Remove project files
print_status "Removing project files..."
rm -rf /var/www/emergency_api

# Remove nginx config
print_status "Removing nginx configuration..."
rm -f /etc/nginx/sites-enabled/emergency_api
rm -f /etc/nginx/sites-available/emergency_api
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Remove systemd service
print_status "Removing systemd service..."
rm -f /etc/systemd/system/emergency-api-worker.service
systemctl daemon-reload

# Remove PHP-FPM pool
print_status "Removing PHP-FPM pool..."
rm -f /etc/php/8.2/fpm/pool.d/emergency_api.conf

# Remove logrotate config
print_status "Removing log rotation..."
rm -f /etc/logrotate.d/emergency_api

# Drop database
print_status "Removing database..."
mysql -e "DROP DATABASE IF EXISTS emergency_api;" || true
mysql -e "DROP USER IF EXISTS 'emergency_user'@'localhost';" || true

# Restart services
print_status "Restarting services..."
systemctl restart nginx
systemctl restart php8.2-fpm

print_status "✅ Cleanup completed!"
print_status "System restored to clean state"

# Optional: remove installed packages (uncomment if needed)
print_warning "To remove installed packages, run:"
echo "sudo apt remove --purge php8.2* mysql-server mysql-client redis-server nginx composer"
echo "sudo apt autoremove"