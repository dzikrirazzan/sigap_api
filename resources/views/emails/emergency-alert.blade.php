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
                <p style="margin: 8px 0 0 0; opacity: 0.9;">Laporan Darurat Baru</p>
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
                    style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #856404; font-size: 16px; font-weight: 600;">Detail Laporan
                    </h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: #666; font-weight: 500;">ID Laporan:</td>
                            <td style="padding: 8px 0; font-weight: 600;">#{{ $panic->id }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #666; font-weight: 500;">Pelapor:</td>
                            <td style="padding: 8px 0;">{{ $panic->user->name }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #666; font-weight: 500;">Kontak:</td>
                            <td style="padding: 8px 0;">{{ $panic->user->no_telp ?? 'Tidak tersedia' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #666; font-weight: 500;">Waktu:</td>
                            <td style="padding: 8px 0;">{{ $panic->created_at->format('d/m/Y H:i') }} WIB</td>
                        </tr>
                    </table>
                </div>

                <!-- Location -->
                <div
                    style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #0c5460; font-size: 16px; font-weight: 600;">📍 Lokasi</h3>
                    <p
                        style="margin: 0 0 15px 0; font-family: monospace; background: white; padding: 10px; border-radius: 4px; border: 1px solid #bee5eb;">
                        Lat: {{ $panic->latitude }}<br>
                        Lng: {{ $panic->longitude }}
                    </p>
                    <a href="https://www.google.com/maps?q={{ $panic->latitude }},{{ $panic->longitude }}"
                        style="display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: 500;">
                        Lihat di Maps
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