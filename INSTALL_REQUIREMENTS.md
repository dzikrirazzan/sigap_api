# 📦 INSTALL REQUIREMENTS - SIGAP Emergency API

**Target OS**: Ubuntu 22.04.5 LTS  
**PHP Version**: 8.1+  
**Laravel Version**: 10.x

---

## 🖥️ SERVER REQUIREMENTS

```
OS:        Ubuntu 22.04.5 LTS (64-bit)
RAM:       Minimal 2GB (Rekomendasi: 4GB)
Storage:   Minimal 20GB SSD
CPU:       Minimal 2 vCPU
IP:        Static Public IP
Domain:    Sudah pointing ke IP VPS
```

**Ports yang harus dibuka:**
- Port 22 (SSH)
- Port 80 (HTTP)
- Port 443 (HTTPS)

---

## 📦 INSTALLATION COMMANDS

### 1. Update System
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl wget git unzip
```

### 2. Install PHP 8.1 + Extensions
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-common \
    php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring \
    php8.1-curl php8.1-xml php8.1-bcmath php8.1-json \
    php8.1-intl php8.1-tokenizer php8.1-soap
```

**Verify:**
```bash
php -v
```

### 3. Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

**Verify:**
```bash
composer --version
```

### 4. Install MySQL
```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

**Create Database:**
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE sigap_emergency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sigap_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON sigap_emergency.* TO 'sigap_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Install Nginx
```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 6. Install Supervisor
```bash
sudo apt install -y supervisor
sudo systemctl start supervisor
sudo systemctl enable supervisor
```

### 7. Install Node.js & NPM
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

**Verify:**
```bash
node -v
npm -v
```

### 8. Install Certbot (SSL)
```bash
sudo apt install -y certbot python3-certbot-nginx
```

---

## 🔧 PHP EXTENSIONS REQUIRED

| Extension | Purpose |
|-----------|---------|
| `php8.1-fpm` | FastCGI Process Manager |
| `php8.1-cli` | Command Line Interface |
| `php8.1-mysql` | MySQL Database Driver |
| `php8.1-mbstring` | Multibyte String Functions |
| `php8.1-xml` | XML Processing |
| `php8.1-curl` | HTTP Client (API calls) |
| `php8.1-zip` | ZIP Archive Handling |
| `php8.1-gd` | Image Processing |
| `php8.1-bcmath` | Precision Mathematics |
| `php8.1-json` | JSON Processing |
| `php8.1-intl` | Internationalization |
| `php8.1-tokenizer` | Token Parsing (Sanctum) |

**Check installed extensions:**
```bash
php -m
```

---

## 🗄️ DATABASE CONFIGURATION

```
Database Name:     sigap_emergency
Database User:     sigap_user
Database Password: [Your Strong Password]
Character Set:     utf8mb4
Collation:         utf8mb4_unicode_ci
```

---

## 🔑 CREDENTIALS NEEDED

### 1. Database
- Database Host: `127.0.0.1`
- Database Port: `3306`
- Database Name: `sigap_emergency`
- Database User: `sigap_user`
- Database Password: `[Your Password]`

### 2. Email (Postmark SMTP)
- SMTP Host: `smtp.postmarkapp.com`
- SMTP Port: `587`
- SMTP Username: `[Postmark Token]`
- SMTP Password: `[Postmark Token]`
- From Email: `noreply@your-domain.com`
- From Name: `SIGAP UNDIP`

### 3. WhatsApp (Fonnte)
- Fonnte Token: `[Your Fonnte Token]`
- Fonnte Base URL: `https://api.fonnte.com`

### 4. Domain
- Domain Name: `api.your-domain.com`
- SSL Email: `admin@your-domain.com`

---

## 📂 APPLICATION SETUP

### 1. Clone Repository
```bash
cd /var/www
sudo git clone https://github.com/YOUR_USERNAME/Emergency_API.git sigap-api
cd sigap-api
```

### 2. Install Dependencies
```bash
sudo -u www-data composer install --optimize-autoloader --no-dev
```

### 3. Setup Environment
```bash
sudo -u www-data cp .env.example .env
sudo nano .env
```

**Minimal .env configuration:**
```env
APP_NAME="SIGAP Emergency API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_emergency
DB_USERNAME=sigap_user
DB_PASSWORD=YOUR_DB_PASSWORD

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=YOUR_POSTMARK_TOKEN
MAIL_PASSWORD=YOUR_POSTMARK_TOKEN
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SIGAP UNDIP"

FONNTE_TOKEN=YOUR_FONNTE_TOKEN
FONNTE_BASE_URL=https://api.fonnte.com
```

### 4. Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/sigap-api
sudo find /var/www/sigap-api -type d -exec chmod 755 {} \;
sudo find /var/www/sigap-api -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache
```

### 5. Run Laravel Commands
```bash
cd /var/www/sigap-api

# Generate key
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Run seeders
sudo -u www-data php artisan db:seed --force

# Storage link
sudo -u www-data php artisan storage:link

# Cache optimization
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 🌐 NGINX CONFIGURATION

**Create config file:**
```bash
sudo nano /etc/nginx/sites-available/sigap-api
```

**Paste this:**
```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name api.your-domain.com;
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

---

## 🔒 SSL CERTIFICATE

```bash
sudo certbot --nginx -d api.your-domain.com
```

---

## ⚙️ SUPERVISOR CONFIGURATION

### Queue Worker
```bash
sudo nano /etc/supervisor/conf.d/sigap-queue-worker.conf
```

**Paste this:**
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

### Scheduler
```bash
sudo nano /etc/supervisor/conf.d/sigap-scheduler.conf
```

**Paste this:**
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

---

## 🔥 FIREWALL SETUP

```bash
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw status
```

---

## 🧪 TEST DEPLOYMENT

```bash
# Health check
curl https://api.your-domain.com/api/health

# Should return:
# {"status":"ok","service":"SIGAP Emergency API","timestamp":"...","version":"1.0.0"}
```

---

## 🔄 UPDATE COMMANDS

```bash
cd /var/www/sigap-api

# Maintenance mode
sudo -u www-data php artisan down

# Pull latest code
sudo -u www-data git pull origin main

# Update dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# Run migrations
sudo -u www-data php artisan migrate --force

# Clear caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Re-cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart services
sudo supervisorctl restart sigap-queue-worker:*
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

# Exit maintenance
sudo -u www-data php artisan up
```

---

## 📋 QUICK CHECKLIST

**Software:**
- [ ] PHP 8.1 + extensions
- [ ] Composer
- [ ] MySQL
- [ ] Nginx
- [ ] Supervisor
- [ ] Node.js + NPM
- [ ] Certbot

**Configuration:**
- [ ] Database created
- [ ] .env configured
- [ ] Migrations run
- [ ] Seeders run
- [ ] Storage link created
- [ ] Nginx configured
- [ ] SSL installed
- [ ] Supervisor configured
- [ ] Firewall configured

**Testing:**
- [ ] Health check works
- [ ] Login works
- [ ] Queue workers running
- [ ] Scheduler running

---

## 🆘 COMMON ISSUES

### Permission Denied
```bash
sudo chown -R www-data:www-data /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/storage
```

### 500 Error
```bash
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo systemctl restart php8.1-fpm
```

### Database Connection Failed
```bash
# Check .env credentials
cat /var/www/sigap-api/.env | grep DB_

# Test connection
sudo -u www-data php artisan tinker
# DB::connection()->getPdo();
```

---

**Version**: 1.0.0  
**Last Updated**: 2026-01-26
