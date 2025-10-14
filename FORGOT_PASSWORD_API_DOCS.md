# 🔐 SIGAP UNDIP - Forgot Password API Documentation

## Overview

Fitur Forgot Password menggunakan sistem **OTP via Email** dengan masa berlaku 10 menit.

---

## 📋 Flow Forgot Password

```
1. User input email
2. Backend kirim OTP ke email
3. User input OTP + password baru
4. Backend verify OTP dan reset password
5. User login dengan password baru
```

---

## 🔌 API Endpoints

### Base URL

```
Production: http://152.42.171.87/api
Development: http://localhost/api
```

---

## 1️⃣ Send Password Reset OTP

### **Endpoint:**

```http
POST /api/password/forgot
```

### **Request Body:**

```json
{
  "email": "user@example.com"
}
```

### **Success Response (200):**

```json
{
  "message": "Password reset OTP has been sent to your email",
  "expires_in_minutes": 10
}
```

### **Error Responses:**

**Email tidak ditemukan (422):**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The selected email is invalid."]
  }
}
```

**Gagal kirim email (500):**

```json
{
  "message": "Failed to send OTP email"
}
```

### **Frontend Implementation Example:**

```javascript
// React/Next.js Example
const handleForgotPassword = async (email) => {
  try {
    const response = await fetch("http://152.42.171.87/api/password/forgot", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ email }),
    });

    const data = await response.json();

    if (response.ok) {
      // Show success message
      toast.success("OTP telah dikirim ke email Anda. Cek inbox/spam.");
      // Navigate to OTP verification page
      router.push("/verify-otp");
    } else {
      toast.error(data.message || "Email tidak ditemukan");
    }
  } catch (error) {
    toast.error("Terjadi kesalahan. Silakan coba lagi.");
  }
};
```

---

## 2️⃣ Verify Password Reset OTP (Optional Step)

### **Endpoint:**

```http
POST /api/password/verify-otp
```

### **Request Body:**

```json
{
  "email": "user@example.com",
  "otp": "123456"
}
```

### **Success Response (200):**

```json
{
  "message": "OTP verified successfully. You can now reset your password.",
  "email": "user@example.com"
}
```

### **Error Response (400):**

```json
{
  "message": "Invalid or expired OTP"
}
```

**Note:** Step ini **opsional**. Anda bisa langsung ke step 3 (Reset Password) yang sudah include verifikasi OTP.

---

## 3️⃣ Reset Password with OTP

### **Endpoint:**

```http
POST /api/password/reset
```

### **Request Body:**

```json
{
  "email": "user@example.com",
  "otp": "123456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### **Success Response (200):**

```json
{
  "message": "Password has been reset successfully. Please login with your new password."
}
```

### **Error Responses:**

**OTP tidak valid (400):**

```json
{
  "message": "Invalid or expired OTP"
}
```

**Password tidak match (422):**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "password": ["The password confirmation does not match."]
  }
}
```

### **Frontend Implementation Example:**

```javascript
const handleResetPassword = async (email, otp, password, passwordConfirmation) => {
  try {
    const response = await fetch("http://152.42.171.87/api/password/reset", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        email,
        otp,
        password,
        password_confirmation: passwordConfirmation,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      toast.success("Password berhasil direset! Silakan login.");
      router.push("/login");
    } else {
      toast.error(data.message || "Gagal reset password");
    }
  } catch (error) {
    toast.error("Terjadi kesalahan. Silakan coba lagi.");
  }
};
```

---

## 4️⃣ Resend Password Reset OTP

### **Endpoint:**

```http
POST /api/password/resend-otp
```

### **Request Body:**

```json
{
  "email": "user@example.com"
}
```

### **Success Response (200):**

```json
{
  "message": "New password reset OTP has been sent to your email",
  "expires_in_minutes": 10
}
```

### **Error Response (500):**

```json
{
  "message": "Failed to send OTP email"
}
```

---

## 📧 Email Format

User akan menerima email dengan format:

**Subject:** `Password Reset OTP - SIGAP UNDIP`

**Content:**

```
🚨 SIGAP UNDIP
Password Reset Request

