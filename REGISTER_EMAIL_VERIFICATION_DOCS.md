# 📧 Email Verification untuk Register - Complete Documentation

## 🚀 Quick Start untuk Tim Frontend

### TL;DR - 3 API Endpoints yang Perlu Diimplementasikan:

#### 1️⃣ Register User

```
POST /api/register
Body: { name, email, password, no_telp, nim?, jurusan? }
→ Redirect ke halaman verify-email
```

#### 2️⃣ Verify OTP

```
POST /api/email/verify-otp
Body: { email, otp }
→ Redirect ke halaman login
```

#### 3️⃣ Resend OTP

```
POST /api/email/resend-otp
Body: { email }
→ Show success message
```

---

## 📱 User Flow Diagram

```
┌─────────────────────┐
│   Register Form     │  User isi: name, email, password, no_telp, nim, jurusan
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  API kirim OTP      │  Email berisi 6-digit OTP (expires in 10 minutes)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Verify OTP Page    │  User input OTP dari email
└──────────┬──────────┘
           │
           ├─── ✅ Valid OTP → Email Verified → Redirect to Login
           │
           └─── ❌ Invalid/Expired OTP → Show error + Resend button
```

---

## 🔄 Complete Authentication Flow

### ✅ **Register** - BUTUH OTP

1. User register → API kirim OTP ke email
2. User verifikasi OTP dari email
3. Email verified → baru bisa login

### ✅ **Login** - TIDAK BUTUH OTP

- User langsung login dengan email + password
- Tidak ada OTP, langsung dapat access token & refresh token
- Kecuali email belum diverifikasi, akan ditolak dengan status 403

### ✅ **Reset Password** - BUTUH OTP (lihat FORGOT_PASSWORD_API_DOCS.md)

1. User forgot password → API kirim OTP ke email
2. User verifikasi OTP dari email
3. User set password baru

---

## 📋 API Endpoints Detail

### Base URL

```
https://sigap-api-5hk6r.ondigitalocean.app
```

---

### 1. Register User Baru

Mendaftarkan user baru dan mengirim OTP verification ke email.

**Endpoint:** `POST /api/register`

**Request Body:**

```json
{
  "name": "Dzikri Razzan",
  "email": "dzikrirazzan@gmail.com",
  "password": "password123",
  "no_telp": "081234567890",
  "nim": "24060122130123",
  "jurusan": "Informatika"
}
```

**Field Details:**

| Field    | Type   | Required    | Description                          |
| -------- | ------ | ----------- | ------------------------------------ |
| name     | string | ✅ Yes      | Nama lengkap user                    |
| email    | string | ✅ Yes      | Email valid & unique                 |
| password | string | ✅ Yes      | Minimal 6 karakter                   |
| no_telp  | string | ✅ Yes      | Nomor telepon (max 15 digit)         |
| nim      | string | ❌ Optional | Nomor Induk Mahasiswa (max 25 char)  |
| jurusan  | string | ❌ Optional | Jurusan/Program Studi (max 100 char) |

**Success Response (201 Created):**

```json
{
  "message": "Registration successful. Please check your email for OTP verification code.",
  "user": {
    "id": 1,
    "name": "Dzikri Razzan",
    "email": "dzikrirazzan@gmail.com",
    "role": "user",
    "no_telp": "081234567890",
    "nim": "24060122130123",
    "jurusan": "Informatika",
    "email_verified_at": null,
    "created_at": "2025-10-14T10:30:00.000000Z",
    "updated_at": "2025-10-14T10:30:00.000000Z"
  },
  "email_verification_required": true,
  "otp_expires_in_minutes": 10
}
```

**Error Response (422 Validation Error):**

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

**Error Response (201 dengan warning - email gagal terkirim):**

```json
{
  "message": "Registration successful but failed to send verification email. Please try to resend OTP.",
  "user": {
    /* user object */
  },
  "email_verification_required": true
}
```

---

### 2. Verify OTP Email

Memverifikasi OTP yang dikirim ke email user.

**Endpoint:** `POST /api/email/verify-otp`

**Request Body:**

```json
{
  "email": "dzikrirazzan@gmail.com",
  "otp": "123456"
}
```

**Field Details:**

