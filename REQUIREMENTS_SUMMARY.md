# 📊 REQUIREMENTS SUMMARY - SIGAP Emergency API VPS Deployment

**Document Version**: 1.0.0  
**Date**: 2026-01-26  
**Project**: SIGAP Emergency API  
**Framework**: Laravel 10.x  
**PHP Version**: 8.1+

---

## 🖥️ SERVER SPECIFICATIONS

| Component | Requirement | Recommended |
|-----------|-------------|-------------|
| **Operating System** | Ubuntu 22.04.5 LTS (64-bit) | Ubuntu 22.04.5 LTS |
| **RAM** | 2GB minimum | 4GB |
| **Storage** | 20GB SSD | 40GB SSD |
| **CPU** | 2 vCPU | 4 vCPU |
| **Bandwidth** | 1TB/month | Unlimited |
| **IP Address** | Static Public IP | Static Public IP |

---

## 🔌 NETWORK REQUIREMENTS

### Required Open Ports:
- **Port 22**: SSH Access
- **Port 80**: HTTP (will redirect to HTTPS)
- **Port 443**: HTTPS (Primary)
- **Port 3306**: MySQL (localhost only, not public)

### DNS Configuration:
- Domain harus sudah pointing ke IP VPS
- Contoh: `api.sigap-undip.com` → `123.456.789.012`

---

## 📦 SOFTWARE STACK

### 1. Web Server
- **Nginx** (latest stable version)
- Purpose: Reverse proxy dan static file serving

### 2. Application Runtime
- **PHP 8.1** atau lebih tinggi
- **PHP-FPM** untuk process management

### 3. Database
- **MySQL 8.0** atau **MariaDB 10.6+**
- Character Set: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`

### 4. Process Manager
- **Supervisor** untuk queue workers dan scheduler

### 5. Package Managers
- **Composer 2.x** (PHP dependency manager)
- **Node.js 18.x LTS** + **NPM** (untuk asset building)

### 6. SSL Certificate
- **Let's Encrypt** via **Certbot**

### 7. Optional (Recommended)
- **Redis** untuk caching dan queue (optional, bisa pakai database)

---

## 🔧 PHP EXTENSIONS (REQUIRED)

Aplikasi membutuhkan PHP extensions berikut:

| Extension | Purpose |
|-----------|---------|
| `php8.1-fpm` | FastCGI Process Manager |
| `php8.1-cli` | Command Line Interface |
| `php8.1-mysql` | MySQL/MariaDB Database Driver |
| `php8.1-mbstring` | Multibyte String Functions |
| `php8.1-xml` | XML Processing |
| `php8.1-curl` | HTTP Client (untuk API calls) |
| `php8.1-zip` | ZIP Archive Handling |
| `php8.1-gd` | Image Processing (untuk upload foto) |
| `php8.1-bcmath` | Precision Mathematics |
| `php8.1-json` | JSON Processing |
| `php8.1-intl` | Internationalization |
| `php8.1-tokenizer` | Token Parsing (Laravel Sanctum) |

---

## 🗄️ DATABASE REQUIREMENTS

### Database Configuration:
```
Database Name: sigap_emergency
Database User: sigap_user (atau custom)
Database Password: [Strong password required]
Character Set: utf8mb4
Collation: utf8mb4_unicode_ci
```

### Estimated Database Size:
- Initial: ~5MB (with seeders)
- Growth: ~10-50MB per month (depending on usage)

### Tables Created (16 migrations):
1. users
2. password_reset_tokens
3. failed_jobs
4. personal_access_tokens (Sanctum)
5. refresh_tokens
6. reports (laporan foto)
7. sessions
8. panic_reports
9. relawan_shifts
10. jobs (queue)
11. job_batches
12. relawan_shift_patterns
13. email_otps
14. cache
15. cache_locks
16. migrations

---

## 🔐 CREDENTIALS & API KEYS NEEDED

### 1. Database Credentials
- Database Host
- Database Name
- Database Username
- Database Password

### 2. Email Service (Postmark SMTP)
- SMTP Host: `smtp.postmarkapp.com`
- SMTP Port: `587`
- SMTP Username: [Postmark Token]
- SMTP Password: [Postmark Token]
- From Email Address
- From Name

### 3. WhatsApp Notification (Fonnte)
- Fonnte API Token
- Fonnte Base URL: `https://api.fonnte.com`

