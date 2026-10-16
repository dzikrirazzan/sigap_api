# SIGAP UNDIP – Production Deployment Requirements

**Version**: 1.0  
**Date**: 2026-01-26

---

## 📦 SOFTWARE REQUIREMENTS

### Install di VPS (Required)

**Core Software:**
- Nginx (latest stable)
- PHP 8.1+ + PHP-FPM 8.1+
- Composer 2.x
- MySQL 8.0
- Supervisor (latest)
- Git (latest)
- Node.js 20.x LTS + npm
- PM2 (latest)
- Certbot (Let's Encrypt)

**PHP Extensions (12 required):**
```
php8.1-cli php8.1-fpm php8.1-mysql php8.1-mbstring
php8.1-xml php8.1-curl php8.1-zip php8.1-gd
php8.1-bcmath php8.1-intl php8.1-tokenizer php8.1-json
```

---

## 📂 REPOSITORY

- **Backend**: https://github.com/dzikrirazzan/sigap_api (Laravel 10)
- **Frontend**: https://github.com/mariosianturi19/SIGAP-UNDIP (Next.js)

---

## 🌐 TARGET URL STRUCTURE

- **Backend API**: `https://api.<domain>`
- **Frontend**: `https://<domain>`
- **Frontend env**: `NEXT_PUBLIC_API_URL=https://api.<domain>`

---

## 🗄️ DATABASE SETUP

```sql
CREATE DATABASE sigap_emergency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sigap_user'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON sigap_emergency.* TO 'sigap_user'@'localhost';
FLUSH PRIVILEGES;
```

**Configuration:**
- Database: `sigap_emergency`
- User: `sigap_user` (ALL PRIVILEGES on this DB only)
- Charset/Collation: `utf8mb4` / `utf8mb4_unicode_ci`
- MySQL bind: `127.0.0.1` (NOT public)
- Timezone: `Asia/Jakarta`

---

## 📂 FILE STRUCTURE & PERMISSIONS

```bash
# Backend
/var/www/sigap-api/

# Frontend
/var/www/sigap-frontend/

# Permissions (Backend)
sudo chown -R www-data:www-data /var/www/sigap-api
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache
sudo chmod 600 /var/www/sigap-api/.env
```

---

## 🔧 BACKEND ENVIRONMENT (.env)

**Critical Keys:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.<domain>
APP_TIMEZONE=Asia/Jakarta

DB_HOST=127.0.0.1
DB_DATABASE=sigap_emergency
DB_USERNAME=sigap_user
DB_PASSWORD=<strong-password>

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=<postmark-token>
MAIL_PASSWORD=<postmark-token>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@<domain>

FONNTE_TOKEN=<fonnte-token>
FONNTE_BASE_URL=https://api.fonnte.com
```

---

## ⚙️ LARAVEL COMMANDS (Post-Deployment)

```bash
cd /var/www/sigap-api

# Generate key
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Run seeders (OPTIONAL - decide ON/OFF for production)
sudo -u www-data php artisan db:seed --force

# Storage link
sudo -u www-data php artisan storage:link

# Clear & cache
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 🔄 SUPERVISOR CONFIGURATION

### Queue Worker
**File**: `/etc/supervisor/conf.d/sigap-queue-worker.conf`
```ini
[program:sigap-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sigap-api/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sigap-api/storage/logs/queue-worker.log
```

### Scheduler
**File**: `/etc/supervisor/conf.d/sigap-scheduler.conf`
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

### Start Services
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sigap-queue-worker:*
sudo supervisorctl start sigap-scheduler:*
sudo supervisorctl status
```

---

## 🌐 NGINX CONFIGURATION

### Backend (api.domain.com)
**File**: `/etc/nginx/sites-available/sigap-api`
```nginx
server {
    listen 80;
    server_name api.<domain>;
    root /var/www/sigap-api/public;
    index index.php;
    
    client_max_body_size 20M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Frontend (domain.com)
**File**: `/etc/nginx/sites-available/sigap-frontend`
```nginx
server {
    listen 80;
    server_name <domain>;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Enable Sites & SSL
```bash
# Enable sites
sudo ln -s /etc/nginx/sites-available/sigap-api /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/sigap-frontend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# Install SSL
sudo certbot --nginx -d api.<domain>
sudo certbot --nginx -d <domain>
```

---

## 🎨 FRONTEND DEPLOYMENT

### Environment (.env.production)
```env
NEXT_PUBLIC_API_URL=https://api.<domain>
NODE_ENV=production
PORT=3000
```

### Build & Deploy
```bash
cd /var/www/sigap-frontend

# Install dependencies
npm install

# Build production
npm run build

# Start with PM2
pm2 start npm --name "sigap-frontend" -- start
pm2 save
pm2 startup
```

---

## 🔗 CORS CONFIGURATION

**Backend**: `config/cors.php`
```php
'allowed_origins' => [
    'https://<domain>',  // Frontend domain
],
'supports_credentials' => true,
```

---

## 🧪 VERIFICATION

```bash
# Backend health check
curl https://api.<domain>/api/health

# Frontend check
curl https://<domain>

# Services status
sudo systemctl status nginx php8.1-fpm mysql
sudo supervisorctl status
pm2 status
```

---

## 📋 DEPLOYMENT CHECKLIST

**Pre-Deployment:**
- [ ] Domain pointing to VPS IP
- [ ] Subdomain `api.<domain>` pointing to VPS IP
- [ ] Database password prepared
- [ ] Postmark API token ready
- [ ] Fonnte API token ready

**Installation:**
- [ ] All software installed
- [ ] PHP extensions installed
- [ ] Database created & user configured
- [ ] Both repositories cloned

**Backend:**
- [ ] .env configured
- [ ] APP_KEY generated
- [ ] Migrations run
- [ ] Seeders run (if needed)
- [ ] Storage link created
- [ ] Caches optimized
- [ ] Supervisor configured
- [ ] Permissions set

**Frontend:**
- [ ] .env.production configured
- [ ] Dependencies installed
- [ ] Production build created
- [ ] PM2 configured

**Web Server:**
- [ ] Nginx configured (both sites)
- [ ] SSL installed (both domains)
- [ ] CORS configured

**Testing:**
- [ ] Backend health check works
- [ ] Frontend loads
- [ ] Frontend → Backend connection works
- [ ] All services running

---

## 📞 SUPPORT

**Backend**: Dzikri Razzan - dzikrirazzan@students.undip.ac.id  
**Frontend**: Mario Sianturi - https://github.com/mariosianturi19

---

**Last Updated**: 2026-01-26
