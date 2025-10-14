# 📧 Email Verification untuk Register - API Documentation

## Overview

Sistem registrasi dengan email verification menggunakan OTP (One-Time Password) yang dikirim via email. User harus verifikasi email sebelum bisa login.

---

## 🔄 Registration & Email Verification Flow

```
1. User Register → API kirim OTP ke email
2. User cek email → dapat OTP 6 digit
3. User input OTP → API verifikasi
4. Email verified → User bisa login
```

---

## 📋 API Endpoints

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
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | ✅ Yes | Nama lengkap user |
| email | string | ✅ Yes | Email valid & unique |
| password | string | ✅ Yes | Minimal 6 karakter |
| no_telp | string | ✅ Yes | Nomor telepon (max 15 digit) |
| nim | string | ❌ Optional | Nomor Induk Mahasiswa (max 25 char) |
| jurusan | string | ❌ Optional | Jurusan/Program Studi (max 100 char) |

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
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | ✅ Yes | Email yang didaftarkan |
| otp | string | ✅ Yes | Kode OTP 6 digit |

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
| Email Delivery | Via Gmail SMTP              |

---

## 🎯 Frontend Implementation Guide

### React/Next.js Example

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

## 🔒 Security Notes

1. **OTP Expiration**: OTP expired dalam 10 menit untuk keamanan
2. **One-Time Use**: Setiap OTP hanya bisa digunakan sekali
3. **Email Uniqueness**: Email harus unik, tidak bisa register dengan email yang sama
4. **Password Hashing**: Password di-hash menggunakan bcrypt
5. **Token Authentication**: Gunakan Bearer token untuk authenticated requests

---

## ❗ Error Handling

### Common Errors & Solutions

| Error                                    | Status | Cause                       | Solution                      |
| ---------------------------------------- | ------ | --------------------------- | ----------------------------- |
| "The email has already been taken"       | 422    | Email sudah terdaftar       | Gunakan email lain atau login |
| "Invalid or expired OTP"                 | 400    | OTP salah/expired           | Cek OTP atau resend           |
| "User not found"                         | 404    | Email tidak ada di database | Register terlebih dahulu      |
| "Email already verified"                 | 200    | Email sudah diverifikasi    | Langsung login saja           |
| "Failed to send OTP email"               | 500    | SMTP/email service error    | Hubungi admin atau coba lagi  |
| "Please verify your email address first" | 403    | Login sebelum verifikasi    | Verifikasi email dulu         |

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

## 🚀 Deployment Checklist

Sebelum deploy ke production, pastikan:

- ✅ Gmail SMTP sudah dikonfigurasi di `.env`
- ✅ `MAIL_FROM_ADDRESS` menggunakan email Gmail yang valid
- ✅ App Password Gmail sudah di-generate
- ✅ Environment variables sudah di-update di DigitalOcean
- ✅ Test email delivery berhasil
- ✅ Frontend sudah handle semua response (success & error)
- ✅ Frontend redirect flow sudah benar (register → verify → login)

---

## 📞 Support

Jika ada pertanyaan atau issue:

1. Cek error message dari API response
2. Cek email spam/junk folder
3. Test dengan cURL untuk isolasi masalah frontend/backend
4. Contact backend team dengan detail error

---

## 🔗 Related Documentation

- [Forgot Password API Docs](./FORGOT_PASSWORD_API_DOCS.md)
- [Gmail SMTP Setup Guide](./GMAIL_SETUP.md)
- [Main API Documentation](./README.md)