### 4. Domain/SSL
- Domain name (e.g., api.sigap-undip.com)
- Email for Let's Encrypt notifications

---

## 📂 DIRECTORY STRUCTURE & PERMISSIONS

### Application Directory:
```
/var/www/sigap-api/
```

### Required Writable Directories:
```
/var/www/sigap-api/storage/          (775)
/var/www/sigap-api/bootstrap/cache/  (775)
```

### Owner/Group:
- Owner: `www-data`
- Group: `www-data`

---

## ⚙️ BACKGROUND PROCESSES

### 1. Queue Workers
- **Purpose**: Process background jobs (email, notifications)
- **Process Manager**: Supervisor
- **Number of Workers**: 2
- **Command**: `php artisan queue:work database`

### 2. Task Scheduler
- **Purpose**: Run scheduled tasks (auto-generate shifts)
- **Process Manager**: Supervisor
- **Schedule**: Every minute (Laravel scheduler)
- **Command**: `php artisan schedule:run`

### 3. Scheduled Tasks:
- **Auto-Generate Shifts**: Daily at 02:00 WIB
  - Generates shifts for next 7 days based on weekly patterns
  - Log: `storage/logs/auto-shift-generation.log`

---

## 🔄 DEPLOYMENT COMMANDS SUMMARY

### Initial Deployment:
```bash
1. composer install --optimize-autoloader --no-dev
2. php artisan key:generate
3. php artisan migrate --force
4. php artisan db:seed --force
5. php artisan storage:link
6. php artisan config:cache
7. php artisan route:cache
8. php artisan view:cache
```

### Update/Redeploy:
```bash
1. php artisan down
2. git pull origin main
3. composer install --optimize-autoloader --no-dev
4. php artisan migrate --force
5. php artisan config:cache
6. php artisan route:cache
7. php artisan view:cache
8. supervisorctl restart all
9. systemctl restart php8.1-fpm
10. php artisan up
```

---

## 📊 RESOURCE USAGE ESTIMATES

### CPU Usage:
- Idle: ~5-10%
- Normal Load: ~20-40%
- Peak Load: ~60-80%

### Memory Usage:
- Base System: ~500MB
- PHP-FPM: ~200-400MB
- MySQL: ~300-500MB
- Nginx: ~50-100MB
- **Total Estimated**: ~1.5-2GB

### Disk Space:
- Application Code: ~100MB
- Vendor Dependencies: ~50MB
- Database: ~5-50MB
- Logs: ~10-100MB (with rotation)
- Backups: ~50-500MB (depending on retention)
- **Total Estimated**: ~500MB - 1GB

### Bandwidth:
- API Requests: ~1-5KB per request
- Image Uploads: ~500KB - 2MB per upload
- **Estimated Monthly**: 10-50GB (depending on usage)

---

## 🔒 SECURITY REQUIREMENTS

### Firewall:
- Only ports 22, 80, 443 open to public
- MySQL port 3306 only accessible from localhost

### SSL/TLS:
- HTTPS required (HTTP redirects to HTTPS)
- Let's Encrypt certificate (free)
- Auto-renewal configured

### Application Security:
- `APP_DEBUG=false` in production
- Strong `APP_KEY` (32 characters)
- Strong database passwords
- `.env` file permissions: 600 (not readable by others)

### File Permissions:
- Application files: 644 (files) / 755 (directories)
- Storage directory: 775
- `.env` file: 600

---

## 📝 SEEDER DATA (Initial Users)

Aplikasi akan membuat user-user berikut saat seeding:

