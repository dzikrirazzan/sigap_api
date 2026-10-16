# 📋 SYSTEM REQUIREMENTS - SIGAP Emergency API

**Project**: SIGAP Emergency API  
**Framework**: Laravel 10.x  
**PHP Version**: 8.1+  
**Date**: 2026-01-26

---

## 🖥️ SERVER SPECIFICATIONS

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **Operating System** | Ubuntu 22.04.5 LTS (64-bit) | Ubuntu 22.04.5 LTS |
| **RAM** | 2GB | 4GB |
| **Storage** | 20GB SSD | 40GB SSD |
| **CPU** | 2 vCPU | 4 vCPU |
| **Bandwidth** | 1TB/month | Unlimited |
| **IP Address** | Static Public IP | Static Public IP |

---

## 🔌 NETWORK REQUIREMENTS

### Required Ports:
- **22** - SSH Access
- **80** - HTTP (redirect to HTTPS)
- **443** - HTTPS
- **3306** - MySQL (localhost only)

### DNS:
- Domain harus pointing ke IP VPS
- Contoh: `api.sigap-undip.com` → `123.456.789.012`

---

## 📦 SOFTWARE DEPENDENCIES

### Core Software:
1. **PHP 8.1** atau lebih tinggi
2. **PHP-FPM 8.1**
3. **Composer 2.x**
4. **MySQL 8.0** atau **MariaDB 10.6+**
5. **Nginx** (latest stable)
6. **Supervisor**
7. **Node.js 18.x LTS**
8. **NPM** (included with Node.js)
9. **Certbot** (Let's Encrypt)
10. **Git**

### Optional:
- **Redis** (untuk caching - optional, bisa pakai database)

---

## 🔧 PHP EXTENSIONS (REQUIRED)

| Extension | Version |
|-----------|---------|
| `php8.1-fpm` | 8.1+ |
| `php8.1-cli` | 8.1+ |
| `php8.1-mysql` | 8.1+ |
| `php8.1-mbstring` | 8.1+ |
| `php8.1-xml` | 8.1+ |
| `php8.1-curl` | 8.1+ |
| `php8.1-zip` | 8.1+ |
| `php8.1-gd` | 8.1+ |
| `php8.1-bcmath` | 8.1+ |
| `php8.1-json` | 8.1+ |
| `php8.1-intl` | 8.1+ |
| `php8.1-tokenizer` | 8.1+ |

**Total**: 12 PHP Extensions

---

## 🗄️ DATABASE REQUIREMENTS

### MySQL/MariaDB Configuration:
```
Database Name:     sigap_emergency
Character Set:     utf8mb4
Collation:         utf8mb4_unicode_ci
Timezone:          Asia/Jakarta (+07:00)
```

### Database User:
```
Username:          sigap_user (atau custom)
Password:          [Strong password required]
Privileges:        ALL PRIVILEGES on sigap_emergency.*
```

### Database Tables (16 tables):
1. `users`
2. `password_reset_tokens`
3. `failed_jobs`
4. `personal_access_tokens` (Laravel Sanctum)
5. `refresh_tokens`
6. `reports` (laporan foto)
7. `sessions`
8. `panic_reports`
9. `relawan_shifts`
10. `jobs` (queue)
11. `job_batches`
12. `relawan_shift_patterns`
13. `email_otps`
14. `cache`
15. `cache_locks`
16. `migrations`

### Estimated Database Size:
- Initial: ~5MB (with seeders)
- Monthly Growth: ~10-50MB (depending on usage)

---

## 🔑 CREDENTIALS & API KEYS

### 1. Database Credentials
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_emergency
DB_USERNAME=sigap_user
DB_PASSWORD=[Your Strong Password]
```

### 2. Email Service (Postmark SMTP)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=[Postmark API Token]
MAIL_PASSWORD=[Postmark API Token]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SIGAP UNDIP"
```

### 3. WhatsApp Notification (Fonnte API)
```
FONNTE_TOKEN=[Your Fonnte API Token]
FONNTE_BASE_URL=https://api.fonnte.com
```

### 4. Application Keys
```
APP_KEY=[32 character random string - auto generated]
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.your-domain.com
```

### 5. Domain & SSL
```
Domain Name:       api.your-domain.com
SSL Email:         admin@your-domain.com
SSL Provider:      Let's Encrypt (Free)
```

---

## 📂 DIRECTORY STRUCTURE

### Application Root:
```
/var/www/sigap-api/
```

### Required Writable Directories:
```
/var/www/sigap-api/storage/          (Permission: 775)
/var/www/sigap-api/bootstrap/cache/  (Permission: 775)
```

### File Ownership:
```
Owner:  www-data
Group:  www-data
```

### File Permissions:
```
Directories:  755
Files:        644
Storage:      775
.env file:    600
```

---

## ⚙️ BACKGROUND SERVICES

### 1. Queue Workers (Supervisor)
```
Service Name:      sigap-queue-worker
Process Count:     2 workers
Command:           php artisan queue:work database
User:              www-data
Auto Start:        Yes
Auto Restart:      Yes
Log File:          /var/www/sigap-api/storage/logs/queue-worker.log
```

### 2. Task Scheduler (Supervisor)
```
Service Name:      sigap-scheduler
Command:           php artisan schedule:run (every minute)
User:              www-data
Auto Start:        Yes
Auto Restart:      Yes
Log File:          /var/www/sigap-api/storage/logs/scheduler.log
```

### 3. Scheduled Tasks:
```
Task:              Auto-generate shifts
Schedule:          Daily at 02:00 WIB
Duration:          Generate 7 days ahead
Log File:          /var/www/sigap-api/storage/logs/auto-shift-generation.log
```

---

## 🔒 SECURITY REQUIREMENTS

### Firewall (UFW):
```
Port 22:   Allowed (SSH)
Port 80:   Allowed (HTTP)
Port 443:  Allowed (HTTPS)
Port 3306: Blocked from public (MySQL localhost only)
```

### SSL/TLS:
```
Protocol:          TLS 1.2+
Certificate:       Let's Encrypt (Free, Auto-renewal)
HTTPS:             Required (HTTP redirects to HTTPS)
```

### Application Security:
```
APP_DEBUG:         false (production)
APP_ENV:           production
Strong Passwords:  Required for all accounts
.env Permissions:  600 (read/write owner only)
```

---

## 📊 RESOURCE USAGE ESTIMATES

### CPU Usage:
```
Idle:         5-10%
Normal Load:  20-40%
Peak Load:    60-80%
```

### Memory Usage:
```
Base System:  ~500MB
PHP-FPM:      ~200-400MB
MySQL:        ~300-500MB
Nginx:        ~50-100MB
Total:        ~1.5-2GB
```

### Disk Space:
```
Application:  ~100MB
Dependencies: ~50MB
Database:     ~5-50MB
Logs:         ~10-100MB (with rotation)
Backups:      ~50-500MB (depending on retention)
Total:        ~500MB - 1GB
```

### Bandwidth:
```
API Request:  ~1-5KB per request
Image Upload: ~500KB - 2MB per upload
Monthly:      ~10-50GB (depending on usage)
```

---

## 🔄 LARAVEL ARTISAN COMMANDS

### Required Commands (Deployment):
```
php artisan key:generate          # Generate APP_KEY
php artisan migrate --force       # Run database migrations
php artisan db:seed --force       # Seed initial data
php artisan storage:link          # Create storage symlink
php artisan config:cache          # Cache configuration
php artisan route:cache           # Cache routes
php artisan view:cache            # Cache views
```

### Seeders:
```
UserSeeder                        # Create admin, relawan, user accounts
RelawanShiftPatternSeeder         # Create weekly shift patterns
```

---

## 👥 INITIAL SEEDER DATA

### Admin Accounts (2):
```
admin@gmail.com          / kikipoiu
admindzikri@gmail.com    / password
```

### Relawan Accounts (6):
```
relawan@gmail.com        / kikipoiu
relawan1@gmail.com       / kikipoiu
relawan2@gmail.com       / kikipoiu
relawan3@gmail.com       / kikipoiu
relawan4@gmail.com       / kikipoiu
relawan5@gmail.com       / kikipoiu
```

### User Accounts (6):
```
user@gmail.com           / kikipoiu
user1@gmail.com          / kikipoiu
user2@gmail.com          / kikipoiu
user3@gmail.com          / kikipoiu
user4@gmail.com          / kikipoiu
user5@gmail.com          / kikipoiu
```

**⚠️ CRITICAL**: Ganti semua password default setelah deployment!

---

## 🌐 WEB SERVER CONFIGURATION

### Nginx:
```
Document Root:         /var/www/sigap-api/public
Server Name:           api.your-domain.com
Upload Max Size:       20MB
FastCGI Socket:        unix:/var/run/php/php8.1-fpm.sock
```

### PHP-FPM:
```
Memory Limit:          256M
Upload Max Filesize:   20M
Post Max Size:         20M
Timezone:              Asia/Jakarta
Error Reporting:       Off (production)
Log Errors:            On
```

---

## 📝 ENVIRONMENT VARIABLES (.env)

### Required Variables:
```
APP_NAME
APP_ENV
APP_KEY
APP_DEBUG
APP_URL
APP_TIMEZONE

DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

SESSION_DRIVER
CACHE_STORE
QUEUE_CONNECTION

MAIL_MAILER
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_ENCRYPTION
MAIL_FROM_ADDRESS
MAIL_FROM_NAME

FONNTE_TOKEN
FONNTE_BASE_URL
```

---

## 🧪 API ENDPOINTS (Testing)

### Health Check:
```
GET /api/health
Response: {"status":"ok","service":"SIGAP Emergency API","timestamp":"...","version":"1.0.0"}
```

### Authentication:
```
POST /api/login
POST /api/register
POST /api/logout
GET  /api/profile
```

### Main Features:
```
POST /api/panic                    # Panic button
GET  /api/panic/today              # Today's panic reports
POST /api/reports                  # Create report
GET  /api/reports                  # List reports
```

---

## 📦 COMPOSER DEPENDENCIES

### Production Dependencies (composer.json):
```
php: ^8.1
laravel/framework: ^10.0
laravel/sanctum: ^3.2
laravel/tinker: ^2.8
guzzlehttp/guzzle: ^7.2
```

### Dev Dependencies:
```
fakerphp/faker: ^1.9.1
laravel/pint: ^1.0
laravel/sail: ^1.18
mockery/mockery: ^1.4.4
nunomaduro/collision: ^7.0
phpunit/phpunit: ^10.0
spatie/laravel-ignition: ^2.0
```

---

## 📋 DEPLOYMENT CHECKLIST

### Pre-Deployment:
- [ ] VPS ready (Ubuntu 22.04.5)
- [ ] Domain pointing to IP
- [ ] SSH access available
- [ ] Database credentials prepared
- [ ] Email credentials (Postmark) prepared
- [ ] WhatsApp API token (Fonnte) prepared

### Software Installation:
- [ ] PHP 8.1 + 12 extensions
- [ ] Composer 2.x
- [ ] MySQL 8.0 / MariaDB 10.6+
- [ ] Nginx
- [ ] Supervisor
- [ ] Node.js 18.x + NPM
- [ ] Certbot

### Configuration:
- [ ] Database created
- [ ] Database user created
- [ ] .env file configured
- [ ] File permissions set
- [ ] Nginx virtual host configured
- [ ] SSL certificate installed
- [ ] Supervisor workers configured
- [ ] Firewall configured

### Laravel Setup:
- [ ] Migrations run
- [ ] Seeders run
- [ ] Storage link created
- [ ] Caches optimized

### Verification:
- [ ] Health check endpoint works
- [ ] Login endpoint works
- [ ] Queue workers running
- [ ] Scheduler running
- [ ] HTTPS working
- [ ] Default passwords changed

---

## 📞 SUPPORT

**Developer**: Dzikri Razzan  
**Email**: dzikrirazzan@students.undip.ac.id

---

**Version**: 1.0.0  
**Last Updated**: 2026-01-26
