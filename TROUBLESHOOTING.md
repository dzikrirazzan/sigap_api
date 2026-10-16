# 🔧 TROUBLESHOOTING GUIDE - SIGAP Emergency API

**Version**: 1.0.0  
**Last Updated**: 2026-01-26

---

## 📋 TABLE OF CONTENTS

1. [Common Issues](#common-issues)
2. [Server Issues](#server-issues)
3. [Application Issues](#application-issues)
4. [Database Issues](#database-issues)
5. [Queue & Scheduler Issues](#queue--scheduler-issues)
6. [Email & Notification Issues](#email--notification-issues)
7. [Performance Issues](#performance-issues)
8. [Security Issues](#security-issues)

---

## 🚨 COMMON ISSUES

### Issue: 500 Internal Server Error

**Symptoms:**
- API returns 500 error
- White screen or generic error page

**Diagnosis:**
```bash
# Check Laravel logs
sudo tail -f /var/www/sigap-api/storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.1-fpm.log
```

**Common Causes & Solutions:**

1. **Cache Issues**
```bash
cd /var/www/sigap-api
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
```

2. **Permission Issues**
```bash
sudo chown -R www-data:www-data /var/www/sigap-api
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache
```

3. **Missing APP_KEY**
```bash
sudo -u www-data php artisan key:generate
```

4. **PHP Extensions Missing**
```bash
# Check installed extensions
php -m

# Install missing extensions
sudo apt install php8.1-[extension-name]
sudo systemctl restart php8.1-fpm
```

---

### Issue: 404 Not Found on All Routes

**Symptoms:**
- All API endpoints return 404
- Only index.php works

**Diagnosis:**
```bash
# Check Nginx configuration
sudo nginx -t

# Check if site is enabled
ls -la /etc/nginx/sites-enabled/
```

**Solutions:**

1. **Check Nginx Configuration**
```bash
sudo nano /etc/nginx/sites-available/sigap-api
```
Ensure `try_files` directive is correct:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

2. **Restart Nginx**
```bash
sudo systemctl restart nginx
```

3. **Clear Route Cache**
```bash
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan route:cache
```

---

### Issue: Permission Denied Errors

**Symptoms:**
- "Permission denied" in logs
- Cannot write to storage
- Cannot create cache files

**Solutions:**

1. **Fix Storage Permissions**
```bash
cd /var/www/sigap-api
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

2. **Fix Bootstrap Cache Permissions**
```bash
sudo chown -R www-data:www-data bootstrap/cache
sudo chmod -R 775 bootstrap/cache
```

3. **Fix All Permissions**
```bash
sudo chown -R www-data:www-data /var/www/sigap-api
sudo find /var/www/sigap-api -type d -exec chmod 755 {} \;
sudo find /var/www/sigap-api -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/sigap-api/storage
sudo chmod -R 775 /var/www/sigap-api/bootstrap/cache
```

---

## 🖥️ SERVER ISSUES

### Issue: Out of Memory

**Symptoms:**
- Server becomes unresponsive
- PHP processes killed
- "Out of memory" errors in logs

**Diagnosis:**
```bash
# Check memory usage
free -m

# Check processes
ps aux --sort=-%mem | head -10

# Check PHP-FPM memory
ps aux | grep php-fpm
```

**Solutions:**

1. **Increase PHP Memory Limit**
```bash
sudo nano /etc/php/8.1/fpm/php.ini
```
```ini
memory_limit = 256M  ; Increase if needed
```

2. **Optimize PHP-FPM Pool**
```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```
```ini
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

3. **Restart PHP-FPM**
```bash
sudo systemctl restart php8.1-fpm
```

4. **Add Swap Space** (if needed)
```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

### Issue: Disk Space Full

**Symptoms:**
- Cannot write files
- Application errors
- "No space left on device"

**Diagnosis:**
```bash
# Check disk usage
df -h

# Find large files
sudo du -sh /var/www/sigap-api/* | sort -h

# Check log sizes
sudo du -sh /var/www/sigap-api/storage/logs/*
```

**Solutions:**

1. **Clear Laravel Logs**
```bash
sudo truncate -s 0 /var/www/sigap-api/storage/logs/laravel.log
```

2. **Clear Old Logs**
```bash
sudo find /var/www/sigap-api/storage/logs -name "*.log" -mtime +30 -delete
```

3. **Clear System Logs**
```bash
sudo journalctl --vacuum-time=7d
```

4. **Setup Log Rotation**
```bash
sudo nano /etc/logrotate.d/laravel
```
```
/var/www/sigap-api/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## 🔧 APPLICATION ISSUES

### Issue: CSRF Token Mismatch

**Symptoms:**
- "CSRF token mismatch" error
- 419 status code

**Solutions:**

1. **This is an API** - CSRF should be disabled for API routes
```bash
# Verify routes are in api.php (not web.php)
cat /var/www/sigap-api/routes/api.php
```

2. **Clear Session**
```bash
sudo -u www-data php artisan session:table
sudo -u www-data php artisan migrate
```

---

### Issue: Unauthenticated / Token Invalid

**Symptoms:**
- "Unauthenticated" error
- "Token tidak valid" error
- 401 status code

**Diagnosis:**
```bash
# Check if Sanctum tables exist
sudo -u www-data php artisan tinker
```
```php
\DB::table('personal_access_tokens')->count();
```

**Solutions:**

1. **Verify Token in Request**
```bash
# Token should be in Authorization header
# Authorization: Bearer <token>
```

2. **Check Sanctum Configuration**
```bash
cat /var/www/sigap-api/config/sanctum.php
```

3. **Clear Config Cache**
```bash
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
```

4. **Regenerate Token**
- Login again to get new token
- Use refresh token endpoint if available

---

### Issue: Storage Link Not Working

**Symptoms:**
- Uploaded images return 404
- Storage files not accessible

**Solutions:**

1. **Recreate Storage Link**
```bash
cd /var/www/sigap-api
sudo -u www-data php artisan storage:link
```

2. **Verify Symbolic Link**
```bash
ls -la /var/www/sigap-api/public/storage
# Should point to ../storage/app/public
```

3. **Check Nginx Configuration**
```nginx
# In Nginx config, ensure public directory is document root
root /var/www/sigap-api/public;
```

---

## 🗄️ DATABASE ISSUES

### Issue: Database Connection Failed

**Symptoms:**
- "SQLSTATE[HY000]" error
- "Connection refused"
- "Access denied"

**Diagnosis:**
```bash
# Test MySQL connection
mysql -u sigap_user -p sigap_emergency

# Check MySQL is running
sudo systemctl status mysql

# Check .env database credentials
cat /var/www/sigap-api/.env | grep DB_
```

**Solutions:**

1. **Verify Database Credentials**
```bash
sudo nano /var/www/sigap-api/.env
```
Ensure:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_emergency
DB_USERNAME=sigap_user
DB_PASSWORD=correct_password
```

2. **Test Connection via Tinker**
```bash
sudo -u www-data php artisan tinker
```
```php
DB::connection()->getPdo();
```

3. **Restart MySQL**
```bash
sudo systemctl restart mysql
```

4. **Check MySQL Logs**
```bash
sudo tail -f /var/log/mysql/error.log
```

---

### Issue: Migration Failed

**Symptoms:**
- Migration errors
- "Table already exists"
- "Column not found"

**Solutions:**

1. **Check Migration Status**
```bash
sudo -u www-data php artisan migrate:status
```

2. **Rollback and Re-migrate**
```bash
# CAUTION: This will delete data!
sudo -u www-data php artisan migrate:fresh --force
sudo -u www-data php artisan db:seed --force
```

3. **Fix Specific Migration**
```bash
# Rollback last migration
sudo -u www-data php artisan migrate:rollback --step=1

# Re-run migrations
sudo -u www-data php artisan migrate --force
```

4. **Reset Migrations** (Development Only!)
```bash
# WARNING: Deletes all data!
sudo -u www-data php artisan migrate:fresh --seed --force
```

---

### Issue: Slow Database Queries

**Symptoms:**
- Slow API responses
- High database CPU usage
- Timeout errors

**Diagnosis:**
```bash
# Check slow query log
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```
Enable slow query log:
```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

**Solutions:**

1. **Add Database Indexes**
```bash
sudo -u www-data php artisan tinker
```
```php
// Check queries without indexes
DB::enableQueryLog();
// Run your slow endpoint
DB::getQueryLog();
```

2. **Optimize Tables**
```sql
OPTIMIZE TABLE users;
OPTIMIZE TABLE panic_reports;
OPTIMIZE TABLE reports;
```

3. **Clear Query Cache**
```bash
sudo -u www-data php artisan cache:clear
```

---

## ⚙️ QUEUE & SCHEDULER ISSUES

### Issue: Queue Not Processing

**Symptoms:**
- Jobs stuck in queue
- Emails not sending
- Notifications not sent

**Diagnosis:**
```bash
# Check supervisor status
sudo supervisorctl status

# Check queue worker logs
sudo tail -f /var/www/sigap-api/storage/logs/queue-worker.log

# Check jobs table
sudo -u www-data php artisan tinker
```
```php
\DB::table('jobs')->count();
```

**Solutions:**

1. **Restart Queue Workers**
```bash
sudo supervisorctl restart sigap-queue-worker:*
```

2. **Check Supervisor Configuration**
```bash
cat /etc/supervisor/conf.d/sigap-queue-worker.conf
```

3. **Manually Process Queue**
```bash
sudo -u www-data php artisan queue:work database --once
```

4. **Clear Failed Jobs**
```bash
sudo -u www-data php artisan queue:flush
```

5. **Check Failed Jobs**
```bash
sudo -u www-data php artisan queue:failed
```

---

### Issue: Scheduler Not Running

**Symptoms:**
- Scheduled tasks not executing
- Auto-shift generation not working
- No scheduler logs

**Diagnosis:**
```bash
# Check scheduler status
sudo supervisorctl status sigap-scheduler

# Check scheduler logs
sudo tail -f /var/www/sigap-api/storage/logs/scheduler.log

# Test scheduler manually
sudo -u www-data php artisan schedule:run
```

**Solutions:**

1. **Restart Scheduler**
```bash
sudo supervisorctl restart sigap-scheduler
```

2. **Verify Scheduler Configuration**
```bash
cat /etc/supervisor/conf.d/sigap-scheduler.conf
```

3. **Check Schedule List**
```bash
sudo -u www-data php artisan schedule:list
```

4. **Test Specific Command**
```bash
sudo -u www-data php artisan shifts:auto-generate --days=7
```

---

## 📧 EMAIL & NOTIFICATION ISSUES

### Issue: Emails Not Sending

**Symptoms:**
- OTP emails not received
- No email errors in logs
- Queue jobs processed but no email

**Diagnosis:**
```bash
# Check mail configuration
cat /var/www/sigap-api/.env | grep MAIL_

# Test email
sudo -u www-data php artisan tinker
```
```php
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

**Solutions:**

1. **Verify SMTP Credentials**
```bash
sudo nano /var/www/sigap-api/.env
```
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=your_token
MAIL_PASSWORD=your_token
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@domain.com
```

2. **Clear Config Cache**
```bash
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
```

3. **Check Postmark Status**
- Login to Postmark dashboard
- Check sending limits
- Verify API token is active

4. **Test with Debug Command**
```bash
sudo -u www-data php artisan debug:email test@example.com
```

---

### Issue: WhatsApp Notifications Not Sending

**Symptoms:**
- WhatsApp messages not received
- Fonnte API errors

**Diagnosis:**
```bash
# Check Fonnte configuration
cat /var/www/sigap-api/.env | grep FONNTE_

# Check Laravel logs for Fonnte errors
sudo tail -f /var/www/sigap-api/storage/logs/laravel.log | grep -i fonnte
```

**Solutions:**

1. **Verify Fonnte Token**
```bash
sudo nano /var/www/sigap-api/.env
```
```env
FONNTE_TOKEN=your_valid_token
FONNTE_BASE_URL=https://api.fonnte.com
```

2. **Test Fonnte API**
```bash
curl -X POST https://api.fonnte.com/send \
  -H "Authorization: YOUR_TOKEN" \
  -d "target=6281234567890&message=Test"
```

3. **Check Fonnte Balance**
- Login to Fonnte dashboard
- Verify balance/quota

---

## 🚀 PERFORMANCE ISSUES

### Issue: Slow API Response

**Symptoms:**
- API takes >2 seconds to respond
- Timeout errors
- High server load

**Diagnosis:**
```bash
# Check server load
top
htop

# Check slow queries
sudo tail -f /var/log/mysql/slow-query.log

# Enable Laravel query log
sudo -u www-data php artisan tinker
```
```php
DB::enableQueryLog();
// Make API request
DB::getQueryLog();
```

**Solutions:**

1. **Enable Caching**
```bash
sudo nano /var/www/sigap-api/.env
```
```env
CACHE_STORE=redis  # or database
```

2. **Optimize Autoloader**
```bash
sudo -u www-data composer dump-autoload --optimize
```

3. **Cache Configuration**
```bash
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

4. **Enable OPcache**
```bash
sudo nano /etc/php/8.1/fpm/php.ini
```
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

5. **Optimize Database**
```sql
ANALYZE TABLE users;
ANALYZE TABLE panic_reports;
OPTIMIZE TABLE users;
OPTIMIZE TABLE panic_reports;
```

---

## 🔒 SECURITY ISSUES

### Issue: Unauthorized Access

**Symptoms:**
- Unauthorized users accessing admin endpoints
- Token bypass

**Solutions:**

1. **Verify Middleware**
```bash
cat /var/www/sigap-api/routes/api.php
```
Ensure admin routes have `admin` middleware

2. **Check Token Validation**
```bash
cat /var/www/sigap-api/app/Http/Middleware/SanctumTokenAuth.php
```

3. **Revoke All Tokens** (Emergency)
```bash
sudo -u www-data php artisan tinker
```
```php
\DB::table('personal_access_tokens')->truncate();
```

---

### Issue: SSL Certificate Expired

**Symptoms:**
- Browser shows "Not Secure"
- SSL certificate warnings

**Solutions:**

1. **Renew Certificate**
```bash
sudo certbot renew
```

2. **Force Renewal**
```bash
sudo certbot renew --force-renewal
```

3. **Restart Nginx**
```bash
sudo systemctl restart nginx
```

4. **Setup Auto-Renewal**
```bash
sudo systemctl status certbot.timer
sudo systemctl enable certbot.timer
```

---

## 🔍 DEBUGGING COMMANDS

### General Debugging
```bash
# Enable debug mode temporarily
sudo nano /var/www/sigap-api/.env
# Set APP_DEBUG=true (ONLY for debugging, set back to false!)

# Clear all caches
sudo -u www-data php artisan optimize:clear

# View all routes
sudo -u www-data php artisan route:list

# View all config
sudo -u www-data php artisan config:show

# Check environment
sudo -u www-data php artisan env
```

### Log Monitoring
```bash
# Monitor Laravel logs
sudo tail -f /var/www/sigap-api/storage/logs/laravel.log

# Monitor Nginx access
sudo tail -f /var/log/nginx/access.log

# Monitor Nginx errors
sudo tail -f /var/log/nginx/error.log

# Monitor PHP-FPM
sudo tail -f /var/log/php8.1-fpm.log

# Monitor MySQL
sudo tail -f /var/log/mysql/error.log
```

### Service Status
```bash
# Check all services
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
sudo supervisorctl status

# Restart all services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
sudo supervisorctl restart all
```

---

## 📞 GETTING HELP

If issue persists after trying solutions above:

1. **Collect Information**:
   - Error message (exact text)
   - Laravel logs
   - Nginx error logs
   - Steps to reproduce

2. **Contact Developer**:
   - Email: dzikrirazzan@students.undip.ac.id
   - Include all collected information

3. **Check Documentation**:
   - Laravel Documentation: https://laravel.com/docs
   - Nginx Documentation: https://nginx.org/en/docs/

---

**Last Updated**: 2026-01-26  
**Version**: 1.0.0