Hello [User Name],

We received a request to reset your password for your SIGAP UNDIP account.
Use the OTP code below to reset your password:

┌─────────────────┐
│  Your OTP Code: │
│    123456       │
│ Valid for 10 min│
└─────────────────┘

⚠️ Security Notice:
• This OTP is valid for 10 minutes only
• Never share this OTP with anyone
• If you didn't request a password reset, please ignore this email
```

---

## 🎨 Frontend Pages Needed

### 1. **Forgot Password Page** (`/forgot-password`)

```jsx
- Input: Email
- Button: Send OTP
- Link: Back to Login
```

### 2. **Verify OTP & Reset Password Page** (`/reset-password`)

```jsx
- Input: Email (readonly/hidden)
- Input: OTP (6 digits)
- Input: New Password
- Input: Confirm Password
- Button: Reset Password
- Link: Resend OTP
- Link: Back to Login
```

---

## ✅ Validation Rules

### Email:

- Required
- Valid email format
- Must exist in database

### OTP:

- Required
- Exactly 6 digits
- Must be valid and not expired (10 minutes)
- Must not be used before

### Password:

- Required
- Minimum 6 characters
- Must match with password_confirmation

---

## 🔒 Security Features

1. **OTP Expiry:** 10 minutes
2. **One-time Use:** OTP can only be used once
3. **Token Revocation:** All user tokens are deleted after password reset
4. **Email Verification:** Only registered emails can request OTP
5. **Auto Cleanup:** Old OTPs are marked as used

---

## 📱 User Experience Flow

```
┌─────────────────┐
│ Login Page      │
│ "Forgot Pass?"  │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Enter Email     │
│ [Submit]        │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Check Email     │
│ OTP Sent!       │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Enter OTP +     │
│ New Password    │
│ [Reset]         │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Success!        │
│ Login Now       │
└─────────────────┘
```

---

## 🧪 Testing

### Test dengan Postman/curl:

```bash
# 1. Send OTP
curl -X POST http://152.42.171.87/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com"}'

# 2. Reset Password
curl -X POST http://152.42.171.87/api/password/reset \
  -H "Content-Type: application/json" \
  -d '{
    "email":"user@example.com",
    "otp":"123456",
    "password":"newpass123",
    "password_confirmation":"newpass123"
  }'
```

---

## ⚠️ Important Notes for Frontend Team

1. **OTP Expiry:** Tampilkan countdown timer 10 menit di UI
2. **Error Handling:** Handle semua error responses dengan toast/alert
3. **Loading States:** Tampilkan loading indicator saat API call
4. **Email Case:** Email tidak case-sensitive di backend
5. **Token Cleanup:** Setelah reset, user harus login ulang (semua token dihapus)
6. **Resend Limit:** Tidak ada rate limiting di backend (bisa tambah di frontend)

---

## 🎯 Complete React/Next.js Component Example

```jsx
// pages/forgot-password.jsx
import { useState } from "react";
import { useRouter } from "next/router";
import { toast } from "react-hot-toast";

export default function ForgotPassword() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/password/forgot`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });

      const data = await res.json();

      if (res.ok) {
        toast.success("OTP telah dikirim ke email Anda!");
        router.push(`/reset-password?email=${encodeURIComponent(email)}`);
      } else {
        toast.error(data.message || "Email tidak ditemukan");
      }
    } catch (error) {
      toast.error("Terjadi kesalahan. Coba lagi.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="max-w-md w-full p-6 bg-white rounded-lg shadow-md">
        <h2 className="text-2xl font-bold mb-6">Lupa Password</h2>

        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Email</label>
            <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} className="w-full px-3 py-2 border rounded-md" required />
          </div>

          <button type="submit" disabled={loading} className="w-full bg-red-600 text-white py-2 rounded-md hover:bg-red-700 disabled:opacity-50">
            {loading ? "Mengirim..." : "Kirim OTP"}
          </button>
        </form>

        <button onClick={() => router.push("/login")} className="w-full mt-4 text-sm text-gray-600 hover:text-gray-800">
          Kembali ke Login
        </button>
      </div>
    </div>
  );
}
```