| Field | Type   | Required | Description            |
| ----- | ------ | -------- | ---------------------- |
| email | string | ✅ Yes   | Email yang didaftarkan |
| otp   | string | ✅ Yes   | Kode OTP 6 digit       |

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Email verified successfully. You can now login."
}
```

**Error Response (400 Bad Request):**

```json
{
  "message": "Invalid or expired OTP"
}
```

**Error Response (404 Not Found):**

```json
{
  "message": "User not found"
}
```

---

### 3. Resend OTP

Mengirim ulang OTP jika user belum menerima atau OTP sudah expired.

**Endpoint:** `POST /api/email/resend-otp`

**Request Body:**

```json
{
  "email": "dzikrirazzan@gmail.com"
}
```

**Success Response (200 OK):**

```json
{
  "message": "OTP sent successfully"
}
```

**Error Response (200 - email sudah verified):**

```json
{
  "message": "Email already verified"
}
```

**Error Response (404 Not Found):**

```json
{
  "message": "User not found"
}
```

**Error Response (500 Internal Server Error):**

```json
{
  "message": "Failed to send OTP email"
}
```

---

### 4. Login (dengan email verification check)

Login user. Jika email belum diverifikasi, akan ditolak.

**Endpoint:** `POST /api/login`

**Request Body:**

```json
{
  "email": "dzikrirazzan@gmail.com",
  "password": "password123"
}
```

**Success Response (200 OK) - Email Verified:**

```json
{
  "user": {
    "id": 1,
    "name": "Dzikri Razzan",
    "email": "dzikrirazzan@gmail.com",
    "role": "user",
    "email_verified_at": "2025-10-14T10:35:00.000000Z"
  },
  "access_token": "1|laravel_sanctum_token...",
  "token_type": "Bearer",
  "expires_in": 525600,
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhb..."
}
```

**Error Response (403 Forbidden) - Email NOT Verified:**

```json
{
  "message": "Please verify your email address first. Check your inbox for OTP code.",
  "email_verification_required": true,
  "email": "dzikrirazzan@gmail.com"
}
```

**Error Response (401 Unauthorized) - Wrong Credentials:**

```json
{
  "message": "Email atau kata sandi salah. Coba lagi atau klik Lupa kata sandi untuk mengatur ulang."
}
```

---

## 📧 Email Template Preview

Email yang diterima user akan berisi:

**Subject:** `Verify Your Email - SIGAP UNDIP`

**Body:**

```
Hi [User Name],

Your verification code is: 123456

This code will expire in 10 minutes.

If you didn't request this code, please ignore this email.

Thanks,
SIGAP UNDIP Team
```

---

## ⏱️ OTP Specifications

| Property       | Value                       |
| -------------- | --------------------------- |
| Length         | 6 digits                    |
| Expiration     | 10 minutes                  |
| Type           | Numeric only (0-9)          |
| Storage        | Database table `email_otps` |
| Email Delivery | Via Postmark SMTP           |

---

## 💻 Frontend Implementation Guide

### React/Next.js Code Examples

#### 1. Registration Page

```tsx
import { useState } from "react";
import { useRouter } from "next/navigation";

export default function RegisterPage() {
  const router = useRouter();
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    password: "",
    no_telp: "",
    nim: "",
    jurusan: "",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const response = await fetch("https://sigap-api-5hk6r.ondigitalocean.app/api/register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (response.ok) {
        // Registration success, redirect to OTP verification
        router.push(`/verify-email?email=${encodeURIComponent(formData.email)}`);
      } else {
        setError(data.message || "Registration failed");
      }
    } catch (err) {
      setError("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleRegister}>
      <h1>Register</h1>

      {error && <div className="alert alert-error">{error}</div>}

      <input type="text" placeholder="Full Name" value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required />

      <input type="email" placeholder="Email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} required />

      <input type="password" placeholder="Password (min 6 characters)" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} required minLength={6} />

      <input type="tel" placeholder="Phone Number" value={formData.no_telp} onChange={(e) => setFormData({ ...formData, no_telp: e.target.value })} required />

      <input type="text" placeholder="NIM (Optional)" value={formData.nim} onChange={(e) => setFormData({ ...formData, nim: e.target.value })} />

      <input type="text" placeholder="Jurusan (Optional)" value={formData.jurusan} onChange={(e) => setFormData({ ...formData, jurusan: e.target.value })} />

      <button type="submit" disabled={loading}>
        {loading ? "Registering..." : "Register"}
      </button>
    </form>
  );
}
```

#### 2. Email Verification Page

```tsx
import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";

