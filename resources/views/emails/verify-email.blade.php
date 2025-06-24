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
                    Terima kasih telah mendaftar di SIGAP UNDIP. Untuk mengaktifkan akun Anda, silakan klik tombol
                    verifikasi di bawah ini.
                </p>

                <!-- Verification Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $verificationUrl }}"
                        style="display: inline-block; background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                        Verifikasi Email
                    </a>
                </div>

                <p style="margin: 20px 0; color: #666; font-size: 14px;">
                    <strong>Penting:</strong> Link verifikasi ini akan kedaluwarsa dalam 60 menit.
                    Jika Anda tidak membuat akun ini, silakan abaikan email ini.
                </p>

                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

                <p style="margin: 0; color: #999; font-size: 12px; line-height: 1.4;">
                    Jika Anda mengalami kesulitan mengklik tombol "Verifikasi Email", salin dan tempel URL di bawah ini
                    ke browser web Anda:<br>
                    <a href="{{ $verificationUrl }}"
                        style="color: #007bff; word-break: break-all;">{{ $verificationUrl }}</a>
                </p>

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