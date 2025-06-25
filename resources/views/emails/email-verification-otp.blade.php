<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body
    style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
            <!-- Content -->
            <div style="padding: 30px;">
                <p style="margin-bottom: 20px; font-size: 16px;">
                    Halo <strong>{{ $user->name }}</strong>,
                </p>

                <p style="margin-bottom: 30px;">
                    Terima kasih telah mendaftar di SIGAP UNDIP. Gunakan kode verifikasi di bawah ini untuk mengaktifkan
                    akun Anda:
                </p>

                <!-- OTP Code Box -->
                <div style="text-align: center; margin: 30px 0;">
                    <div
                        style="display: inline-block; background: #f8f9fa; border: 2px dashed #007bff; padding: 20px 40px; border-radius: 8px;">
                        <h2
                            style="margin: 0; font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 8px; font-family: 'Courier New', monospace;">
                            {{ $otp }}
                        </h2>
                    </div>
                </div>

                <p style="margin: 20px 0; color: #666; font-size: 14px; text-align: center;">
                    <strong>Penting:</strong> Kode verifikasi ini akan kedaluwarsa dalam 10 menit.<br>
                    Jangan bagikan kode ini kepada siapa pun.
                </p>

                <div
                    style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #856404; font-size: 14px;">
                        <strong>Peringatan:</strong> Jika Anda tidak membuat akun ini, silakan abaikan email ini dan
                        hubungi administrator.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
                <p style="margin: 0; font-size: 14px; color: #6c757d;">
                    © 2025 SIGAP UNDIP - Sistem Informasi Gawat dan Pelaporan
                </p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #adb5bd;">
                    Ini adalah pesan otomatis. Jangan membalas email ini.
                </p>
            </div>

        </div>
    </div>
</body>

</html>