

# ============================================
# STEP 11: SETUP ENVIRONMENT FILE
# ============================================

# Copy .env.production to .env
sudo -u www-data cp .env.production .env

# Or copy from example
sudo -u www-data cp .env.example .env

# Edit .env file
sudo nano .env

# Update these values in .env:
# ----------------------------------------
# APP_URL=https://api.your-domain.com
# DB_DATABASE=sigap_emergency
# DB_USERNAME=sigap_user
# DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE
# MAIL_USERNAME=YOUR_POSTMARK_TOKEN
# MAIL_PASSWORD=YOUR_POSTMARK_TOKEN
# MAIL_FROM_ADDRESS=noreply@your-domain.com
# FONNTE_TOKEN=YOUR_FONNTE_TOKEN
# ----------------------------------------


# ============================================
# STEP 12: GENERATE APPLICATION KEY
# ============================================

sudo -u www-data php artisan key:generate

# Verify APP_KEY is generated
cat .env | grep APP_KEY


# ============================================
# STEP 13: SET FILE PERMISSIONS
# ============================================

# Set ownership
sudo chown -R www-data:www-data /var/www/sigap-api

# Set directory permissions
sudo find /var/www/sigap-api -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/sigap-api -type f -exec chmod 644 {} \;

# Set storage writable
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache

# Set storage group ownership
sudo chgrp -R www-data /var/www/sigap-api/storage
sudo chgrp -R www-data /var/www/sigap-api/bootstrap/cache

# Secure .env file
sudo chmod 600 /var/www/sigap-api/.env


# ============================================
# STEP 14: RUN DATABASE MIGRATIONS
# ============================================

cd /var/www/sigap-api

# Run migrations
sudo -u www-data php artisan migrate --force

# Check migration status
sudo -u www-data php artisan migrate:status


# ============================================
# STEP 15: RUN DATABASE SEEDERS
# ============================================

# Run all seeders
sudo -u www-data php artisan db:seed --force

# Or run specific seeders
sudo -u www-data php artisan db:seed --class=UserSeeder --force
sudo -u www-data php artisan db:seed --class=RelawanShiftPatternSeeder --force


# ============================================
# STEP 16: CREATE STORAGE LINK
# ============================================

sudo -u www-data php artisan storage:link

# Verify symbolic link
ls -la /var/www/sigap-api/public/storage


# ============================================
# STEP 17: OPTIMIZE LARAVEL
# ============================================

# Clear all caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Cache for production
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache


# ============================================
# STEP 18: CONFIGURE NGINX
# ============================================

# Create Nginx configuration file
sudo nano /etc/nginx/sites-available/sigap-api

# Paste this configuration:
# ----------------------------------------
# server {
#     listen 80;
#     listen [::]:80;
#     
#     server_name api.your-domain.com;
#     root /var/www/sigap-api/public;
# 
#     add_header X-Frame-Options "SAMEORIGIN";
#     add_header X-Content-Type-Options "nosniff";
# 
#     index index.php;
#     charset utf-8;
#     client_max_body_size 20M;
# 
#     location / {
#         try_files $uri $uri/ /index.php?$query_string;
#     }
# 
#     location = /favicon.ico { access_log off; log_not_found off; }
#     location = /robots.txt  { access_log off; log_not_found off; }
# 
#     error_page 404 /index.php;
# 
#     location ~ \.php$ {
#         fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
#         fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
#         include fastcgi_params;
#         fastcgi_hide_header X-Powered-By;
#     }
# 
#     location ~ /\.(?!well-known).* {
#         deny all;
#     }
# }
# ----------------------------------------

# Enable site
sudo ln -s /etc/nginx/sites-available/sigap-api /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx


# ============================================
# STEP 19: INSTALL SSL CERTIFICATE
# ============================================

# Install SSL certificate
sudo certbot --nginx -d api.your-domain.com

# Follow the prompts:
# - Enter email address
# - Agree to terms
# - Choose to redirect HTTP to HTTPS (option 2)

# Test auto-renewal
sudo certbot renew --dry-run


# ============================================
# STEP 20: CONFIGURE SUPERVISOR (QUEUE WORKER)
# ============================================

# Create queue worker configuration
sudo nano /etc/supervisor/conf.d/sigap-queue-worker.conf

# Paste this configuration:
# ----------------------------------------
# [program:sigap-queue-worker]
# process_name=%(program_name)s_%(process_num)02d
# command=php /var/www/sigap-api/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
# autostart=true
# autorestart=true
# stopasgroup=true
# killasgroup=true
# user=www-data
# numprocs=2
# redirect_stderr=true
# stdout_logfile=/var/www/sigap-api/storage/logs/queue-worker.log
# stopwaitsecs=3600
# ----------------------------------------


# ============================================
# STEP 21: CONFIGURE SUPERVISOR (SCHEDULER)
# ============================================

# Create scheduler configuration
sudo nano /etc/supervisor/conf.d/sigap-scheduler.conf

# Paste this configuration:
# ----------------------------------------
# [program:sigap-scheduler]
# process_name=%(program_name)s
# command=/bin/bash -c "while [ true ]; do php /var/www/sigap-api/artisan schedule:run --verbose --no-interaction & sleep 60; done"
# autostart=true
# autorestart=true
# user=www-data
# redirect_stderr=true
# stdout_logfile=/var/www/sigap-api/storage/logs/scheduler.log
# ----------------------------------------


# ============================================
# STEP 22: START SUPERVISOR SERVICES
# ============================================

# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start queue workers
sudo supervisorctl start sigap-queue-worker:*

# Start scheduler
sudo supervisorctl start sigap-scheduler:*