### Admin (2 users):
- `admin@gmail.com` / `kikipoiu`
- `admindzikri@gmail.com` / `password`

### Relawan (6 users):
- `relawan@gmail.com` / `kikipoiu`
- `relawan1@gmail.com` / `kikipoiu`
- `relawan2@gmail.com` / `kikipoiu`
- `relawan3@gmail.com` / `kikipoiu`
- `relawan4@gmail.com` / `kikipoiu`
- `relawan5@gmail.com` / `kikipoiu`

### Regular Users (6 users):
- `user@gmail.com` / `kikipoiu`
- `user1@gmail.com` / `kikipoiu`
- `user2@gmail.com` / `kikipoiu`
- `user3@gmail.com` / `kikipoiu`
- `user4@gmail.com` / `kikipoiu`
- `user5@gmail.com` / `kikipoiu`

**⚠️ CRITICAL: Ganti semua password default setelah deployment!**

---

## 🧪 TESTING ENDPOINTS

### Health Check:
```
GET https://your-domain.com/api/health
```
Expected Response:
```json
{
    "status": "ok",
    "service": "SIGAP Emergency API",
    "timestamp": "2026-01-26T10:20:00+07:00",
    "version": "1.0.0"
}
```

### Login Test:
```
POST https://your-domain.com/api/login
Content-Type: application/json

{
    "email": "admin@gmail.com",
    "password": "kikipoiu"
}
```

---

## 📞 SUPPORT & DOCUMENTATION

### Documentation Files:
1. **VPS_DEPLOYMENT_GUIDE.md** - Full deployment guide (detailed)
2. **QUICK_DEPLOYMENT.md** - Quick reference commands
3. **DEPLOYMENT_CHECKLIST.md** - Printable checklist
4. **REQUIREMENTS_SUMMARY.md** - This file

### Contact:
- **Developer**: Dzikri Razzan
- **Email**: dzikrirazzan@students.undip.ac.id

---

## 📋 QUICK CHECKLIST FOR TIM DEPLOYMENT

**Sebelum Deployment:**
- [ ] VPS dengan spesifikasi minimal sudah ready
- [ ] Domain sudah pointing ke IP VPS
- [ ] Akses SSH sudah tersedia
- [ ] Database credentials sudah disiapkan
- [ ] Email SMTP credentials (Postmark) sudah disiapkan
- [ ] WhatsApp API token (Fonnte) sudah disiapkan

**Software yang Harus Diinstall:**
- [ ] PHP 8.1 + semua extensions yang diperlukan
- [ ] Composer 2.x
- [ ] MySQL 8.0 / MariaDB 10.6+
- [ ] Nginx
- [ ] Supervisor
- [ ] Node.js 18.x + NPM
- [ ] Certbot (untuk SSL)

**Konfigurasi yang Harus Dilakukan:**
- [ ] Database dibuat dan user di-setup
- [ ] Nginx virtual host configured
- [ ] SSL certificate installed
- [ ] Supervisor workers configured
- [ ] Firewall (UFW) configured
- [ ] File permissions di-set dengan benar

**Setelah Deployment:**
- [ ] Test health check endpoint
- [ ] Test login endpoint
- [ ] Verify queue workers running
- [ ] Verify scheduler running
- [ ] Ganti semua default passwords
- [ ] Setup backup automation

---

## 💡 TIPS UNTUK TIM DEPLOYMENT

1. **Gunakan user `www-data`** untuk semua operasi artisan:
   ```bash
   sudo -u www-data php artisan [command]
   ```

2. **Selalu clear cache** setelah update:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **Monitor logs** saat deployment:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Backup database** sebelum migration:
   ```bash
   mysqldump -u user -p database > backup.sql
   ```

5. **Test di browser** setelah deployment:
   - Health check: `https://domain.com/api/health`
   - Pastikan HTTPS working
   - Pastikan no mixed content warnings

---

**End of Requirements Summary**

Untuk detail lengkap, silakan lihat file **VPS_DEPLOYMENT_GUIDE.md**
