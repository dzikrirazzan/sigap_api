# 📚 DEPLOYMENT DOCUMENTATION INDEX

**Project**: SIGAP Emergency API  
**Version**: 1.0.0  
**Last Updated**: 2026-01-26

---

## 📖 DOKUMENTASI DEPLOYMENT

Proyek ini menyediakan dokumentasi lengkap untuk deployment ke VPS Ubuntu 22.04.5. Berikut adalah panduan untuk menggunakan dokumentasi ini:

---

## 📁 FILE DOKUMENTASI

### 1. **REQUIREMENTS_SUMMARY.md** ⭐ START HERE
**Untuk**: Tim Deployment / Project Manager  
**Isi**: 
- Spesifikasi server VPS yang dibutuhkan
- Software stack dan dependencies
- Credentials yang harus disiapkan
- Resource usage estimates
- Quick checklist

**Kapan digunakan**: Sebelum memulai deployment untuk memahami requirements

---

### 2. **VPS_DEPLOYMENT_GUIDE.md** 📘 FULL GUIDE
**Untuk**: System Administrator / DevOps Engineer  
**Isi**:
- Panduan deployment lengkap step-by-step
- Konfigurasi detail untuk setiap komponen
- Security best practices
- Monitoring & logging setup
- Backup strategy

**Kapan digunakan**: Saat melakukan deployment lengkap dari awal

---

### 3. **QUICK_DEPLOYMENT.md** ⚡ QUICK REFERENCE
**Untuk**: Developer / DevOps yang sudah familiar  
**Isi**:
- Copy-paste commands untuk instalasi cepat
- Update/redeploy commands
- Useful commands untuk maintenance
- Testing commands

**Kapan digunakan**: Untuk deployment cepat atau sebagai reference saat update

---

### 4. **DEPLOYMENT_CHECKLIST.md** ✅ CHECKLIST
**Untuk**: Tim Deployment (dapat di-print)  
**Isi**:
- Checklist lengkap untuk setiap tahap deployment
- Pre-deployment requirements
- Installation checklist
- Configuration checklist
- Testing checklist
- Sign-off section

**Kapan digunakan**: Selama proses deployment untuk memastikan tidak ada yang terlewat

---

### 5. **TROUBLESHOOTING.md** 🔧 PROBLEM SOLVING
**Untuk**: Support Team / System Administrator  
**Isi**:
- Common issues dan solusinya
- Server issues
- Application issues
- Database issues
- Queue & scheduler issues
- Performance issues
- Debugging commands

**Kapan digunakan**: Saat mengalami masalah atau error

---

## 🚀 ALUR DEPLOYMENT

### Untuk Tim yang Belum Familiar:

```
1. Baca REQUIREMENTS_SUMMARY.md
   ↓
2. Siapkan semua requirements (server, credentials, dll)
   ↓
3. Ikuti VPS_DEPLOYMENT_GUIDE.md step-by-step
   ↓
4. Gunakan DEPLOYMENT_CHECKLIST.md untuk tracking
   ↓
5. Test deployment
   ↓
6. Jika ada masalah, lihat TROUBLESHOOTING.md
```

### Untuk Tim yang Sudah Familiar:

```
1. Review REQUIREMENTS_SUMMARY.md
   ↓
2. Gunakan QUICK_DEPLOYMENT.md untuk copy-paste commands
   ↓
3. Checklist dengan DEPLOYMENT_CHECKLIST.md
   ↓
4. Jika ada masalah, lihat TROUBLESHOOTING.md
```

---

## 📋 RINGKASAN PROYEK

### Teknologi Stack:
- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0 / MariaDB 10.6+
- **Web Server**: Nginx
- **Process Manager**: Supervisor
- **SSL**: Let's Encrypt (Certbot)

### Fitur Utama:
1. **Authentication**: Laravel Sanctum (Token-based)
2. **Email OTP**: Verifikasi email dengan OTP
3. **Panic Button**: Emergency reporting system
4. **Shift Management**: Auto-generate shifts untuk relawan
5. **Report Management**: Laporan foto dengan geolocation
6. **WhatsApp Notification**: Via Fonnte API
7. **Email Notification**: Via Postmark SMTP

