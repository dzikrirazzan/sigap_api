# 📋 PANDUAN DEPLOYMENT VPS - SIGAP Emergency API

## 🖥️ SPESIFIKASI SERVER VPS (Ubuntu 22.04.5 LTS)

### Minimum Requirements:
- **OS**: Ubuntu 22.04.5 LTS (64-bit)
- **RAM**: Minimal 2GB (Rekomendasi: 4GB)
- **Storage**: Minimal 20GB SSD
- **CPU**: Minimal 2 vCPU
- **Bandwidth**: Unlimited atau minimal 1TB/bulan

### Network Requirements:
- **IP Address**: Static IP (Public)
- **Ports yang harus dibuka**:
  - Port 80 (HTTP)
  - Port 443 (HTTPS)
  - Port 22 (SSH)
  - Port 3306 (MySQL - jika database di server yang sama)

---

## 📦 SOFTWARE & DEPENDENCIES YANG HARUS DIINSTALL

### 1. System Updates & Essential Tools
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl wget git unzip
```

### 2. PHP 8.1+ & Extensions
```bash
# Add PHP Repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.1 dan extensions yang dibutuhkan
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-common \
    php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring \
    php8.1-curl php8.1-xml php8.1-bcmath php8.1-json \
    php8.1-intl php8.1-tokenizer php8.1-soap php8.1-redis

# Verifikasi instalasi
php -v
```

**PHP Extensions yang dibutuhkan oleh aplikasi:**
- `php8.1-mysql` - Database MySQL/MariaDB
- `php8.1-mbstring` - String manipulation
- `php8.1-xml` - XML processing
- `php8.1-curl` - HTTP requests (untuk Fonnte API)
- `php8.1-zip` - Composer dependencies
- `php8.1-gd` - Image processing (untuk upload foto)
- `php8.1-bcmath` - Precision mathematics
- `php8.1-json` - JSON handling
- `php8.1-intl` - Internationalization
- `php8.1-tokenizer` - Laravel Sanctum
- `php8.1-redis` - Optional (jika pakai Redis untuk cache)

### 3. Composer (Dependency Manager)
```bash
# Download dan install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verifikasi
composer --version
```

### 4. MySQL/MariaDB Database
```bash
# Install MySQL Server
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Login ke MySQL
sudo mysql -u root -p
```

**Konfigurasi Database:**
```sql
-- Buat database
CREATE DATABASE sigap_emergency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Buat user database
CREATE USER 'sigap_user'@'localhost' IDENTIFIED BY 'password_yang_kuat_disini';

-- Berikan privileges
GRANT ALL PRIVILEGES ON sigap_emergency.* TO 'sigap_user'@'localhost';
FLUSH PRIVILEGES;

-- Keluar
EXIT;
```

### 5. Nginx Web Server
```bash
# Install Nginx
sudo apt install -y nginx

# Start dan enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Verifikasi
sudo systemctl status nginx
```

### 6. Supervisor (untuk Queue Worker & Scheduler)
```bash
# Install Supervisor
sudo apt install -y supervisor

# Start dan enable Supervisor
sudo systemctl start supervisor
sudo systemctl enable supervisor
```

### 7. Node.js & NPM (untuk Vite build)
```bash
# Install Node.js 18.x LTS
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Verifikasi
node -v
npm -v
```

### 8. Certbot (untuk SSL Certificate - Let's Encrypt)
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx
```

### 9. Redis (Optional - untuk Cache & Queue)
```bash
# Install Redis
sudo apt install -y redis-server

# Start dan enable Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Verifikasi
redis-cli ping
# Output: PONG
```

---

## 🗂️ STRUKTUR DIREKTORI DI VPS

```
/var/www/
└── sigap-api/
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/          # Document root untuk Nginx
    ├── resources/
    ├── routes/
    ├── storage/         # Harus writable
    │   ├── app/
    │   ├── framework/
    │   └── logs/
    ├── vendor/
    ├── .env             # Environment configuration
    └── artisan
```

---

## 🚀 LANGKAH-LANGKAH DEPLOYMENT

### STEP 1: Clone Repository
```bash
# Masuk ke direktori web
cd /var/www

# Clone repository (ganti dengan URL repo Anda)
sudo git clone https://github.com/username/Emergency_API.git sigap-api

# Set ownership ke user www-data (Nginx user)
sudo chown -R www-data:www-data /var/www/sigap-api
sudo chmod -R 755 /var/www/sigap-api
```

### STEP 2: Install Dependencies
```bash
cd /var/www/sigap-api

# Install PHP dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# Install Node dependencies (jika ada)
sudo -u www-data npm install

# Build assets (jika ada)
sudo -u www-data npm run build
```

