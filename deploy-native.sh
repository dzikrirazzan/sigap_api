#!/bin/bash

# Native Emergency API Deployment for Ubuntu 22.04 LTS
# Usage: curl -sSL https://raw.githubusercontent.com/dzikrirazzan/sigap_api/main/deploy-native.sh | bash

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

echo "Emergency API Native Deployment for Ubuntu 22.04"
echo "$(date)"
echo "$(lsb_release -d | cut -f2)"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root: sudo bash or sudo su"
    exit 1
fi

# 1. Update system
print_status "Updating system packages..."
apt update && apt upgrade -y

# 2. Install basic dependencies
print_status "Installing basic dependencies..."
apt install -y software-properties-common curl wget git unzip

# 3. Add PHP 8.2 repository
print_status "Adding PHP 8.2 repository..."
add-apt-repository ppa:ondrej/php -y
apt update

# 4. Install PHP 8.2 and extensions
print_status "Installing PHP 8.2 and extensions..."
apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring php8.2-gd php8.2-bcmath php8.2-redis php8.2-intl php8.2-soap

# 5. Install Composer
print_status "Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
chmod +x /usr/local/bin/composer

# 6. Install MySQL 8.0
print_status "Installing MySQL 8.0..."
apt install -y mysql-server mysql-client

# 7. Install Redis
print_status "Installing Redis..."
apt install -y redis-server

# 8. Install Nginx
print_status "Installing Nginx..."
apt install -y nginx

# 9. Start and enable services
print_status "Starting services..."
systemctl start mysql
systemctl enable mysql
systemctl start redis-server
systemctl enable redis-server
systemctl start php8.2-fpm
systemctl enable php8.2-fpm
systemctl start nginx
systemctl enable nginx

# 10. Configure MySQL
print_status "Configuring MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS emergency_api;"
mysql -e "CREATE USER IF NOT EXISTS 'emergency_user'@'localhost' IDENTIFIED BY 'emergency_password';"
mysql -e "GRANT ALL PRIVILEGES ON emergency_api.* TO 'emergency_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# 11. Clone repository
print_status "Cloning Emergency API repository..."
cd /var/www
rm -rf emergency_api
git clone https://github.com/dzikrirazzan/sigap_api.git emergency_api
cd emergency_api

# 12. Set up environment
print_status "Setting up environment..."
cp .env.example .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=emergency_api/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=emergency_user/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=emergency_password/' .env
sed -i 's/REDIS_HOST=.*/REDIS_HOST=127.0.0.1/' .env

# 13. Install dependencies
print_status "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# 14. Set permissions
print_status "Setting permissions..."
chown -R www-data:www-data /var/www/emergency_api
chmod -R 755 /var/www/emergency_api
chmod -R 775 /var/www/emergency_api/storage
chmod -R 775 /var/www/emergency_api/bootstrap/cache

# 15. Laravel setup
print_status "Setting up Laravel..."
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan storage:link

# 16. Configure Nginx
print_status "Configuring Nginx..."
cat > /etc/nginx/sites-available/emergency_api << 'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/emergency_api/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/emergency_api /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test nginx config
nginx -t && systemctl reload nginx

# 17. Configure PHP-FPM pool (optional optimization)
print_status "Optimizing PHP-FPM..."
cat > /etc/php/8.2/fpm/pool.d/emergency_api.conf << 'EOF'
[emergency_api]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm-emergency.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF

systemctl restart php8.2-fpm

# 18. Set up log rotation
print_status "Setting up log rotation..."
cat > /etc/logrotate.d/emergency_api << 'EOF'
/var/www/emergency_api/storage/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
EOF

# 19. Create systemd service for queue worker (if needed)
print_status "Creating queue worker service..."
cat > /etc/systemd/system/emergency-api-worker.service << 'EOF'
[Unit]
Description=Emergency API Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/emergency_api
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

systemctl enable emergency-api-worker
systemctl start emergency-api-worker

# 20. Final status check
print_status "Checking services status..."
echo
print_status "=== Service Status ==="
systemctl is-active mysql && print_success "MySQL: Running" || print_error "MySQL: Failed"
systemctl is-active redis-server && print_success "Redis: Running" || print_error "Redis: Failed"
systemctl is-active php8.2-fpm && print_success "PHP-FPM: Running" || print_error "PHP-FPM: Failed"
systemctl is-active nginx && print_success "Nginx: Running" || print_error "Nginx: Failed"
systemctl is-active emergency-api-worker && print_success "Queue Worker: Running" || print_error "Queue Worker: Failed"

# 21. Test API
print_status "Testing API endpoint..."
sleep 3
if curl -f http://localhost/ > /dev/null 2>&1; then
    print_success "✅ API is working!"
else
    print_warning "⚠️ API test failed, check logs with: tail -f /var/www/emergency_api/storage/logs/laravel.log"
fi

# 22. Display final information
echo
print_success "Emergency API Deployment Completed!"
echo
echo "Service Information:"
echo "API URL: http://$(hostname -I | awk '{print $1}')/"
echo "Database: emergency_api"
echo "Project Path: /var/www/emergency_api"
echo "Logs: /var/www/emergency_api/storage/logs/laravel.log"
echo
echo "🔧 Management Commands:"
echo "sudo systemctl restart nginx"
echo "sudo systemctl restart php8.2-fpm"
echo "sudo systemctl restart mysql"
echo "sudo systemctl restart emergency-api-worker"
echo
echo "Monitor logs:"
echo "tail -f /var/www/emergency_api/storage/logs/laravel.log"
echo "tail -f /var/log/nginx/access.log"
echo "tail -f /var/log/nginx/error.log"