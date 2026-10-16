# ✅ DEPLOYMENT CHECKLIST - SIGAP Emergency API

**Project**: SIGAP Emergency API  
**Target Server**: Ubuntu 22.04.5 LTS  
**Date**: _______________  
**Deployed By**: _______________

---

## 📋 PRE-DEPLOYMENT REQUIREMENTS

### Server Specifications
- [ ] VPS dengan Ubuntu 22.04.5 LTS (64-bit)
- [ ] Minimal 2GB RAM (Rekomendasi: 4GB)
- [ ] Minimal 20GB SSD Storage
- [ ] Minimal 2 vCPU
- [ ] Static Public IP Address
- [ ] Domain sudah pointing ke IP VPS

### Access & Credentials
- [ ] SSH access ke VPS tersedia
- [ ] Root atau sudo access tersedia
- [ ] Database credentials disiapkan
- [ ] SMTP credentials (Postmark) disiapkan
- [ ] WhatsApp API token (Fonnte) disiapkan
- [ ] Repository access (GitHub/GitLab) tersedia

---

## 🔧 SYSTEM INSTALLATION

### Base System
- [ ] System update: `sudo apt update && sudo apt upgrade -y`
- [ ] Essential tools installed: `software-properties-common curl wget git unzip`

### PHP & Extensions
- [ ] PHP 8.1 installed
- [ ] php8.1-fpm installed
- [ ] php8.1-mysql installed
- [ ] php8.1-mbstring installed
- [ ] php8.1-xml installed
- [ ] php8.1-curl installed
- [ ] php8.1-zip installed
- [ ] php8.1-gd installed
- [ ] php8.1-bcmath installed
- [ ] php8.1-json installed
- [ ] php8.1-intl installed
- [ ] php8.1-tokenizer installed
- [ ] PHP version verified: `php -v`

### Composer
- [ ] Composer installed
- [ ] Composer moved to `/usr/local/bin/composer`
- [ ] Composer executable: `chmod +x`
- [ ] Composer version verified: `composer --version`

### Database (MySQL)
- [ ] MySQL Server installed
- [ ] MySQL secure installation completed
- [ ] Database `sigap_emergency` created
- [ ] Database user `sigap_user` created
- [ ] Privileges granted to user
- [ ] Database connection tested

### Web Server (Nginx)
- [ ] Nginx installed
- [ ] Nginx started and enabled
- [ ] Nginx status verified: `systemctl status nginx`

### Process Manager (Supervisor)
- [ ] Supervisor installed
- [ ] Supervisor started and enabled
- [ ] Supervisor status verified

### Node.js & NPM
- [ ] Node.js 18.x installed
- [ ] NPM installed
- [ ] Versions verified: `node -v && npm -v`

### SSL Certificate (Certbot)
- [ ] Certbot installed
- [ ] python3-certbot-nginx installed

---

## 📁 APPLICATION SETUP

### Repository & Files
- [ ] Repository cloned to `/var/www/sigap-api`
- [ ] Ownership set to `www-data:www-data`
- [ ] Base permissions set: `chmod -R 755`

### Dependencies
- [ ] Composer dependencies installed: `composer install --optimize-autoloader --no-dev`
- [ ] NPM dependencies installed (if needed): `npm install`
- [ ] Assets built (if needed): `npm run build`

### Environment Configuration
- [ ] `.env` file created from `.env.example`
- [ ] `APP_NAME` configured
- [ ] `APP_ENV` set to `production`
- [ ] `APP_DEBUG` set to `false`
- [ ] `APP_URL` configured with domain
- [ ] `APP_KEY` generated: `php artisan key:generate`
- [ ] Database credentials configured
- [ ] Mail credentials configured
- [ ] Fonnte credentials configured
- [ ] All required env variables filled

### Permissions
- [ ] Storage directory writable: `chmod -R 775 storage`
- [ ] Bootstrap/cache writable: `chmod -R 775 bootstrap/cache`
- [ ] Storage ownership: `chgrp -R www-data storage`
- [ ] Bootstrap/cache ownership: `chgrp -R www-data bootstrap/cache`
- [ ] `.env` file secured: `chmod 600 .env`