# Check status
sudo supervisorctl status


# ============================================
# STEP 23: CONFIGURE FIREWALL (UFW)
# ============================================

# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Check firewall status
sudo ufw status


# ============================================
# STEP 24: CONFIGURE PHP-FPM
# ============================================

# Edit PHP-FPM configuration
sudo nano /etc/php/8.1/fpm/php.ini

# Update these values:
# ----------------------------------------
# memory_limit = 256M
# upload_max_filesize = 20M
# post_max_size = 20M
# date.timezone = Asia/Jakarta
# display_errors = Off
# log_errors = On
# error_log = /var/log/php8.1-fpm-error.log
# ----------------------------------------

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm


# ============================================
# STEP 25: TEST DEPLOYMENT
# ============================================

# Test health check endpoint
curl https://api.your-domain.com/api/health

# Expected response:
# {"status":"ok","service":"SIGAP Emergency API","timestamp":"...","version":"1.0.0"}

# Test login endpoint
curl -X POST https://api.your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com","password":"kikipoiu"}'

# Test with browser
# Open: https://api.your-domain.com/api/health


# ============================================
# STEP 26: VERIFY SERVICES
# ============================================

# Check Nginx status
sudo systemctl status nginx

# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Check MySQL status
sudo systemctl status mysql

# Check Supervisor status
sudo supervisorctl status

# Check queue workers
sudo supervisorctl status sigap-queue-worker:*

# Check scheduler
sudo supervisorctl status sigap-scheduler:*


# ============================================
# STEP 27: CHECK LOGS
# ============================================

# Laravel logs
sudo tail -f /var/www/sigap-api/storage/logs/laravel.log

# Queue worker logs
sudo tail -f /var/www/sigap-api/storage/logs/queue-worker.log

# Scheduler logs
sudo tail -f /var/www/sigap-api/storage/logs/scheduler.log

# Nginx access logs
sudo tail -f /var/log/nginx/access.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.1-fpm.log


# ============================================
# STEP 28: CHANGE DEFAULT PASSWORDS
# ============================================

# Login to application and change these default passwords:
# Admin: admin@gmail.com / kikipoiu
# Admin: admindzikri@gmail.com / password
# Relawan: relawan@gmail.com / kikipoiu
# User: user@gmail.com / kikipoiu


# ============================================
# STEP 29: SETUP DATABASE BACKUP
# ============================================

# Create backup directory
sudo mkdir -p /backup/sigap-api
sudo chown www-data:www-data /backup/sigap-api

# Create backup script
sudo nano /usr/local/bin/backup-sigap-db.sh

# Paste this script:
# ----------------------------------------
# #!/bin/bash
# BACKUP_DIR="/backup/sigap-api"
# DATE=$(date +%Y%m%d_%H%M%S)
# DB_NAME="sigap_emergency"
# DB_USER="sigap_user"
# DB_PASS="YOUR_DB_PASSWORD"
# 
# mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz
# find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete
# echo "Backup completed: db_backup_$DATE.sql.gz"
# ----------------------------------------

# Make script executable
sudo chmod +x /usr/local/bin/backup-sigap-db.sh

# Add to crontab (backup daily at 3 AM)
sudo crontab -e
# Add this line:
# 0 3 * * * /usr/local/bin/backup-sigap-db.sh >> /var/log/sigap-backup.log 2>&1


# ============================================
# STEP 30: FINAL VERIFICATION
# ============================================

# Test database connection
sudo -u www-data php artisan tinker
# In tinker, run:
# DB::connection()->getPdo();
# exit

# Test queue
sudo -u www-data php artisan queue:work database --once

# Test scheduler
sudo -u www-data php artisan schedule:run

# Check all services are running
sudo systemctl status nginx php8.1-fpm mysql
sudo supervisorctl status


# ============================================
# UPDATE/REDEPLOY COMMANDS
# ============================================

# When you need to update the application:

cd /var/www/sigap-api

# 1. Enable maintenance mode
sudo -u www-data php artisan down

# 2. Pull latest code
sudo -u www-data git pull origin main

# 3. Update dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# 4. Run new migrations
sudo -u www-data php artisan migrate --force

# 5. Clear caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# 6. Re-cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 7. Restart services
sudo supervisorctl restart sigap-queue-worker:*
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

# 8. Disable maintenance mode
sudo -u www-data php artisan up


# ============================================
# USEFUL MAINTENANCE COMMANDS
# ============================================

# Clear all Laravel caches
sudo -u www-data php artisan optimize:clear

# View routes
sudo -u www-data php artisan route:list

# View scheduled tasks
sudo -u www-data php artisan schedule:list

# Check failed queue jobs
sudo -u www-data php artisan queue:failed

# Retry failed jobs
sudo -u www-data php artisan queue:retry all

# Clear failed jobs
sudo -u www-data php artisan queue:flush

# Generate test panic reports
sudo -u www-data php artisan generate:test-panic-reports

# Auto-generate shifts
sudo -u www-data php artisan shifts:auto-generate --days=7

# Restart all supervisor services
sudo supervisorctl restart all

# Reload supervisor configuration
sudo supervisorctl reread && sudo supervisorctl update


# ============================================
# TROUBLESHOOTING COMMANDS
# ============================================

# Fix permissions
sudo chown -R www-data:www-data /var/www/sigap-api
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache

# Clear all caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Restart all services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
sudo supervisorctl restart all

# Check disk space
df -h

# Check memory usage
free -m

# Check running processes
ps aux | grep php


# ============================================
# END OF DEPLOYMENT COMMANDS
# ============================================