export default function VerifyEmailPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const email = searchParams.get("email");

  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [resending, setResending] = useState(false);

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const response = await fetch("https://sigap-api-5hk6r.ondigitalocean.app/api/email/verify-otp", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email, otp }),
      });

      const data = await response.json();

      if (response.ok) {
        setSuccess("Email verified successfully! Redirecting to login...");
        setTimeout(() => {
          router.push("/login");
        }, 2000);
      } else {
        setError(data.message || "Invalid OTP");
      }
    } catch (err) {
      setError("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const handleResendOtp = async () => {
    setResending(true);
    setError("");
    setSuccess("");

    try {
      const response = await fetch("https://sigap-api-5hk6r.ondigitalocean.app/api/email/resend-otp", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email }),
      });

      const data = await response.json();

      if (response.ok) {
        setSuccess("OTP sent successfully! Check your email.");
      } else {
        setError(data.message || "Failed to resend OTP");
      }
    } catch (err) {
      setError("Network error. Please try again.");
    } finally {
      setResending(false);
    }
  };

  return (
    <div>
      <h1>Verify Your Email</h1>
      <p>
        We've sent a 6-digit verification code to <strong>{email}</strong>
      </p>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <form onSubmit={handleVerifyOtp}>
        <input type="text" placeholder="Enter 6-digit OTP" value={otp} onChange={(e) => setOtp(e.target.value.replace(/\D/g, "").slice(0, 6))} required maxLength={6} pattern="\d{6}" />

        <button type="submit" disabled={loading || otp.length !== 6}>
          {loading ? "Verifying..." : "Verify Email"}
        </button>
      </form>

      <div className="resend-section">
        <p>Didn't receive the code?</p>
        <button onClick={handleResendOtp} disabled={resending}>
          {resending ? "Sending..." : "Resend OTP"}
        </button>
      </div>

      <p className="note">Code expires in 10 minutes</p>
    </div>
  );
}
```

#### 3. Login Page (dengan email verification check)

```tsx
import { useState } from "react";
import { useRouter } from "next/navigation";

export default function LoginPage() {
  const router = useRouter();
  const [formData, setFormData] = useState({
    email: "",
    password: "",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const response = await fetch("https://sigap-api-5hk6r.ondigitalocean.app/api/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (response.ok) {
        // Login success - save tokens
        localStorage.setItem("access_token", data.access_token);
        localStorage.setItem("refresh_token", data.refresh_token);
        localStorage.setItem("user", JSON.stringify(data.user));

        router.push("/dashboard");
      } else if (response.status === 403 && data.email_verification_required) {
        // Email not verified - redirect to verification page
        router.push(`/verify-email?email=${encodeURIComponent(data.email)}`);
      } else {
        setError(data.message || "Login failed");
      }
    } catch (err) {
      setError("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleLogin}>
      <h1>Login</h1>

      {error && <div className="alert alert-error">{error}</div>}

      <input type="email" placeholder="Email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} required />

      <input type="password" placeholder="Password" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} required />

      <button type="submit" disabled={loading}>
        {loading ? "Logging in..." : "Login"}
      </button>

      <a href="/register">Don't have an account? Register</a>
      <a href="/forgot-password">Forgot Password?</a>
    </form>
  );
}
```

---

## 🎨 UI/UX Requirements

### Register Page

- Form fields: name, email, password, no_telp, nim (optional), jurusan (optional)
- Validation: email format, password min 6 chars, phone number format
- After success → Auto redirect to verify-email page with email in URL
- Show loading state during registration

### Verify Email Page

- Display email address yang akan diverifikasi
- Input: 6-digit OTP (numeric only, auto-format)
- Timer countdown: "Code expires in 10 minutes"
- Resend button: "Didn't receive code? Resend OTP"
- After success → Show success message → Redirect to login page
- Handle expired OTP with clear error message

### Login Page

- Standard email + password form
- If user belum verify email → Show error & redirect to verify-email page
- Link to register page
- Link to forgot password page

---

## 🧪 Testing dengan cURL

### Test Register

```bash
curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "testuser@gmail.com",
    "password": "password123",
    "no_telp": "081234567890",
    "nim": "24060122130001",
    "jurusan": "Informatika"
  }'
```

### Test Verify OTP

```bash
curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/email/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@gmail.com",
    "otp": "123456"
  }'
```

### Test Resend OTP

```bash
curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/email/resend-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@gmail.com"
  }'
```

### Test Login

```bash
curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@gmail.com",
    "password": "password123"
  }'
