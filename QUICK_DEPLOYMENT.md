# 🚀 QUICK DEPLOYMENT COMMANDS - SIGAP API

## INSTALASI AWAL (Copy-Paste Commands)

### 1. Update System & Install Dependencies
```bash
# System update
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl wget git unzip

# PHP 8.1 + Extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-common \
    php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring \
    php8.1-curl php8.1-xml php8.1-bcmath php8.1-json \
    php8.1-intl php8.1-tokenizer php8.1-soap

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# MySQL
sudo apt install -y mysql-server

# Nginx
sudo apt install -y nginx

# Supervisor
sudo apt install -y supervisor

# Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Certbot (SSL)
sudo apt install -y certbot python3-certbot-nginx
```

### 2. Setup Database
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE sigap_emergency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sigap_user'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_DISINI';
GRANT ALL PRIVILEGES ON sigap_emergency.* TO 'sigap_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Clone & Setup Application
```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/YOUR_USERNAME/Emergency_API.git sigap-api
cd sigap-api

# Set ownership
sudo chown -R www-data:www-data /var/www/sigap-api

# Install dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# Copy .env
sudo -u www-data cp .env.example .env

# Edit .env (gunakan nano atau vim)
sudo nano .env
```

### 4. Configure .env (Minimal Configuration)
```env
APP_NAME="SIGAP Emergency API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_emergency
DB_USERNAME=sigap_user
DB_PASSWORD=GANTI_PASSWORD_DISINI

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Email & WhatsApp (isi sesuai credentials Anda)
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=your_token
MAIL_PASSWORD=your_token
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SIGAP UNDIP"

FONNTE_TOKEN=your_fonnte_token
FONNTE_BASE_URL=https://api.fonnte.com
```

### 5. Laravel Setup
```bash
# Generate key
sudo -u www-data php artisan key:generate

# Set permissions
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache
sudo chgrp -R www-data /var/www/sigap-api/storage
sudo chgrp -R www-data /var/www/sigap-api/bootstrap/cache

# Run migrations
sudo -u www-data php artisan migrate --force

# Run seeders
sudo -u www-data php artisan db:seed --force

# Storage link
sudo -u www-data php artisan storage:link

# Optimize
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### 6. Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/sigap-api
```

