# REQUIREMENT DEPLOYMENT SIGAP UNDIP

**Date**: 2026-01-26  
**Version**: 1.0

---

## 📦 REPOSITORY

### Backend (Laravel 10 API)
- **GitHub**: https://github.com/dzikrirazzan/sigap_api
- **Framework**: Laravel 10
- **PHP Version**: 8.1+

### Frontend (Next.js)
- **GitHub**: https://github.com/mariosianturi19/SIGAP-UNDIP
- **Framework**: Next.js
- **Node Version**: 20.x LTS

---

## 🎯 SCOPE PROYEK

1. **SIGAP Emergency API** (Laravel 10) - Backend REST API
2. **SIGAP UNDIP Frontend** (Next.js) - Frontend Web Application

---

## 🖥️ SERVER REQUIREMENTS

### VPS Specifications (Minimum)
- **OS**: Ubuntu 22.04.5 LTS (64-bit)
- **RAM**: 2GB (Recommended: 4GB)
- **CPU**: 2 vCPU (Recommended: 4 vCPU)
- **Storage**: 20GB SSD (Recommended: 40GB SSD)
- **IP**: Static Public IP
- **Bandwidth**: 1TB/month minimum

### Network Requirements
**Ports yang harus dibuka:**
- Port 22 (SSH)
- Port 80 (HTTP - redirect to HTTPS)
- Port 443 (HTTPS)
- Port 3306 (MySQL - localhost only, NOT public)

**DNS Requirements:**
- Domain harus sudah pointing ke IP VPS
- Subdomain `api.<domain>` untuk backend
- Domain utama `<domain>` untuk frontend

---

## 🏗️ TARGET ARSITEKTUR

### URL Structure
- **Backend API**: `https://api.<domain>` (Laravel 10)
- **Frontend**: `https://<domain>` (Next.js)
- **Frontend wajib mengarah ke URL backend production via `NEXT_PUBLIC_API_URL`**

### Architecture Flow
```
User → Frontend (Next.js) → Backend API (Laravel) → Database (MySQL)
```

---

## 📦 DEPENDENCIES & VERSIONS

### Backend (Laravel 10 API)