### Background Services:
1. **Queue Workers**: Process background jobs (email, notifications)
2. **Scheduler**: Auto-generate shifts daily at 02:00 WIB

---

## 🔑 INFORMASI PENTING

### Default Credentials (HARUS DIGANTI!)
Setelah deployment, aplikasi akan memiliki user default:

**Admin**:
- `admin@gmail.com` / `kikipoiu`
- `admindzikri@gmail.com` / `password`

**Relawan**:
- `relawan@gmail.com` / `kikipoiu`
- `relawan1-5@gmail.com` / `kikipoiu`

**User**:
- `user@gmail.com` / `kikipoiu`
- `user1-5@gmail.com` / `kikipoiu`

⚠️ **CRITICAL**: Ganti semua password default setelah deployment!

---

## 📊 SPESIFIKASI SERVER MINIMUM

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| OS | Ubuntu 22.04.5 LTS | Ubuntu 22.04.5 LTS |
| RAM | 2GB | 4GB |
| Storage | 20GB SSD | 40GB SSD |
| CPU | 2 vCPU | 4 vCPU |
| Bandwidth | 1TB/month | Unlimited |

---

## 🔧 COMMAND ARTISAN PENTING

### Deployment Pertama Kali:
```bash
php artisan key:generate          # Generate APP_KEY
php artisan migrate --force       # Run migrations
php artisan db:seed --force       # Seed database
php artisan storage:link          # Create storage link
php artisan config:cache          # Cache config
php artisan route:cache           # Cache routes
php artisan view:cache            # Cache views
```

### Update/Redeploy:
```bash
php artisan down                  # Maintenance mode
git pull origin main              # Pull latest code
composer install --no-dev         # Install dependencies
php artisan migrate --force       # Run new migrations
php artisan config:cache          # Re-cache config
php artisan route:cache           # Re-cache routes
php artisan view:cache            # Re-cache views
php artisan up                    # Exit maintenance mode
```

### Maintenance:
```bash
php artisan config:clear          # Clear config cache
php artisan cache:clear           # Clear application cache
php artisan route:clear           # Clear route cache
php artisan view:clear            # Clear view cache
php artisan optimize:clear        # Clear all caches
```

---

## 🧪 TESTING DEPLOYMENT

### 1. Health Check
```bash
curl https://your-domain.com/api/health
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

### 2. Login Test
```bash
curl -X POST https://your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com","password":"kikipoiu"}'
```

### 3. Authenticated Request
```bash
curl -X GET https://your-domain.com/api/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📞 SUPPORT & CONTACT

**Developer**: Dzikri Razzan  
**Email**: dzikrirazzan@students.undip.ac.id

Untuk pertanyaan atau bantuan deployment, silakan hubungi developer dengan menyertakan:
- Error message (exact text)
- Log files (Laravel, Nginx, PHP-FPM)
- Steps to reproduce
- Server environment details

---

## 📝 CATATAN TAMBAHAN

### Setelah Deployment:
1. ✅ Ganti semua default passwords
2. ✅ Setup backup automation
3. ✅ Configure monitoring
4. ✅ Test all major features
5. ✅ Document server credentials
6. ✅ Setup log rotation

### Maintenance Rutin:
- **Daily**: Monitor logs, check disk space
- **Weekly**: Review performance, check backups
- **Monthly**: Security updates, database optimization

---

## 🔗 REFERENSI EKSTERNAL

- [Laravel Documentation](https://laravel.com/docs/10.x)
- [Laravel Deployment](https://laravel.com/docs/10.x/deployment)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Supervisor Documentation](http://supervisord.org/)
- [Ubuntu Server Guide](https://ubuntu.com/server/docs)
- [Let's Encrypt](https://letsencrypt.org/)

---

## 📜 LICENSE

This project is part of SIGAP UNDIP Emergency System.

---

**Happy Deploying! 🚀**

Jika ada pertanyaan atau membutuhkan bantuan, jangan ragu untuk menghubungi developer.
