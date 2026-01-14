# 🚀 Tutorial Deploy SIGAP Emergency API ke Heroku

## 📋 Ringkasan Requirement Proyek

### **Tech Stack:**

- **Framework:** Laravel 10
- **PHP:** ^8.1
- **Database:** PostgreSQL (di Heroku)
- **Queue:** Database driver
- **Cache/Session:** Database driver
- **Email:** SMTP (Gmail)
- **WhatsApp:** Fonnte API

### **Fitur Utama:**

- Authentication dengan Laravel Sanctum
- Email OTP verification
- Password reset dengan OTP
- Emergency report management
- Panic button system
- Relawan shift management & auto-generation
- WhatsApp notifications
- File upload untuk laporan

---

## 🛠️ Prerequisite

### **1. Akun yang Dibutuhkan:**

- ✅ GitHub account
- ✅ Heroku account (buat di https://signup.heroku.com)
- ✅ Gmail account (untuk SMTP)
- ✅ Fonnte account (untuk WhatsApp API - https://fonnte.com)

### **2. Tools di Komputer:**

```bash
# Install Heroku CLI
brew tap heroku/brew && brew install heroku

# Verify installation
heroku --version
```

### **3. Persiapan Credentials:**

#### **Gmail App Password:**

1. Buka https://myaccount.google.com/security
2. Enable 2-Step Verification
3. Go to App Passwords
4. Generate password untuk "Mail"
5. Copy password (16 karakter)

#### **Fonnte API Token:**

1. Daftar di https://fonnte.com
2. Login ke dashboard
3. Go to Settings → API
4. Copy token Anda

---

## 📦 Step 1: Persiapan File Proyek (SUDAH SELESAI ✅)

File-file berikut sudah saya siapkan untuk Anda:

### **1. Procfile** ✅

Mendefinisikan dyno types untuk Heroku:

- `web`: Web server
- `worker`: Queue worker
- `release`: Commands yang dijalankan saat deployment

### **2. app.json** ✅

Konfigurasi Heroku app dengan semua environment variables yang diperlukan.

### **3. composer.json** ✅

Sudah diupdate dengan script untuk Heroku deployment.

### **4. Database Configuration** ✅

- Default database diubah ke PostgreSQL
- Support untuk Heroku DATABASE_URL

### **5. TrustProxies Middleware** ✅

Trust semua proxies untuk Heroku load balancer.

### **6. AppServiceProvider** ✅

Force HTTPS di production dan support PostgreSQL timezone.

---

## 🌐 Step 2: Push Kode ke GitHub

### **2.1 Initialize Git (jika belum)**

```bash
# Cek status git
git status

# Jika belum ada .git folder
git init
```

### **2.2 Commit File yang Sudah Saya Buat**

```bash
# Add semua perubahan
git add .

# Commit dengan pesan yang jelas
git commit -m "Prepare for Heroku deployment

- Add Procfile for web, worker, and release dynos
- Add app.json for Heroku configuration
- Update composer.json with post-install scripts
- Configure PostgreSQL as default database
- Update TrustProxies for Heroku load balancer
- Force HTTPS in production
- Add PostgreSQL timezone support"
```

### **2.3 Create GitHub Repository**

```bash
# Create repo di GitHub UI atau via gh CLI
# Kemudian push:
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/Emergency_API.git
git push -u origin main
```

---

## 🚀 Step 3: Setup Heroku App

### **3.1 Login ke Heroku**

```bash
heroku login
# Browser akan terbuka untuk login
```

### **3.2 Create Heroku App**

```bash
# Ganti 'sigap-undip-api' dengan nama unik Anda
heroku create sigap-undip-api

# Atau tanpa nama (Heroku akan generate random)
heroku create

# Output:
# Creating ⬢ sigap-undip-api... done
# https://sigap-undip-api.herokuapp.com/ | https://git.heroku.com/sigap-undip-api.git
```

### **3.3 Add PostgreSQL Database**

```bash
# Essential-0 plan (gratis, limit 10K rows)
heroku addons:create heroku-postgresql:essential-0

# Verify
heroku addons
heroku config:get DATABASE_URL
```

---

## ⚙️ Step 4: Konfigurasi Environment Variables

### **4.1 Generate APP_KEY**

```bash
# Di komputer lokal
php artisan key:generate --show
# Output: base64:abcd1234...xyz

# Set ke Heroku (ganti dengan output Anda)
heroku config:set APP_KEY="base64:abcd1234...xyz"
```

### **4.2 Set Basic Config**

```bash
heroku config:set APP_NAME="SIGAP Emergency API"
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set LOG_CHANNEL=errorlog
heroku config:set DB_CONNECTION=pgsql
```

### **4.3 Set Mail Configuration (Gmail)**

```bash
# Ganti dengan email dan app password Anda
heroku config:set MAIL_MAILER=smtp
heroku config:set MAIL_HOST=smtp.gmail.com
heroku config:set MAIL_PORT=587
heroku config:set MAIL_USERNAME=your-email@gmail.com
heroku config:set MAIL_PASSWORD=your-16-char-app-password
heroku config:set MAIL_ENCRYPTION=tls
heroku config:set MAIL_FROM_ADDRESS=noreply@sigap.com
heroku config:set MAIL_FROM_NAME="SIGAP Emergency API"
```

### **4.4 Set WhatsApp (Fonnte) Config**

```bash
# Ganti dengan token Fonnte Anda
heroku config:set FONNTE_TOKEN=your-fonnte-token-here
heroku config:set FONNTE_BASE_URL=https://api.fonnte.com
```

### **4.5 Set Queue & Cache Config**

```bash
heroku config:set QUEUE_CONNECTION=database
heroku config:set SESSION_DRIVER=database
heroku config:set CACHE_DRIVER=database
```

### **4.6 Verify Semua Config**

```bash
heroku config
```

---

## 🔗 Step 5: Connect GitHub ke Heroku

### **Method A: Via Heroku Dashboard (Recommended)**

1. **Buka Dashboard:**

   - Go to https://dashboard.heroku.com
   - Click app Anda: `sigap-undip-api`

2. **Connect GitHub:**

   - Click tab **Deploy**
   - Deployment method: pilih **GitHub**
   - Click **Connect to GitHub**
   - Authorize Heroku jika diminta
   - Search repository: `Emergency_API`
   - Click **Connect**

3. **Enable Automatic Deploys:**

   - Scroll ke **Automatic deploys**
   - Choose branch: `main`
   - Click **Enable Automatic Deploys**

4. **Manual Deploy Pertama:**
   - Scroll ke **Manual deploy**
   - Branch: `main`
   - Click **Deploy Branch**

### **Method B: Via Git (Alternative)**

```bash
# Link Heroku git remote
heroku git:remote -a sigap-undip-api

# Deploy
git push heroku main
```

---

## 🗄️ Step 6: Database Setup

### **6.1 Run Migrations**

```bash
# Run migrations di Heroku
heroku run php artisan migrate --force

# Check migration status
heroku run php artisan migrate:status
```

### **6.2 Create Storage Link**

```bash
heroku run php artisan storage:link
```

### **6.3 Seed Database (Optional)**

```bash
# Jika ada seeders
heroku run php artisan db:seed --force
```

---

## 👷 Step 7: Setup Worker Dyno

### **7.1 Scale Worker untuk Queue Processing**

```bash
# Enable worker dyno (1 instance)
heroku ps:scale worker=1

# Check status
heroku ps
```

### **7.2 Verify Worker Running**

```bash
# Check worker logs
heroku logs --tail --dyno worker
```

---

## 📅 Step 8: Setup Scheduler untuk Auto-Generate Shifts

### **8.1 Install Heroku Scheduler Add-on**

```bash
heroku addons:create scheduler:standard
```

### **8.2 Configure Scheduler**

```bash
# Open scheduler dashboard
heroku addons:open scheduler
```

Di dashboard Scheduler:

1. Click **Create job**
2. **Command:** `php artisan shifts:auto-generate --days=7`
3. **Frequency:** Daily
4. **Next Due:** 02:00 (atau jam lain yang Anda inginkan)
5. Click **Save job**

### **8.3 Test Scheduler Command**

```bash
# Test manual
heroku run php artisan shifts:auto-generate --days=7
```

---

## 🧪 Step 9: Testing Deployment

### **9.1 Open App**

```bash
heroku open
```

### **9.2 Test Health Check Endpoint**

```bash
# Get app URL
heroku info | grep "Web URL"

# Test endpoint
curl https://sigap-undip-api.herokuapp.com/api/health

# Expected output:
# {
#   "status": "ok",
#   "service": "SIGAP Emergency API",
#   "timestamp": "2026-01-14T12:00:00+00:00",
#   "version": "1.0.0"
# }
```

### **9.3 Check Logs**

```bash
# Real-time logs (semua dynos)
heroku logs --tail

# Filter by dyno
heroku logs --tail --dyno web
heroku logs --tail --dyno worker

# Last 500 lines
heroku logs -n 500
```

### **9.4 Test API Endpoints**

```bash
# Health check
curl https://sigap-undip-api.herokuapp.com/api/health

# Register (test)
curl -X POST https://sigap-undip-api.herokuapp.com/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "081234567890"
  }'
```

### **9.5 Verify Database Connection**

```bash
# Connect to PostgreSQL
heroku pg:psql

# Check tables
\dt

# Check users table
SELECT * FROM users LIMIT 5;

# Exit
\q
```

---

## 🔄 Step 10: Update & Redeploy

### **10.1 Automatic Deploy**

Jika sudah enable automatic deploys:

```bash
# Buat perubahan
git add .
git commit -m "Update: your changes"
git push origin main

# Heroku akan otomatis deploy
# Monitor di: https://dashboard.heroku.com/apps/sigap-undip-api/activity
```

### **10.2 Manual Deploy**

```bash
# Via GitHub
# Go to dashboard → Deploy → Manual deploy → Deploy Branch

# Via Git
git push heroku main
```

### **10.3 Rollback (jika ada error)**

```bash
# Lihat release history
heroku releases

# Rollback ke versi sebelumnya (ganti v10 dengan versi Anda)
heroku rollback v10
```

---

## 📊 Step 11: Monitoring & Maintenance

### **11.1 View Metrics**

```bash
# Dashboard metrics
heroku metrics

# Dyno status
heroku ps

# Database info
heroku pg:info

# Database size
heroku pg:info --app sigap-undip-api
```

### **11.2 Check Dyno Hours**

```bash
# Free tier: 1000 hours/month
# Cek remaining hours di dashboard:
# https://dashboard.heroku.com/account/billing
```

### **11.3 Database Backup**

```bash
# Create manual backup
heroku pg:backups:capture

# List backups
heroku pg:backups

# Download backup
heroku pg:backups:download
```

### **11.4 Clear Cache**

```bash
heroku run php artisan cache:clear
heroku run php artisan config:clear
heroku run php artisan route:clear
heroku run php artisan view:clear
```

---

## 🔒 Step 12: Security Checklist

- [x] `APP_DEBUG=false` di production
- [x] HTTPS forced via AppServiceProvider
- [x] TrustProxies configured
- [x] Database credentials via DATABASE_URL
- [x] Sensitive credentials di Config Vars (bukan di code)
- [x] `.env` tidak di-commit ke Git
- [ ] Setup CORS jika ada frontend
- [ ] Rate limiting untuk API endpoints
- [ ] Input validation di semua endpoints

---

## 🆘 Troubleshooting

### **Error: "Application Error"**

```bash
# Check logs
heroku logs --tail

# Common fixes:
heroku restart
heroku run php artisan migrate --force
heroku run php artisan config:cache
```

### **Database Connection Error**

```bash
# Check DATABASE_URL
heroku config:get DATABASE_URL

# Reset database (WARNING: deletes all data)
heroku pg:reset DATABASE_URL
heroku run php artisan migrate --force
```

### **Queue Not Processing**

```bash
# Check worker status
heroku ps

# Restart worker
heroku restart worker

# Check worker logs
heroku logs --tail --dyno worker
```

### **Scheduler Not Running**

```bash
# Open scheduler
heroku addons:open scheduler

# Verify job configured
# Check logs setelah scheduled time
heroku logs --tail | grep "shifts:auto-generate"
```

### **File Upload Issues**

Heroku filesystem is ephemeral (file akan hilang saat dyno restart).

**Solusi:** Gunakan external storage:

```bash
# Option 1: AWS S3
composer require league/flysystem-aws-s3-v3

# Option 2: Cloudinary
composer require cloudinary/cloudinary_php
```

---

## 💰 Cost & Upgrade Path

### **Free Tier Limits:**

- Dyno hours: 1000/month (web + worker combined)
- PostgreSQL: 10,000 rows
- Scheduler: Unlimited jobs
- No credit card required

### **When to Upgrade:**

#### **Dyno Sleep Issue:**

Free dynos sleep after 30 minutes inactivity.

**Solution:**

```bash
# Upgrade to Basic ($7/month per dyno)
heroku ps:resize web=basic
heroku ps:resize worker=basic
```

#### **Database Limit:**

Essential-0: 10,000 rows

**Solution:**

```bash
# Mini plan: 10M rows ($5/month)
heroku addons:upgrade heroku-postgresql:mini
```

---

## 📝 Useful Commands Cheatsheet

### **Deployment**

```bash
git push origin main              # Auto-deploy (if enabled)
git push heroku main              # Manual deploy via git
heroku releases                   # Release history
heroku rollback v10               # Rollback to version
```

### **Logs & Debugging**

```bash
heroku logs --tail                # Real-time logs
heroku logs -n 500                # Last 500 lines
heroku logs --dyno web            # Filter by dyno
heroku logs --source app          # App logs only
```

### **Dyno Management**

```bash
heroku ps                         # List dynos
heroku restart                    # Restart all dynos
heroku restart web                # Restart web dyno
heroku ps:scale web=2             # Scale to 2 web dynos
```

### **Database**

```bash
heroku pg:psql                    # Connect to database
heroku pg:info                    # Database info
heroku pg:backups:capture         # Create backup
heroku pg:backups:download        # Download backup
```

### **Run Commands**

```bash
heroku run php artisan migrate    # Run migration
heroku run php artisan tinker     # Open Tinker
heroku run bash                   # SSH into dyno
```

### **Configuration**

```bash
heroku config                     # List all vars
heroku config:set KEY=value       # Set variable
heroku config:unset KEY           # Remove variable
heroku config:get KEY             # Get single value
```

### **Add-ons**

```bash
heroku addons                     # List add-ons
heroku addons:open scheduler      # Open scheduler dashboard
heroku addons:open DATABASE       # Open database dashboard
```

---

## ✅ Final Checklist

- [ ] Repository pushed ke GitHub
- [ ] Heroku app created
- [ ] PostgreSQL add-on installed
- [ ] All environment variables configured
- [ ] GitHub connected to Heroku
- [ ] Automatic deploys enabled
- [ ] First deployment successful
- [ ] Database migrations run
- [ ] Storage link created
- [ ] Worker dyno scaled
- [ ] Scheduler configured for auto-generate shifts
- [ ] Health check endpoint tested
- [ ] API endpoints tested
- [ ] Logs checked (no errors)
- [ ] Email sending tested
- [ ] WhatsApp notification tested

---

## 🎯 Next Steps

1. **Frontend Integration:**

   - Update frontend API base URL ke Heroku URL
   - Configure CORS di `config/cors.php`

2. **Domain Custom (Optional):**

   ```bash
   heroku domains:add www.sigap-undip.com
   # Follow instructions untuk DNS setup
   ```

3. **SSL Certificate (Automatic di Heroku):**

   - Heroku otomatis provide SSL untuk \*.herokuapp.com
   - Untuk custom domain, SSL juga otomatis via Let's Encrypt

4. **Monitoring Setup:**
   - Install New Relic add-on untuk monitoring
   - Setup error tracking (Sentry, Bugsnag)

---

## 📞 Support

**Dokumentasi:**

- Heroku: https://devcenter.heroku.com/categories/php
- Laravel: https://laravel.com/docs/10.x/deployment

**Jika ada masalah:**

1. Check logs: `heroku logs --tail`
2. Check status: https://status.heroku.com
3. Heroku Support: https://help.heroku.com

---

**🎉 Selamat! API Anda sudah live di Heroku!**

URL: https://sigap-undip-api.herokuapp.com