```

---

## ❗ Error Handling

### Common Errors & Solutions

| Error                                    | Status | Cause                       | Solution                      |
| ---------------------------------------- | ------ | --------------------------- | ----------------------------- |
| "The email has already been taken"       | 422    | Email sudah terdaftar       | Gunakan email lain atau login |
| "Invalid or expired OTP"                 | 400    | OTP salah/expired           | Cek OTP atau klik Resend      |
| "User not found"                         | 404    | Email tidak ada di database | Register terlebih dahulu      |
| "Email already verified"                 | 200    | Email sudah diverifikasi    | Langsung login saja           |
| "Failed to send OTP email"               | 500    | SMTP/email service error    | Hubungi admin atau coba lagi  |
| "Please verify your email address first" | 403    | Login sebelum verifikasi    | Verifikasi email dulu         |

### Frontend Error Handling Best Practices

```typescript
// Handle API errors properly
try {
  const response = await fetch(url, options);
  const data = await response.json();

  if (!response.ok) {
    // Handle specific status codes
    switch (response.status) {
      case 400:
        setError("Invalid OTP. Please check and try again.");
        break;
      case 403:
        if (data.email_verification_required) {
          router.push(`/verify-email?email=${data.email}`);
        }
        break;
      case 404:
        setError("User not found. Please register first.");
        break;
      case 422:
        // Validation errors
        setError(data.message);
        break;
      case 500:
        setError("Server error. Please try again later.");
        break;
      default:
        setError(data.message || "Something went wrong");
    }
  }
} catch (error) {
  setError("Network error. Please check your connection.");
}
```

---

## 🔒 Security Notes

1. **OTP Expiration**: OTP expired dalam 10 menit untuk keamanan
2. **One-Time Use**: Setiap OTP hanya bisa digunakan sekali
3. **Email Uniqueness**: Email harus unik, tidak bisa register dengan email yang sama
4. **Password Hashing**: Password di-hash menggunakan bcrypt
5. **Token Authentication**: Gunakan Bearer token untuk authenticated requests
6. **Rate Limiting**: Implement rate limiting di frontend untuk prevent spam resend OTP

---

## 📝 Database Schema

### Table: `users`

```sql
id, name, email, password, role, no_telp, nim, jurusan,
email_verified_at, created_at, updated_at
```

### Table: `email_otps`

```sql
id, email, otp, type, used, expires_at, created_at, updated_at
```

**OTP Types:**

- `email_verification` - untuk verifikasi email saat register
- `password_reset` - untuk reset password

---

## 🚀 Deployment & Configuration

### Backend Configuration (Already Set)

✅ Email verification **ENABLED**  
✅ OTP system **WORKING**  
✅ Postmark SMTP **CONFIGURED**  
✅ API endpoints **READY**

**Email Provider:** Postmark SMTP  
**Sender Email:** dzikrirazzan@students.undip.ac.id  
**Base URL:** https://sigap-api-5hk6r.ondigitalocean.app

### Environment Variables

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=d919f595-dacf-4ebe-b85a-fc519efb481b
MAIL_PASSWORD=d919f595-dacf-4ebe-b85a-fc519efb481b
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=dzikrirazzan@students.undip.ac.id
MAIL_FROM_NAME="SIGAP UNDIP"
```

---

## 📞 Support & Troubleshooting

### Jika Ada Masalah:

1. **Email tidak masuk:**

   - Cek folder spam/junk
   - Tunggu 1-2 menit (email delivery delay)
   - Klik "Resend OTP"
   - Pastikan email address benar

2. **OTP expired:**

   - Klik "Resend OTP" untuk mendapat kode baru
   - OTP berlaku 10 menit sejak dikirim

3. **Login ditolak:**

   - Pastikan email sudah diverifikasi (cek email_verified_at)
   - Pastikan password benar
   - Cek role user (hanya role 'user' yang butuh verification)

4. **API error 500:**
   - Cek backend logs
   - Pastikan Postmark SMTP credentials valid
   - Contact backend team

### Contact

Jika ada pertanyaan atau issue, contact:

- Backend Developer: dzikrirazzan@students.undip.ac.id
- GitHub Repository: https://github.com/dzikrirazzan/sigap_api

---

## 🔗 Related Documentation

- [Forgot Password API Docs](./FORGOT_PASSWORD_API_DOCS.md)
- [Gmail SMTP Setup Guide](./GMAIL_SETUP.md) (alternative provider)
- [Main API Documentation](./README.md)

---

## ✅ Frontend Implementation Checklist

- [ ] Register page dengan form lengkap
- [ ] Verify email page dengan OTP input
- [ ] Resend OTP functionality
- [ ] Login page dengan email verification check
- [ ] Error handling untuk semua scenarios
- [ ] Loading states pada semua actions
- [ ] Success messages dan redirects
- [ ] Email validation
- [ ] Password strength validation (min 6 chars)
- [ ] OTP input validation (6 digits, numeric only)
- [ ] Responsive design untuk mobile
- [ ] Accessibility (a11y) considerations

---

## 📊 Success Metrics

Setelah implementasi, pastikan:

- ✅ User bisa register dan terima OTP via email
- ✅ OTP verification berhasil dan email_verified_at terisi
- ✅ User tidak bisa login sebelum verifikasi email
- ✅ Resend OTP berfungsi dengan baik
- ✅ Error messages jelas dan helpful
- ✅ Flow seamless dari register → verify → login

---

**Last Updated:** October 14, 2025  
**API Version:** 1.0  
**Status:** ✅ Production Ready
