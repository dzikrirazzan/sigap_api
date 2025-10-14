# 🚀 Quick Start Guide - Email Verification Registration

## TL;DR - Untuk Tim Frontend

### 3 API Endpoints yang Perlu Diimplementasikan:

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

## 📱 User Flow

```
┌─────────────┐
│   Register  │ → User isi form register
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Email Sent  │ → API kirim OTP ke email (6 digit)
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Verify OTP  │ → User input OTP dari email
└──────┬──────┘
       │
       ▼
┌─────────────┐
│    Login    │ → User bisa login
└─────────────┘
```

---

## 🎨 UI/UX Requirements

### Register Page

- Form fields: name, email, password, no_telp, nim (optional), jurusan (optional)
- Validation: email format, password min 6 chars
- After success → Auto redirect to verify-email page with email in URL

### Verify Email Page

- Input: 6-digit OTP (numeric only)
- Timer: "Code expires in 10 minutes"
- Resend button: "Didn't receive code? Resend"
- After success → Redirect to login page

### Login Page

- If user belum verify email → Show error & redirect to verify-email page

---

## 💻 Code Example (Simplified)

### Register

```typescript
const response = await fetch("/api/register", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    name: "John Doe",
    email: "john@example.com",
    password: "password123",
    no_telp: "081234567890",
  }),
});

if (response.ok) {
  router.push(`/verify-email?email=${email}`);
}
```

### Verify OTP

```typescript
const response = await fetch("/api/email/verify-otp", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    email: email,
    otp: otp,
  }),
});

if (response.ok) {
  router.push("/login");
}
```

### Resend OTP

```typescript
const response = await fetch("/api/email/resend-otp", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ email: email }),
});
```

---

## ✅ Testing Steps

1. **Register** di `https://sigap-api-5hk6r.ondigitalocean.app/api/register`
2. **Cek email** untuk OTP (expires in 10 minutes)
3. **Verify OTP** di `/api/email/verify-otp`
4. **Login** di `/api/login`

---

## 🐛 Common Issues

| Issue                 | Fix                                    |
| --------------------- | -------------------------------------- |
| Email tidak masuk     | Cek spam folder, atau klik Resend OTP  |
| OTP expired           | Klik Resend OTP untuk dapat kode baru  |
| Login ditolak         | Pastikan sudah verify email dulu       |
| Email sudah terdaftar | Gunakan email lain atau reset password |

---

## 📄 Full Documentation

Untuk detail lengkap lihat: [REGISTER_EMAIL_VERIFICATION_API_DOCS.md](./REGISTER_EMAIL_VERIFICATION_API_DOCS.md)

---

## ⚙️ Backend Status

✅ Email verification ACTIVE  
✅ OTP system working  
✅ Gmail SMTP configured  
✅ API endpoints ready

**Base URL:** `https://sigap-api-5hk6r.ondigitalocean.app`
