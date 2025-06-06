# 🚀 Setup Auto Shift Assignment di DigitalOcean

## Langkah-langkah Setup

### 1. SSH ke Server DigitalOcean
```bash
ssh root@YOUR_SERVER_IP
```

### 2. Navigate ke Project Directory
```bash
cd /var/www/emergency-api  # Sesuaikan dengan path deployment Anda
```

### 3. Setup Cron Job untuk Laravel Scheduler
```bash
# Edit crontab
crontab -e

# Tambahkan line berikut (ganti path sesuai dengan project Anda):
* * * * * cd /var/www/emergency-api && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Verify Setup
```bash
# List semua scheduled commands
php artisan schedule:list

# Test manual generation
php artisan shifts:auto-generate --days=7

# Check automation status via API
curl -X GET "https://your-domain.com/api/admin/automation/status" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Enable/Disable Automation via API

**Enable automation:**
```bash
curl -X POST "https://your-domain.com/api/admin/automation/toggle" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"enabled": true}'
```

**Check status:**
```bash
curl -X GET "https://your-domain.com/api/admin/automation/status" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🔄 Yang Akan Terjadi Otomatis

1. **Setiap hari jam 02:00 WIB**, sistem akan:
   - Generate shifts untuk 7 hari ke depan
   - Berdasarkan weekly pattern yang sudah di-set
   - Skip tanggal yang sudah ada shifts
   - Log hasil ke `storage/logs/auto-shift-generation.log`

2. **Setiap minggu**, sistem akan:
   - Cleanup log file jika lebih dari 5MB

## 📊 Monitoring

### Via API:
- **Status**: `GET /api/admin/automation/status`
- **Logs**: `GET /api/admin/automation/logs`
- **Force generate**: `POST /api/admin/automation/force-generation`

### Via Server:
```bash
# Check logs
tail -f storage/logs/auto-shift-generation.log

# Manual test
php artisan shifts:auto-generate --days=7
```

## 🛠️ Troubleshooting

### Jika cron tidak jalan:
1. Pastikan path ke project benar
2. Pastikan permission: `chmod +x artisan`
3. Test manual: `php artisan schedule:run`

### Jika database connection error:
1. Check `.env` file database settings
2. Pastikan DigitalOcean managed database accessible
3. Test connection: `php artisan migrate:status`

## 🔧 Configuration Files

File yang mengatur automation:
- `app/Console/Kernel.php` - Schedule definition
- `app/Console/Commands/AutoGenerateShifts.php` - Auto generation logic
- `app/Http/Controllers/ShiftAutomationController.php` - API controls