### STEP 3: Environment Configuration
```bash
# Copy .env.example ke .env
sudo -u www-data cp .env.example .env

# Edit .env file
sudo nano .env
```

**Konfigurasi .env untuk Production:**
```env
APP_NAME="SIGAP Emergency API"
APP_ENV=production
APP_KEY=base64:GENERATE_KEY_BARU_DISINI
APP_DEBUG=false
APP_URL=https://api.sigap-undip.com

# Timezone
APP_TIMEZONE=Asia/Jakarta

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_emergency
DB_USERNAME=sigap_user
DB_PASSWORD=password_yang_kuat_disini

# Session & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database

# Queue (gunakan database atau redis)
QUEUE_CONNECTION=database

# Mail Configuration (Postmark SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=your_postmark_token
MAIL_PASSWORD=your_postmark_token
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sigap-undip.com
MAIL_FROM_NAME="SIGAP UNDIP"

# WhatsApp Notification (Fonnte)
FONNTE_TOKEN=your_fonnte_token
FONNTE_BASE_URL=https://api.fonnte.com

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### STEP 4: Generate Application Key
```bash
# Generate APP_KEY
sudo -u www-data php artisan key:generate

# Verifikasi .env sudah terisi APP_KEY
cat .env | grep APP_KEY
```

### STEP 5: Set Permissions
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/sigap-api

# Set directory permissions
sudo find /var/www/sigap-api -type d -exec chmod 755 {} \;
sudo find /var/www/sigap-api -type f -exec chmod 644 {} \;

# Set storage & bootstrap/cache writable
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache

# Set storage group ownership
sudo chgrp -R www-data /var/www/sigap-api/storage
sudo chgrp -R www-data /var/www/sigap-api/bootstrap/cache
```

### STEP 6: Database Migration & Seeding
```bash
cd /var/www/sigap-api

# Run migrations
sudo -u www-data php artisan migrate --force

# Run seeders (untuk data awal)
sudo -u www-data php artisan db:seed --force

# Atau run seeder spesifik
sudo -u www-data php artisan db:seed --class=UserSeeder --force
sudo -u www-data php artisan db:seed --class=RelawanShiftPatternSeeder --force
```

### STEP 7: Storage Link
```bash
# Buat symbolic link untuk storage
sudo -u www-data php artisan storage:link
```

### STEP 8: Optimize Laravel
```bash
# Clear all caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Cache config, routes, views untuk production
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### STEP 9: Configure Nginx
```bash
# Buat file konfigurasi Nginx
sudo nano /etc/nginx/sites-available/sigap-api
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name api.sigap-undip.com;  # Ganti dengan domain Anda
    root /var/www/sigap-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Increase upload size limit (untuk upload foto)
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

**Enable site dan restart Nginx:**
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/sigap-api /etc/nginx/sites-enabled/

# Test konfigurasi
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### STEP 10: SSL Certificate (HTTPS)
```bash
# Install SSL Certificate dengan Certbot
sudo certbot --nginx -d api.sigap-undip.com

# Certbot akan otomatis update konfigurasi Nginx untuk HTTPS
# Pilih option untuk redirect HTTP ke HTTPS

# Test auto-renewal
sudo certbot renew --dry-run
```

### STEP 11: Configure Supervisor (Queue Worker & Scheduler)

**A. Queue Worker Configuration:**
```bash
# Buat file konfigurasi supervisor untuk queue worker
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

**B. Scheduler Configuration:**
```bash
# Buat file konfigurasi supervisor untuk scheduler
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

**Reload Supervisor:**
```bash
# Reload konfigurasi supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start sigap-queue-worker:*
sudo supervisorctl start sigap-scheduler:*

# Check status
sudo supervisorctl status
```

### STEP 12: Configure Firewall (UFW)
```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP & HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Check status
sudo ufw status
```

### STEP 13: Configure PHP-FPM
```bash
# Edit PHP-FPM configuration
sudo nano /etc/php/8.1/fpm/php.ini
```

**Konfigurasi penting:**
```ini
# Increase memory limit
memory_limit = 256M

# Increase upload size
upload_max_filesize = 20M
post_max_size = 20M

# Timezone
date.timezone = Asia/Jakarta

# Error reporting (production)
display_errors = Off
log_errors = On
error_log = /var/log/php8.1-fpm-error.log
```

**Restart PHP-FPM:**
```bash
sudo systemctl restart php8.1-fpm
```

---

## 🔧 COMMAND-COMMAND ARTISAN YANG HARUS DIJALANKAN

