#!/bin/bash

# Update Deployment Script for SIGAP Emergency API
# 
# Usage Method 1 (Remote - Direct from GitHub):
#   curl -sSL https://raw.githubusercontent.com/dzikrirazzan/sigap_api/main/update-deploy.sh | sudo bash
#
# Usage Method 2 (Local - If you have the file):
#   sudo bash update-deploy.sh
#   or: sudo ./update-deploy.sh
# 
# This script safely updates an existing deployment without reinstalling
# the entire stack. Use this for code updates after initial deployment.

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "========================================="
echo "SIGAP Emergency API - Update Deployment"
echo "========================================="
echo "$(date)"
echo ""

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    print_error "Please run with sudo: curl -sSL https://raw.githubusercontent.com/.../update-deploy.sh | sudo bash"
    exit 1
fi

# Function to find project directory
find_project_directory() {
    print_status "Searching for project directory..."
    
    # List of possible project locations
    POSSIBLE_PATHS=(
        "/var/www/emergency_api"
        "/var/www/html/emergency_api"
        "/opt/emergency_api"
        "$HOME/emergency_api"
    )
    
    # Also check /home/*/emergency_api for all users
    for user_home in /home/*; do
        if [ -d "$user_home/emergency_api" ]; then
            POSSIBLE_PATHS+=("$user_home/emergency_api")
        fi
    done
    
    # Try each possible path
    for path in "${POSSIBLE_PATHS[@]}"; do
        if [ -d "$path" ] && [ -f "$path/artisan" ]; then
            PROJECT_DIR="$path"
            print_success "Found project at: $PROJECT_DIR"
            return 0
        fi
    done
    
    # If not found, ask user
    print_warning "Could not auto-detect project directory."
    echo -n "Please enter the full path to your emergency_api directory: "
    read -r user_input
    
    if [ -d "$user_input" ] && [ -f "$user_input/artisan" ]; then
        PROJECT_DIR="$user_input"
        print_success "Using directory: $PROJECT_DIR"
        return 0
    else
        print_error "Invalid directory. Make sure the path contains the Laravel project with artisan file."
        return 1
    fi
}

# Find the project directory
if ! find_project_directory; then
    print_error "Failed to locate project directory. Please run deploy-native.sh first for initial setup."
    exit 1
fi

cd $PROJECT_DIR

# 1. Backup current state (optional but recommended)
print_status "Creating backup..."
BACKUP_DIR="/var/www/backups"
mkdir -p $BACKUP_DIR
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
tar -czf "$BACKUP_DIR/emergency_api_backup_$TIMESTAMP.tar.gz" \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs' \
    . 2>/dev/null || true
print_success "Backup created: emergency_api_backup_$TIMESTAMP.tar.gz"

# 2. Put application in maintenance mode
print_status "Enabling maintenance mode..."
php artisan down --message="Updating application, please wait..." --retry=60 || true

# 3. Pull latest code from GitHub
print_status "Pulling latest code from GitHub..."
git fetch origin
git pull origin main

# 4. Install/update Composer dependencies
print_status "Installing/updating Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 5. Run database migrations (if any)
print_status "Running database migrations..."
php artisan migrate --force

# 6. Clear all caches
print_status "Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 7. Rebuild optimized caches
print_status "Rebuilding optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Create storage link (if not exists)
print_status "Ensuring storage link exists..."
php artisan storage:link || true

# 9. Fix permissions
print_status "Fixing permissions..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 $PROJECT_DIR/storage
chmod -R 775 $PROJECT_DIR/bootstrap/cache

# 10. Restart PHP-FPM
print_status "Restarting PHP-FPM..."
systemctl restart php8.2-fpm

# 11. Reload Nginx (graceful reload, no downtime)
print_status "Reloading Nginx..."
systemctl reload nginx

# 12. Bring application back online
print_status "Disabling maintenance mode..."
php artisan up

# 13. Check service status
print_status "Checking service status..."
echo ""
print_status "=== Service Status ==="
systemctl is-active php8.2-fpm && print_success "PHP-FPM: Running" || print_error "PHP-FPM: Failed"
systemctl is-active nginx && print_success "Nginx: Running" || print_error "Nginx: Failed"
systemctl is-active mysql && print_success "MySQL: Running" || print_error "MySQL: Failed"

# 14. Show application info
echo ""
print_status "=== Deployment Info ==="
echo "Git Branch: $(git branch --show-current)"
echo "Last Commit: $(git log -1 --pretty=format:'%h - %s (%cr)')"
echo "Laravel Version: $(php artisan --version)"
echo ""

print_success "========================================="
print_success "🎉 Update deployment completed!"
print_success "========================================="
print_success "Your application has been successfully updated"
print_success "Backup location: $BACKUP_DIR/emergency_api_backup_$TIMESTAMP.tar.gz"
echo ""
print_warning "If you encounter any issues, check logs:"
echo "  - Laravel: $PROJECT_DIR/storage/logs/laravel.log"
echo "  - Nginx: /var/log/nginx/error.log"
echo "  - PHP-FPM: /var/log/php8.2-fpm.log"