**Paste this configuration (ganti your-domain.com):**
```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name your-domain.com;
    root /var/www/sigap-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/sigap-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 7. SSL Certificate
```bash
sudo certbot --nginx -d your-domain.com
# Pilih option untuk redirect HTTP ke HTTPS
```

### 8. Supervisor Configuration

**Queue Worker:**
```bash
sudo nano /etc/supervisor/conf.d/sigap-queue-worker.conf
```
```ini
[program:sigap-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sigap-api/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sigap-api/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Scheduler:**
```bash
sudo nano /etc/supervisor/conf.d/sigap-scheduler.conf
```
```ini
[program:sigap-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while [ true ]; do php /var/www/sigap-api/artisan schedule:run --verbose --no-interaction & sleep 60; done"
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sigap-api/storage/logs/scheduler.log
```

**Start services:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sigap-queue-worker:*
sudo supervisorctl start sigap-scheduler:*
sudo supervisorctl status
```

### 9. Firewall
```bash
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw status
```

---

## UPDATE/REDEPLOY COMMANDS

```bash
cd /var/www/sigap-api

# 1. Maintenance mode
sudo -u www-data php artisan down

# 2. Pull latest code
sudo -u www-data git pull origin main

# 3. Update dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# 4. Run migrations (jika ada)
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

# 8. Exit maintenance mode
sudo -u www-data php artisan up
```

---

## USEFUL COMMANDS

### Monitoring
```bash
# Laravel logs
sudo tail -f /var/www/sigap-api/storage/logs/laravel.log

# Queue worker logs
sudo tail -f /var/www/sigap-api/storage/logs/queue-worker.log

# Nginx access logs
sudo tail -f /var/log/nginx/access.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Supervisor status
sudo supervisorctl status

# System resources
htop
df -h
free -m
```

### Troubleshooting
```bash
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
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
sudo supervisorctl restart all

# Test database connection
sudo -u www-data php artisan tinker
# DB::connection()->getPdo();
```

### Database
```bash
# Backup database
mysqldump -u sigap_user -p sigap_emergency > backup_$(date +%Y%m%d).sql

# Restore database
mysql -u sigap_user -p sigap_emergency < backup_20260126.sql

# Access MySQL
sudo mysql -u root -p
mysql -u sigap_user -p sigap_emergency
```

### Artisan Commands
```bash
# Run specific seeder
sudo -u www-data php artisan db:seed --class=UserSeeder --force

# Generate test data
sudo -u www-data php artisan generate:test-panic-reports

# Auto-generate shifts
sudo -u www-data php artisan shifts:auto-generate --days=7

# View routes
sudo -u www-data php artisan route:list

# Tinker (Laravel REPL)
sudo -u www-data php artisan tinker
```

---

## TESTING DEPLOYMENT

```bash
# 1. Test health check
curl https://your-domain.com/api/health

# 2. Test login
curl -X POST https://your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com","password":"kikipoiu"}'

# 3. Test authenticated endpoint
curl -X GET https://your-domain.com/api/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## SEEDER DATA

Setelah deployment, database akan memiliki user-user berikut:

### Admin Accounts:
- Email: `admin@gmail.com` | Password: `kikipoiu`
- Email: `admindzikri@gmail.com` | Password: `password`

### Relawan Accounts:
- Email: `relawan@gmail.com` | Password: `kikipoiu`
- Email: `relawan1@gmail.com` | Password: `kikipoiu`
- Email: `relawan2@gmail.com` | Password: `kikipoiu`
- Email: `relawan3@gmail.com` | Password: `kikipoiu`
- Email: `relawan4@gmail.com` | Password: `kikipoiu`
- Email: `relawan5@gmail.com` | Password: `kikipoiu`

### User Accounts:
- Email: `user@gmail.com` | Password: `kikipoiu`
- Email: `user1@gmail.com` | Password: `kikipoiu`
- Email: `user2@gmail.com` | Password: `kikipoiu`
- Email: `user3@gmail.com` | Password: `kikipoiu`
- Email: `user4@gmail.com` | Password: `kikipoiu`
- Email: `user5@gmail.com` | Password: `kikipoiu`

**⚠️ PENTING: Ganti password default setelah deployment!**

---

## SCHEDULED TASKS

Aplikasi ini memiliki scheduled task yang berjalan otomatis:

1. **Auto-Generate Shifts**: Setiap hari jam 02:00 WIB
   - Generate shift untuk 7 hari ke depan
   - Berdasarkan weekly pattern yang sudah di-set
   - Log: `/var/www/sigap-api/storage/logs/auto-shift-generation.log`

2. **Log Cleanup**: Setiap minggu
   - Clear log file jika ukuran > 5MB

---

## BACKUP STRATEGY

### Setup Automated Database Backup:
```bash
# Create backup directory
sudo mkdir -p /backup/sigap-api
sudo chown www-data:www-data /backup/sigap-api

# Create backup script
sudo nano /usr/local/bin/backup-sigap-db.sh
```

**Backup script:**
```bash
#!/bin/bash
BACKUP_DIR="/backup/sigap-api"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="sigap_emergency"
DB_USER="sigap_user"
DB_PASS="YOUR_DB_PASSWORD"

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Keep only last 7 days backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete

echo "Backup completed: db_backup_$DATE.sql.gz"
```

**Make executable and add to cron:**
```bash
sudo chmod +x /usr/local/bin/backup-sigap-db.sh

# Add to crontab (backup setiap hari jam 3 pagi)
sudo crontab -e
```
```cron
0 3 * * * /usr/local/bin/backup-sigap-db.sh >> /var/log/sigap-backup.log 2>&1
```

---

**Last Updated**: 2026-01-26