### Database Setup
- [ ] Migrations executed: `php artisan migrate --force`
- [ ] UserSeeder executed: `php artisan db:seed --class=UserSeeder --force`
- [ ] RelawanShiftPatternSeeder executed: `php artisan db:seed --class=RelawanShiftPatternSeeder --force`
- [ ] Database seeding verified

### Laravel Configuration
- [ ] Storage link created: `php artisan storage:link`
- [ ] Config cached: `php artisan config:cache`
- [ ] Routes cached: `php artisan route:cache`
- [ ] Views cached: `php artisan view:cache`

---

## 🌐 WEB SERVER CONFIGURATION

### Nginx Setup
- [ ] Virtual host file created: `/etc/nginx/sites-available/sigap-api`
- [ ] Document root set to `/var/www/sigap-api/public`
- [ ] Server name configured with domain
- [ ] Upload size limit set: `client_max_body_size 20M`
- [ ] PHP-FPM socket configured
- [ ] Symbolic link created: `/etc/nginx/sites-enabled/sigap-api`
- [ ] Nginx config tested: `nginx -t`
- [ ] Nginx restarted

### SSL Certificate
- [ ] SSL certificate obtained: `certbot --nginx -d domain.com`
- [ ] HTTPS redirect enabled
- [ ] SSL certificate verified in browser
- [ ] Auto-renewal tested: `certbot renew --dry-run`

### PHP-FPM Configuration
- [ ] Memory limit set: `memory_limit = 256M`
- [ ] Upload size configured: `upload_max_filesize = 20M`
- [ ] Post size configured: `post_max_size = 20M`
- [ ] Timezone set: `date.timezone = Asia/Jakarta`
- [ ] Error logging configured for production
- [ ] PHP-FPM restarted

---

## ⚙️ BACKGROUND SERVICES

### Queue Worker (Supervisor)
- [ ] Config file created: `/etc/supervisor/conf.d/sigap-queue-worker.conf`
- [ ] Process name configured
- [ ] Command configured: `queue:work database`
- [ ] User set to `www-data`
- [ ] Number of processes set: `numprocs=2`
- [ ] Log file configured
- [ ] Auto-start enabled
- [ ] Auto-restart enabled

### Scheduler (Supervisor)
- [ ] Config file created: `/etc/supervisor/conf.d/sigap-scheduler.conf`
- [ ] Command configured: `schedule:run`
- [ ] User set to `www-data`
- [ ] Log file configured
- [ ] Auto-start enabled
- [ ] Auto-restart enabled

### Supervisor Services
- [ ] Supervisor config reloaded: `supervisorctl reread`
- [ ] Supervisor updated: `supervisorctl update`
- [ ] Queue workers started
- [ ] Scheduler started
- [ ] All services status verified: `supervisorctl status`

---

## 🔒 SECURITY CONFIGURATION

### Firewall (UFW)
- [ ] UFW enabled
- [ ] SSH port allowed: `ufw allow 22/tcp`
- [ ] HTTP port allowed: `ufw allow 80/tcp`
- [ ] HTTPS port allowed: `ufw allow 443/tcp`
- [ ] Firewall status verified: `ufw status`