### Saat Deployment Pertama Kali:
```bash
cd /var/www/sigap-api

# 1. Generate application key
sudo -u www-data php artisan key:generate

# 2. Run migrations
sudo -u www-data php artisan migrate --force

# 3. Run seeders
sudo -u www-data php artisan db:seed --force

# 4. Create storage link
sudo -u www-data php artisan storage:link

# 5. Cache optimization
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### Saat Update/Deployment Baru:
```bash
cd /var/www/sigap-api

# 1. Pull latest code
sudo -u www-data git pull origin main

# 2. Install/Update dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# 3. Run migrations (jika ada migration baru)
sudo -u www-data php artisan migrate --force

# 4. Clear caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# 5. Re-cache untuk production
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 6. Restart queue workers
sudo supervisorctl restart sigap-queue-worker:*

# 7. Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Maintenance Commands:
```bash
# Masuk ke maintenance mode
sudo -u www-data php artisan down

# Keluar dari maintenance mode
sudo -u www-data php artisan up

# Clear specific cache
sudo -u www-data php artisan cache:forget key_name

# View routes
sudo -u www-data php artisan route:list

# Test database connection
sudo -u www-data php artisan tinker
# Dalam tinker: DB::connection()->getPdo();

# Generate test panic reports (untuk testing)
sudo -u www-data php artisan generate:test-panic-reports

# Auto-generate shifts
sudo -u www-data php artisan shifts:auto-generate --days=7
```

---

## 📊 MONITORING & LOGGING

### Log Files Location:
```bash
# Laravel logs
/var/www/sigap-api/storage/logs/laravel.log

# Queue worker logs
/var/www/sigap-api/storage/logs/queue-worker.log

# Scheduler logs
/var/www/sigap-api/storage/logs/scheduler.log

# Auto shift generation logs
/var/www/sigap-api/storage/logs/auto-shift-generation.log

# Nginx access logs
/var/log/nginx/access.log

# Nginx error logs
/var/log/nginx/error.log

# PHP-FPM error logs
/var/log/php8.1-fpm-error.log
```

### Monitoring Commands:
```bash
# Monitor Laravel logs
sudo tail -f /var/www/sigap-api/storage/logs/laravel.log

# Monitor Nginx access
sudo tail -f /var/log/nginx/access.log

# Monitor queue workers
sudo supervisorctl tail -f sigap-queue-worker:sigap-queue-worker_00 stdout

# Check disk usage
df -h

# Check memory usage
free -m

# Check running processes
ps aux | grep php

# Check MySQL processes
sudo mysqladmin -u root -p processlist
```

---

## 🔐 SECURITY CHECKLIST

### 1. File Permissions
```bash
# Pastikan .env tidak bisa diakses public
sudo chmod 600 /var/www/sigap-api/.env

# Pastikan storage hanya writable oleh www-data
sudo chmod -R 775 /var/www/sigap-api/storage
```

### 2. Environment Variables
- ✅ `APP_DEBUG=false` di production
- ✅ `APP_ENV=production`
- ✅ Strong `APP_KEY`
- ✅ Strong database password
- ✅ Secure API tokens (Fonnte, Postmark)

### 3. Database Security
```bash
# Pastikan MySQL hanya listen di localhost
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# bind-address = 127.0.0.1
```

### 4. Firewall
```bash
# Hanya allow port yang diperlukan
sudo ufw status
```

### 5. SSL/TLS
- ✅ HTTPS enabled
- ✅ Auto-renewal SSL certificate configured

### 6. Backup Strategy
```bash
# Backup database daily
sudo crontab -e
```

**Cron untuk backup database:**
```cron
# Backup database setiap hari jam 3 pagi
0 3 * * * mysqldump -u sigap_user -p'password' sigap_emergency > /backup/sigap_db_$(date +\%Y\%m\%d).sql
```

---

## 🧪 TESTING DEPLOYMENT

### 1. Test Health Check
```bash
curl https://api.sigap-undip.com/api/health
```

**Expected Response:**
```json
{
    "status": "ok",
    "service": "SIGAP Emergency API",
    "timestamp": "2026-01-26T10:20:00+07:00",
    "version": "1.0.0"
}
```

### 2. Test Database Connection
```bash
sudo -u www-data php artisan tinker
```
```php
DB::connection()->getPdo();
// Should return PDO object without error
```

### 3. Test Queue Worker
```bash
# Check supervisor status
sudo supervisorctl status

# Should show:
# sigap-queue-worker:sigap-queue-worker_00   RUNNING
# sigap-queue-worker:sigap-queue-worker_01   RUNNING
# sigap-scheduler:sigap-scheduler            RUNNING
```

