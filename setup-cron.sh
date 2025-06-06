#!/bin/bash

# Setup Cron Job untuk Laravel Scheduler di DigitalOcean
# Jalankan script ini setelah deploy ke server

echo "🔧 Setting up Laravel Scheduler untuk Emergency API..."

# Path ke project Laravel (sesuaikan dengan path deployment Anda)
PROJECT_PATH="/var/www/emergency-api"

# Backup current crontab
echo "📋 Backing up current crontab..."
crontab -l > /tmp/crontab.backup 2>/dev/null || true

# Add Laravel scheduler to crontab
echo "⚙️ Adding Laravel scheduler to crontab..."
(crontab -l 2>/dev/null; echo "* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo "✅ Cron job added successfully!"
echo ""
echo "📋 Current crontab:"
crontab -l
echo ""
echo "🕐 Schedule yang akan berjalan:"
echo "   • Auto-generate shifts: Daily at 02:00 WIB"
echo "   • Log cleanup: Weekly"
echo ""
echo "📝 Untuk verify schedule, jalankan di server:"
echo "   php artisan schedule:list"