```jsx
// pages/reset-password.jsx
import { useState, useEffect } from "react";
import { useRouter } from "next/router";
import { toast } from "react-hot-toast";

export default function ResetPassword() {
  const router = useRouter();
  const { email } = router.query;

  const [otp, setOtp] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);
  const [countdown, setCountdown] = useState(600); // 10 minutes

  useEffect(() => {
    if (countdown > 0) {
      const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [countdown]);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (password !== passwordConfirmation) {
      toast.error("Password tidak cocok");
      return;
    }

    setLoading(true);

    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/password/reset`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          email,
          otp,
          password,
          password_confirmation: passwordConfirmation,
        }),
      });

      const data = await res.json();

      if (res.ok) {
        toast.success("Password berhasil direset!");
        router.push("/login");
      } else {
        toast.error(data.message || "Gagal reset password");
      }
    } catch (error) {
      toast.error("Terjadi kesalahan. Coba lagi.");
    } finally {
      setLoading(false);
    }
  };

  const handleResendOtp = async () => {
    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/password/resend-otp`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });

      if (res.ok) {
        toast.success("OTP baru telah dikirim!");
        setCountdown(600);
      } else {
        toast.error("Gagal mengirim OTP");
      }
    } catch (error) {
      toast.error("Terjadi kesalahan");
    }
  };

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, "0")}`;
  };

  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="max-w-md w-full p-6 bg-white rounded-lg shadow-md">
        <h2 className="text-2xl font-bold mb-6">Reset Password</h2>

        <div className="mb-4 p-3 bg-yellow-50 border-l-4 border-yellow-400">
          <p className="text-sm text-yellow-700">
            OTP berlaku: <strong>{formatTime(countdown)}</strong>
          </p>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Email</label>
            <input type="email" value={email} readOnly className="w-full px-3 py-2 border rounded-md bg-gray-50" />
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Kode OTP</label>
            <input type="text" value={otp} onChange={(e) => setOtp(e.target.value)} maxLength={6} placeholder="123456" className="w-full px-3 py-2 border rounded-md text-center text-2xl tracking-widest" required />
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Password Baru</label>
            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} className="w-full px-3 py-2 border rounded-md" minLength={6} required />
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Konfirmasi Password</label>
            <input type="password" value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} className="w-full px-3 py-2 border rounded-md" minLength={6} required />
          </div>

          <button type="submit" disabled={loading} className="w-full bg-red-600 text-white py-2 rounded-md hover:bg-red-700 disabled:opacity-50">
            {loading ? "Mereset..." : "Reset Password"}
          </button>
        </form>

        <button
          onClick={handleResendOtp}
          disabled={countdown > 540} // Disable jika baru 1 menit
          className="w-full mt-4 text-sm text-blue-600 hover:text-blue-800 disabled:text-gray-400"
        >
          Kirim Ulang OTP
        </button>

        <button onClick={() => router.push("/login")} className="w-full mt-2 text-sm text-gray-600 hover:text-gray-800">
          Kembali ke Login
        </button>
      </div>
    </div>
  );
}
```

---

## 📞 Support

Jika ada pertanyaan atau masalah:

- Check Laravel logs: `/var/www/emergency_api/storage/logs/laravel.log`
- Test email settings di `.env`
- Verify database migration sudah jalan

---

**Created for SIGAP UNDIP Frontend Team** 🚀
**Last Updated:** October 2025
