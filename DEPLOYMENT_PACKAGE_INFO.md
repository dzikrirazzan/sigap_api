# 📦 SIGAP API - Deployment Package Info

## Backend Repository Structure

```
Emergency_API/
├── app/                    # Application code
├── database/              # Migrations & seeders
├── routes/               # API routes
├── config/               # Configuration files
├── storage/              # Logs & cache
├── .env                  # Environment variables (SANITIZE BEFORE SENDING)
├── .env.example          # Example env file
├── composer.json         # Dependencies
├── composer.lock         # Lock file
└── [other Laravel files]
```

## ✅ Deployment Checklist

### 1. **Clone Repository**

```bash
git clone <backend-repo-url>
cd Emergency_API
```

### 2. **Install Dependencies**

```bash
composer install
```

### 3. **Setup Environment**

```bash
cp .env.example .env
php artisan key:generate
```

### 4. **Database Configuration**

```bash
# Update .env with production database credentials
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Run migrations
php artisan migrate --force

# Seed data (optional)
php artisan db:seed
```

### 5. **Clear Cache**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. **Start Server**

```bash
# Production (using PHP built-in server for testing)
php artisan serve

# OR use proper web server (Nginx/Apache with PHP-FPM)
# Configure vhost pointing to public/ directory
```

### 7. **Set Permissions**

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## 🔐 Environment Variables to Configure

- `APP_ENV`: production
- `APP_DEBUG`: false
- `APP_URL`: Your production URL
- `DB_CONNECTION`: mysql
- `DB_HOST`: Production database host
- `DB_PORT`: 3306
- `DB_DATABASE`: Production database name
- `DB_USERNAME`: Production DB user
- `DB_PASSWORD`: Production DB password
- `MAIL_MAILER`: smtp/postmark
- `MAIL_HOST`: Email provider host
- `MAIL_PORT`: Email provider port
- `MAIL_USERNAME`: Email credentials
- `MAIL_PASSWORD`: Email credentials

## 📋 API Endpoints Available

All routes are prefixed with `/api/`

### Public Routes (No Auth Required)

- `POST /register` - User registration
- `POST /login` - User login
- `POST /refresh` - Refresh token
- `POST /email/send-otp` - Send email OTP
- `POST /email/verify-otp` - Verify email OTP
- `POST /password/forgot` - Request password reset
- `POST /password/reset` - Reset password

### Protected Routes (Auth Required)

- `GET /profile` - Get current user profile
- `POST /logout` - Logout user
- `GET /reports` - Get all reports
- `POST /reports` - Create report
- `GET /reports/{reportId}` - Get specific report
- `PUT /reports/{reportId}` - Update report

### Panic Routes (Auth Required)

- `POST /panic` - Create panic alert
- `GET /panic/today` - Get today's panic alerts
- `PUT /panic/{panicId}/status` - Update panic status

### Admin Routes (Auth + Admin Role)

- `GET /admin/users` - List all users
- `GET /admin/relawan` - List all volunteers
- `POST /admin/relawan` - Create volunteer
- `PUT /admin/relawan/{id}` - Update volunteer
- `DELETE /admin/users/{id}` - Delete user
- `GET /admin/panic` - List all panic alerts
- `GET /admin/shifts` - Manage shifts
- `GET /admin/shifts/weekly-patterns` - Get weekly patterns

## 🔍 Health Check

```bash
curl https://your-api-url/api/health
```

Should return:

```json
{
  "status": "ok",
  "service": "SIGAP Emergency API",
  "timestamp": "2026-04-28T...",
  "version": "1.0.0"
}
```

## 📊 Database

- **Connection**: MySQL
- **Version**: 5.7+
- **Character Set**: utf8mb4
- **Migrations**: Run automatically or manually with `php artisan migrate`

## 🚀 Production Recommendations

1. Use proper web server (Nginx + PHP-FPM recommended)
2. Enable HTTPS/SSL certificate
3. Set `APP_DEBUG=false`
4. Use environment file variables for secrets
5. Enable Laravel Horizon for queue processing (if needed)
6. Setup proper logging & monitoring
7. Configure CORS if needed (in `config/cors.php`)
8. Use database connection pooling
9. Setup automated backups for database
10. Monitor API response times & error rates

## 📞 Support

For issues, check logs in `storage/logs/`
