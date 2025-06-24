<?php

namespace App\Services;

use App\Models\PanicReport;
use App\Models\User;
use App\Models\RelawanShift;
use App\Models\RelawanShiftPattern;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\EmergencyAlert;
use Carbon\Carbon;

class EmergencyEmailService
{
    /**
     * Send emergency alert to all on-duty relawan
     */
    public function sendEmergencyAlert(PanicReport $panic)
    {
        try {
            // Get relawan yang sedang jaga hari ini
            $onDutyRelawans = $this->getTodayOnDutyRelawans();

            if ($onDutyRelawans->isEmpty()) {
                Log::warning('No relawan on duty for emergency alert', [
                    'panic_id' => $panic->id
                ]);
                return false;
            }

            $successCount = 0;
            $failCount = 0;

            foreach ($onDutyRelawans as $relawan) {
                try {
                    Mail::to($relawan->email)
                        ->send(new EmergencyAlert($panic, $relawan));

                    $successCount++;

                    Log::info('Emergency email sent successfully', [
                        'panic_id' => $panic->id,
                        'relawan_id' => $relawan->id,
                        'relawan_email' => $relawan->email
                    ]);
                } catch (\Exception $e) {
                    $failCount++;

                    Log::error('Failed to send emergency email', [
                        'panic_id' => $panic->id,
                        'relawan_id' => $relawan->id,
                        'relawan_email' => $relawan->email,
                        'error' => $e->getMessage()
                    ]);
                }

                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
            }

            // Log summary
            Log::info('Emergency email notification summary', [
                'panic_id' => $panic->id,
                'total_relawan' => $onDutyRelawans->count(),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ]);

            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error('Emergency email service error', [
                'panic_id' => $panic->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get today's on-duty relawan
     */
    private function getTodayOnDutyRelawans()
    {
        $today = Carbon::now()->toDateString();
        $dayOfWeek = strtolower(Carbon::now()->format('l'));

        // Priority 1: Actual shifts
        $actualShifts = RelawanShift::where('shift_date', $today)
            ->with('relawan:id,name,email,no_telp')
            ->get()
            ->pluck('relawan')
            ->filter();

        if ($actualShifts->isNotEmpty()) {
            return $actualShifts;
        }

        // Priority 2: Weekly patterns  
        $patterns = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->with('relawan:id,name,email,no_telp')
            ->get();

        return $patterns->pluck('relawan')->filter();
    }

    /**
     * Send test emergency email
     */
    public function sendTestEmergencyEmail($relawanEmail)
    {
        try {
            // Create dummy panic data for testing
            $testPanic = new \stdClass();
            $testPanic->id = 9999;
            $testPanic->description = 'Ini adalah test emergency alert dari sistem SIGAP';
            $testPanic->latitude = -6.2088;
            $testPanic->longitude = 106.8456;
            $testPanic->status = 'pending';
            $testPanic->created_at = now();

            $testUser = new \stdClass();
            $testUser->name = 'Test User';
            $testUser->email = 'test@example.com';
            $testUser->no_telp = '081234567890';

            $testPanic->user = $testUser;

            $testRelawan = new \stdClass();
            $testRelawan->name = 'Test Relawan';
            $testRelawan->email = $relawanEmail;

            Mail::to($relawanEmail)->send(new EmergencyAlert($testPanic, $testRelawan));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send test emergency email', [
                'email' => $relawanEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
