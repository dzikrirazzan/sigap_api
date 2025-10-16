<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $token;
    private $baseUrl;

    public function __construct()
    {
        $this->token = config('services.fonnte.token', 'tgX3Wbv4zgGaVSDU749z');
        $this->baseUrl = 'https://api.fonnte.com';
    }

    /**
     * Send WhatsApp message to single target
     */
    public function sendMessage($phoneNumber, $message, $options = [])
    {
        try {
            $payload = [
                'target' => $this->formatPhoneNumber($phoneNumber),
                'message' => $message,
                'countryCode' => '62',
                'delay' => $options['delay'] ?? '2',
                'typing' => $options['typing'] ?? false,
                'schedule' => $options['schedule'] ?? 0,
            ];

            // Add optional parameters
            if (isset($options['url'])) {
                $payload['url'] = $options['url'];
            }

            if (isset($options['filename'])) {
                $payload['filename'] = $options['filename'];
            }

            if (isset($options['location'])) {
                $payload['location'] = $options['location'];
            }

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->asForm()->post($this->baseUrl . '/send', $payload);

            $result = $response->json();

            Log::info('WhatsApp message sent', [
                'phone' => $phoneNumber,
                'response' => $result
            ]);

            return [
                'success' => $response->successful() && isset($result['status']) && $result['status'] === true,
                'data' => $result,
                'message' => $result['reason'] ?? 'Pesan berhasil dikirim'
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp send error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Send WhatsApp message to multiple targets
     */
    public function sendBulkMessage($targets, $message, $options = [])
    {
        try {
            // Format targets: phone|name|role,phone|name|role
            $formattedTargets = [];
            foreach ($targets as $target) {
                $phone = $this->formatPhoneNumber($target['phone']);
                $name = $target['name'] ?? 'User';
                $role = $target['role'] ?? 'Member';
                $formattedTargets[] = "{$phone}|{$name}|{$role}";
            }

            $payload = [
                'target' => implode(',', $formattedTargets),
                'message' => $message,
                'countryCode' => '62',
                'delay' => $options['delay'] ?? '2',
                'typing' => $options['typing'] ?? false,
                'schedule' => $options['schedule'] ?? 0,
            ];

            // Add optional parameters
            if (isset($options['url'])) {
                $payload['url'] = $options['url'];
            }

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->asForm()->post($this->baseUrl . '/send', $payload);

            $result = $response->json();

            Log::info('WhatsApp bulk message sent', [
                'targets_count' => count($targets),
                'response' => $result
            ]);

            return [
                'success' => $response->successful() && isset($result['status']) && $result['status'] === true,
                'data' => $result,
                'message' => $result['reason'] ?? 'Pesan massal berhasil dikirim'
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp bulk send error', [
                'targets_count' => count($targets),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Send emergency alert to on-duty volunteers
     */
    public function sendEmergencyAlert($panicReport, $onDutyVolunteers)
    {
        if (empty($onDutyVolunteers)) {
            return [
                'success' => false,
                'message' => 'Tidak ada relawan yang bertugas untuk diberitahu'
            ];
        }

        // Prepare targets for bulk message
        $targets = [];
        foreach ($onDutyVolunteers as $volunteer) {
            if ($volunteer->no_telp) {
                $targets[] = [
                    'phone' => $volunteer->no_telp,
                    'name' => $volunteer->name,
                    'role' => 'Relawan'
                ];
            }
        }

        if (empty($targets)) {
            return [
                'success' => false,
                'message' => 'Tidak ada relawan dengan nomor telepon yang ditemukan'
            ];
        }

        // Create emergency message
        $message = $this->createEmergencyMessage($panicReport);

        // Add location if available
        $options = ['delay' => '1']; // Faster for emergency
        if ($panicReport->latitude && $panicReport->longitude) {
            $options['location'] = "{$panicReport->latitude}, {$panicReport->longitude}";
        }

        return $this->sendBulkMessage($targets, $message, $options);
    }

    /**
     * Create emergency message template
     */
    private function createEmergencyMessage($panicReport)
    {
        $timestamp = $panicReport->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s');
        $reporterName = $panicReport->user->name ?? 'Unknown';
        $reporterPhone = $panicReport->user->no_telp ?? 'Not provided';

        $message = "*PANIC ALERT - SIGAP UNDIP*\n\n";
        $message .= "Halo {name}! Ada laporan darurat yang memerlukan perhatian:\n\n";
        $message .= "📋 *Detail Laporan:*\n";
        $message .= "• Waktu: {$timestamp} WIB\n";
        $message .= "• Pelapor: {$reporterName}\n";
        $message .= "• Kontak: {$reporterPhone}\n\n";

        if ($panicReport->deskripsi) {
            $message .= "• Deskripsi: {$panicReport->deskripsi}\n";
        }

        if ($panicReport->latitude && $panicReport->longitude) {
            $message .= "• Lokasi: {$panicReport->latitude}, {$panicReport->longitude}\n";
            $message .= "• Maps: https://maps.google.com/?q={$panicReport->latitude},{$panicReport->longitude}\n";
        }

        $message .= "\n🔗 *Link Akses Dashboard Relawan:* https://www.sigapundip.xyz\n\n";
        $message .= "_Pesan otomatis dari Sistem SIGAP UNDIP_";
        return $message;
    }

    /**
     * Send shift reminder to volunteers (DISABLED)
     * This feature has been disabled per user request
     */
    public function sendShiftReminder($volunteer, $shift)
    {
        // FEATURE DISABLED: Shift reminder functionality has been turned off
        Log::info('Shift reminder feature is disabled', [
            'volunteer_id' => $volunteer->id ?? 'unknown',
            'volunteer_name' => $volunteer->name ?? 'unknown'
        ]);

        return [
            'success' => false,
            'message' => 'Fitur pengingat shift saat ini dinonaktifkan',
            'disabled' => true
        ];
    }

    /**
     * Format phone number to Indonesian format
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Convert to Indonesian format
        if (substr($phone, 0, 1) === '0') {
            // Replace leading 0 with 62
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) !== '62') {
            // Add 62 if not present
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Get account info from Fonnte
     */
    public function getAccountInfo()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->baseUrl . '/validate-token');

            return $response->json();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if WhatsApp service is configured properly
     */
    public function isConfigured()
    {
        return !empty($this->token) && $this->token !== 'your-fonnte-token';
    }
}