### Application Security
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production` set
- [ ] Strong `APP_KEY` generated
- [ ] Strong database password used
- [ ] `.env` file permissions: `600`
- [ ] API tokens secured

### Database Security
- [ ] MySQL only listening on localhost
- [ ] Strong database password used
- [ ] Unnecessary MySQL users removed
- [ ] MySQL root password set

---

## 🧪 TESTING & VERIFICATION

### Basic Functionality
- [ ] Health check endpoint works: `/api/health`
- [ ] Returns correct JSON response
- [ ] HTTP status 200

### Database Connection
- [ ] Database connection tested via tinker
- [ ] No connection errors
- [ ] Tables created successfully
- [ ] Seeded data exists

### API Endpoints
- [ ] Login endpoint works: `POST /api/login`
- [ ] Returns access token
- [ ] Profile endpoint works with token: `GET /api/profile`
- [ ] Returns user data

### Background Services
- [ ] Queue workers running
- [ ] Scheduler running
- [ ] No errors in supervisor logs
- [ ] Jobs processing correctly

### File Uploads
- [ ] Storage directory accessible
- [ ] File uploads working
- [ ] Uploaded files accessible via URL

### Email & Notifications
- [ ] Email sending tested
- [ ] OTP emails received
- [ ] WhatsApp notifications working (if configured)

### SSL/HTTPS
- [ ] HTTPS working
- [ ] Certificate valid
- [ ] HTTP redirects to HTTPS
- [ ] No mixed content warnings

---

## 📊 MONITORING & LOGGING

### Log Files
- [ ] Laravel logs accessible: `storage/logs/laravel.log`
- [ ] Queue worker logs accessible
- [ ] Scheduler logs accessible
- [ ] Nginx access logs accessible
- [ ] Nginx error logs accessible
- [ ] PHP-FPM logs accessible

### Monitoring Setup
- [ ] Log rotation configured
- [ ] Disk space monitoring setup
- [ ] Memory usage monitoring setup
- [ ] CPU usage monitoring setup

---

## 💾 BACKUP CONFIGURATION

### Database Backup
- [ ] Backup directory created: `/backup/sigap-api`
- [ ] Backup script created
- [ ] Backup script executable
- [ ] Cron job configured for daily backup
- [ ] Backup retention policy set (7 days)
- [ ] Test backup created and verified

### Application Backup
- [ ] `.env` file backed up securely
- [ ] Application code backed up
- [ ] Storage files backup strategy defined

---

## 📝 DOCUMENTATION

### Deployment Documentation
- [ ] Server credentials documented
- [ ] Database credentials documented
- [ ] API credentials documented
- [ ] Domain/DNS configuration documented
- [ ] Deployment date documented
- [ ] Deployed by documented

### Access Information
- [ ] Admin login credentials documented
- [ ] SSH access documented
- [ ] Database access documented
- [ ] Server IP address documented
- [ ] Domain name documented

---

## 🎯 POST-DEPLOYMENT TASKS

### Immediate Tasks
- [ ] Change default seeder passwords
- [ ] Test all major features
- [ ] Verify email notifications
- [ ] Verify WhatsApp notifications
- [ ] Test panic button functionality
- [ ] Test shift generation

### Within 24 Hours
- [ ] Monitor error logs
- [ ] Monitor performance
- [ ] Verify scheduled tasks running
- [ ] Test backup restoration
- [ ] Update documentation

### Within 1 Week
- [ ] Performance optimization if needed
- [ ] Security audit
- [ ] Load testing
- [ ] User acceptance testing
- [ ] Training for operations team

---

## 🆘 EMERGENCY CONTACTS

**Developer**:  
Name: _______________  
Email: _______________  
Phone: _______________

**System Administrator**:  
Name: _______________  
Email: _______________  
Phone: _______________

**Database Administrator**:  
Name: _______________  
Email: _______________  
Phone: _______________

---

## 📌 IMPORTANT NOTES

### Default Credentials (MUST CHANGE AFTER DEPLOYMENT!)
- Admin: `admin@gmail.com` / `kikipoiu`
- Relawan: `relawan@gmail.com` / `kikipoiu`
- User: `user@gmail.com` / `kikipoiu`

### Scheduled Tasks
- Auto-generate shifts: Daily at 02:00 WIB
- Log cleanup: Weekly

### Critical Paths
- Application: `/var/www/sigap-api`
- Logs: `/var/www/sigap-api/storage/logs`
- Nginx config: `/etc/nginx/sites-available/sigap-api`
- Supervisor config: `/etc/supervisor/conf.d/`

---

## ✍️ SIGN-OFF

**Deployment Completed By**:  
Name: _______________  
Signature: _______________  
Date: _______________

**Verified By**:  
Name: _______________  
Signature: _______________  
Date: _______________

**Approved By**:  
Name: _______________  
Signature: _______________  
Date: _______________

---

**Notes/Issues During Deployment**:
```
_________________________________________________________________

_________________________________________________________________

_________________________________________________________________

_________________________________________________________________

_________________________________________________________________
```

---

**Version**: 1.0.0  
**Last Updated**: 2026-01-26
