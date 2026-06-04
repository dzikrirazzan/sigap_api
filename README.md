# SIGAP UNDIP API

Backend Laravel untuk aplikasi SIGAP UNDIP.

## Requirement

- PHP 8.2 atau 8.3
- Composer
- MySQL 8
- Node.js LTS jika perlu build asset

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

API lokal:

```txt
http://127.0.0.1:8000/api
```

## Env Penting

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=emergency_api
DB_USERNAME=root
DB_PASSWORD=
```

Untuk production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.domain.com
FRONTEND_URL=https://app.domain.com
CORS_ALLOWED_ORIGINS=https://app.domain.com
```

## Test

```bash
php artisan test
```

## Deploy Singkat

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Tambahkan Laravel scheduler di cron:

```cron
* * * * * cd /path/to/sigap_api && php artisan schedule:run >> /dev/null 2>&1
```

## Akun Seeder

```txt
admin@gmail.com / kikipoiu
relawan@gmail.com / kikipoiu
user@gmail.com / kikipoiu
```

User biasa perlu email terverifikasi untuk login.