### 4. Test Scheduler
```bash
# Run scheduler manually
sudo -u www-data php artisan schedule:run

# Check logs
cat /var/www/sigap-api/storage/logs/scheduler.log
```

### 5. Test API Endpoints
```bash
# Test login
curl -X POST https://api.sigap-undip.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com","password":"kikipoiu"}'

# Test authenticated endpoint (ganti TOKEN dengan token dari login)
curl -X GET https://api.sigap-undip.com/api/profile \
  -H "Authorization: Bearer TOKEN"
```

---

## 📝 CHECKLIST DEPLOYMENT

### Pre-Deployment:
- [ ] VPS dengan Ubuntu 22.04.5 sudah ready
- [ ] Domain sudah pointing ke IP VPS
- [ ] Akses SSH ke VPS sudah tersedia
- [ ] Database credentials sudah disiapkan
- [ ] API keys (Fonnte, Postmark) sudah disiapkan

### Installation:
- [ ] PHP 8.1+ terinstall dengan semua extensions
- [ ] Composer terinstall
- [ ] MySQL/MariaDB terinstall dan configured
- [ ] Nginx terinstall dan configured
- [ ] Supervisor terinstall
- [ ] Node.js & NPM terinstall
- [ ] Certbot terinstall

### Configuration:
- [ ] Repository di-clone ke `/var/www/sigap-api`
- [ ] Dependencies terinstall (`composer install`)
- [ ] `.env` file configured dengan benar
- [ ] `APP_KEY` generated
- [ ] File permissions sudah di-set
- [ ] Database migrations dijalankan
- [ ] Seeders dijalankan
- [ ] Storage link dibuat
- [ ] Laravel caches di-optimize

### Web Server:
- [ ] Nginx virtual host configured
- [ ] SSL certificate terinstall
- [ ] HTTPS redirect enabled
- [ ] Upload size limit configured

### Background Services:
- [ ] Queue worker configured di Supervisor
- [ ] Scheduler configured di Supervisor
- [ ] Supervisor services running

### Security:
- [ ] Firewall (UFW) configured
- [ ] `.env` file permissions secure
- [ ] `APP_DEBUG=false`
- [ ] Database hanya listen di localhost
- [ ] Strong passwords digunakan

### Testing:
- [ ] Health check endpoint works
- [ ] Database connection works
- [ ] API endpoints works
- [ ] Queue workers running
- [ ] Scheduler running
- [ ] SSL certificate valid

### Monitoring:
- [ ] Log files accessible
- [ ] Backup strategy implemented
- [ ] Monitoring tools configured (optional)

---

## 🆘 TROUBLESHOOTING

### Issue: 500 Internal Server Error
```bash
# Check Laravel logs
sudo tail -f /var/www/sigap-api/storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.1-fpm.log

# Common fixes:
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

### Issue: Permission Denied
```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/storage

# Fix bootstrap/cache permissions
sudo chown -R www-data:www-data /var/www/sigap-api/bootstrap/cache
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache
```

### Issue: Database Connection Failed
```bash
# Test MySQL connection
mysql -u sigap_user -p sigap_emergency

# Check MySQL is running
sudo systemctl status mysql

# Check .env database credentials
cat /var/www/sigap-api/.env | grep DB_
```

### Issue: Queue Not Processing
```bash
# Check supervisor status
sudo supervisorctl status

# Restart queue workers
sudo supervisorctl restart sigap-queue-worker:*

# Check queue worker logs
sudo tail -f /var/www/sigap-api/storage/logs/queue-worker.log
```

### Issue: Scheduler Not Running
```bash
# Check scheduler status
sudo supervisorctl status sigap-scheduler

# Restart scheduler
sudo supervisorctl restart sigap-scheduler

# Test scheduler manually
sudo -u www-data php artisan schedule:run
```

---

## 📞 CONTACT & SUPPORT

Untuk pertanyaan atau bantuan deployment, hubungi:
- **Developer**: Dzikri Razzan
- **Email**: dzikrirazzan@students.undip.ac.id

---

## 📚 REFERENSI

- [Laravel Deployment Documentation](https://laravel.com/docs/10.x/deployment)
- [Nginx Configuration](https://nginx.org/en/docs/)
- [Supervisor Documentation](http://supervisord.org/)
- [Let's Encrypt Certbot](https://certbot.eff.org/)
- [Ubuntu Server Guide](https://ubuntu.com/server/docs)

---

**Last Updated**: 2026-01-26
**Version**: 1.0.0
