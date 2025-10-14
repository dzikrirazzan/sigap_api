# Gmail SMTP Configuration Guide

## Cara Mendapatkan Gmail App Password

### Langkah 1: Aktifkan 2-Step Verification

1. Buka [Google Account Security](https://myaccount.google.com/security)
2. Cari bagian "2-Step Verification"
3. Klik dan ikuti instruksi untuk mengaktifkan 2FA (wajib untuk App Password)

### Langkah 2: Generate App Password

1. Buka [App Passwords](https://myaccount.google.com/apppasswords)
2. Atau dari Google Account → Security → 2-Step Verification → App passwords (paling bawah)
3. Pilih "Mail" dan "Other (Custom name)"
4. Ketik nama: "SIGAP Emergency API"
5. Klik "Generate"
6. Copy **16-character password** yang muncul (format: xxxx xxxx xxxx xxxx)

### Langkah 3: Update .env File

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=dzikrirazzan@gmail.com
MAIL_PASSWORD=your-16-char-app-password  # Paste app password tanpa spasi
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="dzikrirazzan@gmail.com"
MAIL_FROM_NAME="SIGAP UNDIP"
```

### Langkah 4: Test Email Configuration

```bash
# Test via API endpoint
curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{"email":"your-test-email@example.com"}'
```

## Spesifikasi Gmail SMTP

| Setting        | Value                      |
| -------------- | -------------------------- |
| Host           | smtp.gmail.com             |
| Port           | 587 (TLS) atau 465 (SSL)   |
| Encryption     | TLS atau SSL               |
| Authentication | Required                   |
| Username       | Email Gmail lengkap        |
| Password       | App Password (16 karakter) |
| Daily Limit    | 500 emails/day             |

## Troubleshooting

### Error: "Username and Password not accepted"

- Pastikan 2-Step Verification sudah aktif
- Pastikan menggunakan App Password, BUKAN password Gmail biasa
- Pastikan App Password tidak ada spasi

### Error: "Connection timeout"

- Pastikan port 587 tidak diblokir firewall
- Coba gunakan port 465 dengan SSL:
  ```
  MAIL_PORT=465
  MAIL_ENCRYPTION=ssl
  ```

### Email Masuk ke Spam

- Tambahkan SPF record di domain (jika kirim dari custom domain)
- Minta penerima add sender ke whitelist
- Gunakan domain email yang sama dengan sender (gunakan Gmail → Gmail)

## Keuntungan Gmail SMTP

✅ Gratis hingga 500 email/hari
✅ Reliable dan stabil
✅ Setup mudah dengan App Password
✅ Email jarang masuk spam (trusted domain)
✅ Support monitoring via Gmail Sent folder
✅ Tidak perlu verifikasi domain

## Limitasi

⚠️ 500 emails per day limit
⚠️ Butuh 2-Step Verification aktif
⚠️ Tidak cocok untuk email blast besar
⚠️ Sender email harus dari Gmail account yang sama

## Alternative Configuration (Port 465)

Jika port 587 terblokir, gunakan SSL port 465:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=dzikrirazzan@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="dzikrirazzan@gmail.com"
MAIL_FROM_NAME="SIGAP UNDIP"
```

## Update di DigitalOcean App Platform

1. Masuk ke [DigitalOcean Dashboard](https://cloud.digitalocean.com/apps)
2. Pilih app "sigap-api-5hk6r"
3. Settings → Environment Variables
4. Update variabel berikut:
   - `MAIL_HOST=smtp.gmail.com`
   - `MAIL_PORT=587`
   - `MAIL_USERNAME=dzikrirazzan@gmail.com`
   - `MAIL_PASSWORD=your-app-password` (encrypt as secret)
   - `MAIL_ENCRYPTION=tls`
   - `MAIL_FROM_ADDRESS=dzikrirazzan@gmail.com`
5. Klik "Save" → App akan redeploy otomatis

## Testing

Setelah konfigurasi, test dengan:

```bash
# Test forgot password endpoint
curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{"email":"dzikrirazzan@gmail.com"}'

# Expected response:
{
  "message": "Password reset OTP has been sent to your email",
  "expires_in_minutes": 10
}
```

Cek inbox Gmail Anda untuk OTP code.
