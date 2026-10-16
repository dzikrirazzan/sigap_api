# 🚀 LOCAL DEVELOPMENT SETUP GUIDE

## Prerequisites

- PHP 8.1+ with Composer (Backend)
- Node.js 18+ with npm (Frontend)
- MySQL 5.7+ (Local database)
- Git

## 📍 Part 1: Backend Setup (Laravel API)

### Step 1: Navigate to Backend

```bash
cd /Users/dzikrirazzan/code/Emergency_API
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### Step 4: Update .env for Local Development

```env
APP_NAME=SIGAP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_local
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
```

### Step 5: Create Local Database

```bash
# Using MySQL CLI
mysql -u root -p
```

```sql
CREATE DATABASE sigap_local;
EXIT;
```

### Step 6: Run Migrations

```bash
php artisan migrate:fresh --seed
```

### Step 7: Start Backend Server

```bash
php artisan serve
# Server runs on http://localhost:8000
```

## 📍 Part 2: Frontend Setup (Next.js)

### Step 1: Navigate to Frontend

```bash
cd "/Users/dzikrirazzan/Downloads/SIGAP-UNDIP-main 3"
```

### Step 2: Install Dependencies

```bash
npm install
```

### Step 3: Setup Environment

```bash
cp .env.example .env.local
```

### Step 4: Update .env.local

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

### Step 5: Start Frontend Server

```bash
npm run dev
# Frontend runs on http://localhost:3000
```

## ✅ Verify Both Are Running

### Terminal 1 - Backend

```bash
$ php artisan serve
Starting Laravel development server: http://localhost:8000
```

### Terminal 2 - Frontend

```bash
$ npm run dev
  ▲ Next.js 15.3.8
  - Local:        http://localhost:3000
```

### Terminal 3 - Test API Connection

```bash
# Check health endpoint
curl http://localhost:8000/api/health

# Should return:
# {"status":"ok","service":"SIGAP Emergency API","timestamp":"2026-04-28T...","version":"1.0.0"}
```

## 🧪 Part 3: Testing All Features

### Access Points

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000/api
- Health Check: http://localhost:8000/api/health

### Test Accounts (After Migration)

Run seeder to create test data:

```bash
php artisan db:seed
```

Look in `database/seeders/DatabaseSeeder.php` for test credentials.

### Testing Workflow

#### 1. **Authentication Tests**

```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@test.com","password":"password123"}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123"}'

# Response includes: access_token, refresh_token, user data
```

#### 2. **Student Features**

- [ ] Login as student
- [ ] Create report with photo
- [ ] View report history
- [ ] Use panic button
- [ ] View emergency contacts

#### 3. **Volunteer Features**

- [ ] Login as volunteer
- [ ] View assigned shifts
- [ ] View today's panic alerts
- [ ] Update panic alert status
- [ ] View reports

#### 4. **Admin Features**

- [ ] Login as admin
- [ ] View dashboard with stats
- [ ] Manage volunteers
- [ ] View/manage all reports
- [ ] View/manage panic alerts
- [ ] Manage shifts & patterns
- [ ] Bulk delete operations

#### 5. **API Endpoint Tests**

**Public Routes:**

```bash
# Health check
GET /api/health

# Register
POST /api/register

# Login
POST /api/login

# Forgot password
POST /api/password/forgot
```

**Protected Routes (Need Bearer Token):**

```bash
# After login, use token in header:
# Authorization: Bearer <access_token>

# User profile
GET /api/profile

# Create report
POST /api/reports
POST /api/upload-photo

# Create panic alert
POST /api/panic
GET /api/panic/today

# Update panic status
PUT /api/panic/{panicId}/status
```

**Admin Routes:**

```bash
# Requires admin role + token

# List volunteers
GET /api/admin/relawan

# Create volunteer
POST /api/admin/relawan

# Update volunteer
PUT /api/admin/relawan/{id}

# List panic alerts
GET /api/admin/panic

# List all reports
GET /admin/reports (or /api/reports)

# Manage shifts
GET /api/admin/shifts
GET /api/admin/shifts/weekly-patterns
POST /api/admin/shifts/set-day-pattern
```

## 🐛 Debugging Tips

### Backend Debugging

```bash
# View logs
tail -f storage/logs/laravel.log

# Run artisan commands
php artisan tinker
php artisan migrate:rollback
php artisan cache:clear
```

### Frontend Debugging

```bash
# Browser console (F12)
# Check Network tab for API calls
# Check if NEXT_PUBLIC_API_URL is set

# Check environment
echo $NEXT_PUBLIC_API_URL
```

### Common Issues

**CORS Error on Frontend**

- Ensure backend CORS config in `config/cors.php`
- Verify API URL in `.env.local`
- Check browser console for exact error

**Database Connection Failed**

- Verify MySQL is running
- Check DB credentials in .env
- Check database exists: `SHOW DATABASES;`

**Port Already in Use**

```bash
# Backend on different port
php artisan serve --port=8001

# Frontend on different port
npm run dev -- -p 3001
```

**API Endpoints Returning 405**

- Check backend routes in `routes/api.php`
- Verify HTTP method (GET, POST, PUT, DELETE)
- Check middleware requirements

## 📊 Database Schema

Key tables created:

- `users` - All users (students, volunteers, admins)
- `reports` - Emergency reports with photos
- `panic_reports` - Panic button alerts
- `relawan_shifts` - Volunteer shift assignments
- `relawan_shift_patterns` - Weekly patterns
- `email_otps` - Email verification
- `refresh_tokens` - Token management

View with:

```bash
php artisan tinker
>>> DB::connection()->getDoctrineConnection()->getSchemaManager()->listTableNames()
```

## 🎯 Full Feature Test Checklist

### Frontend Features

- [ ] User authentication (login/register)
- [ ] Email OTP verification
- [ ] Password reset flow
- [ ] Student can submit emergency report
- [ ] Student can upload photo with report
- [ ] Student can view report history
- [ ] Student can view emergency contacts
- [ ] Panic button works
- [ ] Volunteer can view assigned shifts
- [ ] Volunteer can see today's panic alerts
- [ ] Volunteer can update panic status
- [ ] Admin dashboard loads with stats
- [ ] Admin can manage volunteers (CRUD)
- [ ] Admin can view all reports
- [ ] Admin can manage panic alerts
- [ ] Admin can manage shift patterns
- [ ] Dark/light mode toggle works
- [ ] Responsive design (mobile friendly)

### Backend API Features

- [ ] Health check endpoint
- [ ] User registration
- [ ] User login with token
- [ ] Token refresh
- [ ] Profile endpoint
- [ ] Report CRUD operations
- [ ] Panic alert CRUD
- [ ] Shift management
- [ ] Volunteer management
- [ ] Email notifications
- [ ] Error handling (400, 401, 403, 404, 500)
- [ ] Pagination working
- [ ] Filtering & search working

## 🚀 Ready to Deploy?

Once all tests pass, run:

```bash
# Backend
php artisan optimize

# Frontend
npm run build
npm start
```

Both should run without errors. Ready to send to infrastructure team!
