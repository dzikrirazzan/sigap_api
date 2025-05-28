# 🚨 SIGAP - Emergency Panic Button System

## Fitur yang Sudah Dibuat

### 1. **Panic Button System**
- ✅ User bisa mengirim panic report dengan lokasi GPS
- ✅ Sistem otomatis kirim ke relawan yang sedang bertugas
- ✅ Real-time notification ke relawan
- ✅ Relawan bisa handle dan resolve panic reports
- ✅ Admin bisa melihat semua panic reports

### 2. **Sistem Relawan Bergiliran**
- ✅ 4 relawan bertugas setiap hari secara bergiliran
- ✅ Command untuk assign shift otomatis
- ✅ Sistem rotasi yang adil
- ✅ Relawan hanya bisa akses panic reports saat bertugas

### 3. **Database Schema**
```sql
-- Panic Reports Table
- id
- user_id (foreign key)
- latitude, longitude (GPS coordinates)
- location_description
- status (pending, handling, resolved, cancelled)
- handled_by (relawan yang menangani)
- handled_at (timestamp)
- created_at, updated_at

-- Relawan Shifts Table
- id
- relawan_id (foreign key)
- shift_date
- created_at, updated_at
```

### 4. **API Endpoints**

#### Panic Button
- `POST /api/panic` - User kirim panic report
- `GET /api/panic-today` - Relawan lihat panic hari ini
- `POST /api/panic/{id}/handle` - Relawan ambil panic report
- `POST /api/panic/{id}/resolve` - Relawan selesaikan panic report
- `GET /api/panic/admin` - Admin lihat semua panic reports
- `GET /api/panic/relawan-today` - Admin lihat relawan bertugas hari ini

#### Shift Management
- `GET /api/admin/shifts` - Lihat shift relawan
- `POST /api/admin/shifts` - Buat shift manual
- `DELETE /api/admin/shifts/{date}` - Hapus shift
- `POST /api/admin/shifts/auto-assign` - Auto assign shift

### 5. **Frontend Testing Pages**
- 📱 `panic-test.html` - Panic button untuk user
- 👮‍♂️ `relawan-dashboard.html` - Dashboard untuk relawan

### 6. **Command Line Tools**
```bash
# Generate test data
php artisan test:panic-reports 50

# Assign daily shift (4 relawan per hari)
php artisan relawan:assign-daily-shift

# Assign shift untuk tanggal tertentu
php artisan relawan:assign-daily-shift 2025-05-29
```

## Cara Testing

### 1. **Via Postman Collection**
```bash
# Import postman_collection.json
# Gunakan "Kirim Panic Report Random" untuk test dengan data random
# Set iterations = 50 untuk bulk testing
```

### 2. **Via Web Interface**
```bash
# User panic button
open https://sigap-api-5hk6r.ondigitalocean.app/panic-test.html

# Relawan dashboard
open https://sigap-api-5hk6r.ondigitalocean.app/relawan-dashboard.html
```

### 3. **Via Command Line**
```bash
# Generate test data
php artisan test:panic-reports 50

# Assign relawan shift
php artisan relawan:assign-daily-shift
```

## Flow Sistem

### 1. **User Emergency Flow**
1. User buka panic-test.html
2. Login dengan akun user
3. Browser minta akses lokasi
4. Tekan tombol PANIC
5. Sistem kirim lokasi + profile ke relawan bertugas
6. Relawan dapat notifikasi real-time

### 2. **Relawan Response Flow**
1. Relawan buka relawan-dashboard.html
2. Login dengan akun relawan
3. Lihat daftar panic reports hari ini
4. Klik "Handle" untuk ambil kasus
5. Hubungi user via telepon (nomor tersedia)
6. Klik "Resolve" setelah selesai

### 3. **Admin Management Flow**
1. Admin assign 4 relawan per hari
2. Monitor semua panic reports
3. Lihat statistik dan laporan

## Data yang Dikirim ke Relawan

Ketika user tekan panic button, relawan bertugas akan menerima:
- 📍 **Lokasi GPS** (latitude, longitude)
- 🗺️ **Link Google Maps** 
- 👤 **Data Profile User:**
  - Nama lengkap
  - Email
  - **Nomor telepon** (untuk kontak langsung)
  - NIK
- 🕐 **Timestamp** emergency
- 📝 **Deskripsi lokasi** (opsional)

## Keamanan & Authorization

- ✅ **Sanctum Authentication** untuk semua API
- ✅ **Role-based Access:**
  - User: hanya bisa kirim panic
  - Relawan: hanya akses panic saat bertugas
  - Admin: akses penuh
- ✅ **CORS** configured untuk web access
- ✅ **Input validation** untuk semua endpoints

## Real-time Features

- ✅ **Auto-refresh** dashboard relawan (30 detik)
- ✅ **Broadcast notifications** (framework ready)
- ✅ **Status tracking** (pending → handling → resolved)

## Production Ready Features

- ✅ **Error handling** yang proper
- ✅ **Logging** untuk debugging
- ✅ **Database indexing** untuk performance
- ✅ **API documentation** via Postman
- ✅ **Test data generators**
- ✅ **Automated shift assignment**

---

**Status: ✅ COMPLETE & READY FOR TESTING**

Sistem panic button sudah fully functional dengan:
- Frontend interface untuk testing
- Backend API yang complete
- Database schema yang proper
- Sistem relawan bergiliran
- Command line tools untuk management
- Real-time notifications
- Role-based security