**Core Software:**
- PHP 8.1+
- PHP-FPM 8.1+
- Composer 2.x
- Nginx (latest stable)
- MySQL 8.0
- Supervisor (latest stable)
- Git (latest)
- Certbot (Let's Encrypt)

**PHP Extensions (12 extensions wajib):**
- `php8.1-cli`
- `php8.1-fpm`
- `php8.1-mysql`
- `php8.1-mbstring`
- `php8.1-xml`
- `php8.1-curl`
- `php8.1-zip`
- `php8.1-gd`
- `php8.1-bcmath`
- `php8.1-intl`
- `php8.1-tokenizer`
- `php8.1-json`

### Frontend (Next.js)

**Core Software:**
- Node.js 20.x LTS
- npm (bundled with Node.js)
- PM2 (latest) - untuk process management
- Nginx (latest stable) - untuk reverse proxy
- Certbot (Let's Encrypt) - untuk SSL

---

## 🔑 CREDENTIALS YANG HARUS DISIAPKAN

### 1. Database Credentials
- Database Name: `sigap_emergency`
- Database User: `sigap_user`
- Database Password: **[Strong password - min 16 characters]**

### 2. Email Service (Postmark SMTP)
- Postmark API Token: **[Your Postmark token]**
- From Email: `noreply@<your-domain>.com`

### 3. WhatsApp Notification (Fonnte)
- Fonnte API Token: **[Your Fonnte token]**

### 4. Firebase (Frontend)
- Firebase Storage Bucket: `seputipy.appspot.com`
- Firebase Config File: **[Jika ada firebase-config.json]**

### 5. SSL Certificate
- Email untuk Let's Encrypt: `admin@<your-domain>.com`

---

## 🗄️ DATABASE SETUP

### Database Configuration

**Database yang harus dibuat:**
```sql
CREATE DATABASE sigap_emergency 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'sigap_user'@'localhost' 
    IDENTIFIED BY 'YOUR_STRONG_PASSWORD';

GRANT ALL PRIVILEGES ON sigap_emergency.* 
    TO 'sigap_user'@'localhost';

FLUSH PRIVILEGES;
```

**Database Settings:**
- DB Name: `sigap_emergency`
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Timezone: `Asia/Jakarta (+07:00)`
- User: `sigap_user`
- Privileges: ALL hanya untuk `sigap_emergency.*`
- Security: MySQL bind ke `127.0.0.1` (tidak boleh public)

**Total Tables:** 16 tables (akan dibuat oleh migrations)

---

## 📂 FILE STRUCTURE & PERMISSIONS

### Backend Directory
```
/var/www/sigap-api/
```

### Frontend Directory
```
/var/www/sigap-frontend/
```

### File Permissions (Backend)
```bash
# Ownership
Owner: www-data
Group: www-data

# Permissions
Directories: 755
Files: 644
storage/: 775
bootstrap/cache/: 775
.env: 600
```

---

## ⚙️ BACKEND DEPLOYMENT COMMANDS

### 1. Generate Application Key
```bash
sudo -u www-data php artisan key:generate
```

### 2. Run Database Migrations
```bash
cd /var/www/sigap-api

# Run migrations
sudo -u www-data php artisan migrate --force

# Check migration status
sudo -u www-data php artisan migrate:status
```

### 3. Run Database Seeders
```bash
# Run all seeders
sudo -u www-data php artisan db:seed --force

# Or run specific seeders
sudo -u www-data php artisan db:seed --class=UserSeeder --force
sudo -u www-data php artisan db:seed --class=RelawanShiftPatternSeeder --force
```

**Seeder akan membuat:**
- 2 Admin accounts
- 6 Relawan accounts
- 6 User accounts
- Weekly shift patterns

**⚠️ PENTING: Ganti semua password default setelah deployment!**

### 4. Create Storage Link
```bash
sudo -u www-data php artisan storage:link
```

### 5. Optimize Laravel
```bash
# Cache configuration
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 🔄 SUPERVISOR CONFIGURATION

### Queue Worker Configuration
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

### Scheduler Configuration
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

### Start Supervisor Services
```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start queue workers
sudo supervisorctl start sigap-queue-worker:*

# Start scheduler
sudo supervisorctl start sigap-scheduler:*

# Check status
sudo supervisorctl status
```

---

## 🌐 FRONTEND DEPLOYMENT COMMANDS

### 1. Install Dependencies
```bash
cd /var/www/sigap-frontend
npm install
```

### 2. Build Production
```bash
npm run build
```

### 3. PM2 Configuration
```bash
# Start with PM2
pm2 start npm --name "sigap-frontend" -- start

# Save PM2 configuration
pm2 save

# Setup PM2 startup
pm2 startup
```

### 4. PM2 Ecosystem File (Recommended)
Create `ecosystem.config.js`:
```javascript
module.exports = {
  apps: [{
    name: 'sigap-frontend',
    script: 'npm',
    args: 'start',
    cwd: '/var/www/sigap-frontend',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    }
  }]
}
```

Then start:
```bash
pm2 start ecosystem.config.js
pm2 save
```

---

## 🌐 NGINX CONFIGURATION

### Backend (api.domain.com)
```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name api.your-domain.com;
    root /var/www/sigap-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf8mb4;
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

### Frontend (domain.com)
```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## 🔒 SSL CERTIFICATE

### Install SSL for Both Domains
```bash
# Backend
sudo certbot --nginx -d api.your-domain.com

# Frontend
sudo certbot --nginx -d your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## 🔧 BACKEND ENVIRONMENT (.env)

```env
APP_NAME="SIGAP Emergency API"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://api.your-domain.com

APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_emergency
DB_USERNAME=sigap_user
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=YOUR_POSTMARK_API_TOKEN
MAIL_PASSWORD=YOUR_POSTMARK_API_TOKEN
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SIGAP UNDIP"

FONNTE_TOKEN=YOUR_FONNTE_API_TOKEN
FONNTE_BASE_URL=https://api.fonnte.com

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

---

## 🎨 FRONTEND ENVIRONMENT (.env.production)

```env
NEXT_PUBLIC_API_URL=https://api.your-domain.com
NODE_ENV=production
PORT=3000

NEXT_PUBLIC_ENABLE_AUTO_REFRESH=true
NEXT_PUBLIC_REFRESH_INTERVAL=30000
NEXT_PUBLIC_ENABLE_LOGS=false

NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET=seputipy.appspot.com

NEXT_PUBLIC_TOKEN_EXPIRY_WARNING=300
NEXT_PUBLIC_MAX_FILE_SIZE=20
NEXT_PUBLIC_LOCATION_TIMEOUT=10000
NEXT_PUBLIC_LOCATION_MAX_AGE=300000
NEXT_PUBLIC_LOCATION_MIN_ACCURACY=1000
```

---

## 🔗 CORS CONFIGURATION (CRITICAL!)

### Backend CORS Setup

**File**: `config/cors.php`

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'https://your-domain.com',  // Frontend production URL
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];
```

**ATAU via .env (jika ada):**
```env
CORS_ALLOWED_ORIGINS=https://your-domain.com
```

**Frontend harus bisa akses Backend:**
- Frontend domain: `https://<domain>`
- Backend harus allow origin tersebut di CORS configuration

---

## 🧪 TESTING & VERIFICATION

### Backend Testing

**1. Health Check**
```bash
curl https://api.your-domain.com/api/health
```
Expected response:
```json
{
    "status": "ok",
    "service": "SIGAP Emergency API",
    "timestamp": "...",
    "version": "1.0.0"
}
```

**2. Test Login**
```bash
curl -X POST https://api.your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com","password":"kikipoiu"}'
```

**3. Test Database Connection**
```bash
sudo -u www-data php artisan tinker
# In tinker: DB::connection()->getPdo();
```

### Frontend Testing

**1. Check PM2 Status**
```bash
pm2 status
pm2 logs sigap-frontend
```

**2. Test Frontend URL**
```bash
curl https://your-domain.com
```

**3. Test Frontend → Backend Connection**
- Open browser: `https://your-domain.com`
- Try login
- Check browser console for CORS errors

### Services Verification

```bash
# Check all services
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
sudo supervisorctl status
pm2 status
```

---

## 📋 DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] VPS ready (Ubuntu 22.04.5, 2GB+ RAM)
- [ ] Domain pointing ke IP VPS
- [ ] Subdomain `api.<domain>` pointing ke IP VPS
- [ ] SSH access available
- [ ] Database password prepared (strong)
- [ ] Postmark API token ready
- [ ] Fonnte API token ready

### Software Installation
- [ ] PHP 8.1 + 12 extensions
- [ ] Composer 2.x
- [ ] MySQL 8.0
- [ ] Nginx
- [ ] Supervisor
- [ ] Node.js 20.x + npm
- [ ] PM2
- [ ] Certbot

### Backend Configuration
- [ ] Database created
- [ ] Database user created
- [ ] .env configured
- [ ] APP_KEY generated
- [ ] Migrations run
- [ ] Seeders run
- [ ] Storage link created
- [ ] Caches optimized
- [ ] Nginx configured
- [ ] SSL installed
- [ ] Supervisor configured
- [ ] File permissions set

### Frontend Configuration
- [ ] .env.production configured
- [ ] Dependencies installed
- [ ] Production build created
- [ ] PM2 configured
- [ ] Nginx configured
- [ ] SSL installed

### CORS & Integration
- [ ] CORS configured di backend
- [ ] Frontend bisa akses backend
- [ ] No CORS errors di browser console

### Testing
- [ ] Backend health check works
- [ ] Backend login works
- [ ] Frontend loads
- [ ] Frontend → Backend connection works
- [ ] Queue workers running
- [ ] Scheduler running
- [ ] PM2 running

### Security
- [ ] Default passwords changed
- [ ] .env permissions 600
- [ ] Firewall configured
- [ ] HTTPS working (both domains)
- [ ] MySQL localhost only

---

## 📞 SUPPORT

**Developer Backend**: Dzikri Razzan  
**Email**: dzikrirazzan@students.undip.ac.id

**Developer Frontend**: Mario Sianturi  
**GitHub**: https://github.com/mariosianturi19

---

**Version**: 1.0.0  
**Last Updated**: 2026-01-26
