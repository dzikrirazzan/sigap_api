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

            <!-- Header -->
            <div style="background: #dc2626; color: white; padding: 30px; text-align: center;">
                <h1 style="margin: 0; font-size: 24px; font-weight: 600;">PANIC ALERT</h1>
            </div>

            <!-- Content -->
            <div style="padding: 30px;">
                <p style="margin-bottom: 20px; font-size: 16px;">
                    Halo <strong>{{ $relawan->name }}</strong>,
                </p>

                <p style="margin-bottom: 30px;">
                    Ada laporan darurat baru yang memerlukan perhatian segera. Anda menerima notifikasi ini karena
                    sedang bertugas hari ini.
                </p>

                <!-- Report Info -->
                <div
                    style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px; font-weight: 600;">Detail Laporan</h3>

                    <div style="margin-bottom: 20px;">
                        <p style="margin: 5px 0; font-size: 14px;"><strong>ID Laporan:</strong> #{{ $panic->id }}</p>
                        <p style="margin: 5px 0; font-size: 14px;"><strong>Pelapor:</strong> {{ $panic->user->name }}
                        </p>
                        <p style="margin: 5px 0; font-size: 14px;"><strong>Kontak:</strong>
                            {{ $panic->user->no_telp ?? 'Tidak tersedia' }}
                        </p>
                        <p style="margin: 5px 0; font-size: 14px;"><strong>Waktu:</strong>
                            {{ $panic->created_at->format('d/m/Y H:i') }} WIB
                        </p>
                    </div>

                    <div style="border-top: 1px solid #dee2e6; padding-top: 15px;">
                        <p style="margin: 0 0 10px 0; font-weight: 600; color: #333;">📍 Lokasi</p>
                        <a href="https://www.google.com/maps?q={{ $panic->latitude }},{{ $panic->longitude }}"
                            style="display: inline-block; background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 500; font-size: 14px;">
                            Lihat Lokasi di Maps
                        </a>
                    </div>
                </div>

                <!-- Action Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="https://sigapundip.xyz/auth/login"
                        style="display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                        Akses Dashboard Relawan
                    </a>
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