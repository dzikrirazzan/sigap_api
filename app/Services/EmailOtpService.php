<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Models\User;
use App\Mail\EmailVerificationOtp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailOtpService
{
    /**
     * Send OTP for email verification
     */
    public function sendEmailVerificationOtp($email)
    {
        try {
            // Generate new OTP
            $otpRecord = EmailOtp::generateEmailVerificationOtp($email);

            // Get user details for email template
            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::error('User not found for OTP email: ' . $email);
                return false;
            }

            // Send OTP email
            Mail::to($email)->send(new EmailVerificationOtp($user, $otpRecord->otp));

            Log::info('OTP email sent successfully to: ' . $email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify OTP and mark email as verified
     */
    public function verifyEmailOtp($email, $otp)
    {
        try {
            // Verify OTP
            $isValidOtp = EmailOtp::verifyEmailOtp($email, $otp);

            if (!$isValidOtp) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ];
            }

            // Find user and mark email as verified
            $user = User::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            if ($user->hasVerifiedEmail()) {
                return [
                    'success' => true,
                    'message' => 'Email sudah diverifikasi',
                    'user' => $user,
                    'already_verified' => true
                ];
            }

            // Mark email as verified
            $user->markEmailAsVerified();

            // Generate tokens for auto-login
            $token = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createRefreshToken();

            Log::info('Email verified successfully for: ' . $email);

            return [
                'success' => true,
                'message' => 'Email berhasil diverifikasi',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration') * 60,
                'refresh_token' => $refreshToken->token,
                'auto_login' => true
            ];
        } catch (\Exception $e) {
            Log::error('OTP verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verifikasi gagal'
            ];
        }
    }

    /**
     * Clean expired OTPs (can be called via scheduled job)
     */
    public function cleanExpiredOtps()
    {
        $deleted = EmailOtp::where('expires_at', '<', now())->delete();
        Log::info("Cleaned {$deleted} expired OTP records");
        return $deleted;
    }

    /**
     * Send OTP for password reset
     */
    public function sendPasswordResetOtp($user)
    {
        try {
            // Generate new OTP for password reset
            $otpRecord = EmailOtp::generatePasswordResetOtp($user->email);

            // Send OTP email (you can create separate mail class or reuse existing)
            Mail::to($user->email)->send(new \App\Mail\PasswordResetOtp($user, $otpRecord->otp));

            Log::info('Password reset OTP sent successfully to: ' . $user->email);

            return [
                'success' => true,
                'message' => 'OTP reset password berhasil dikirim'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal mengirim email OTP'
            ];
        }
    }

    /**
     * Verify password reset OTP
     */
    public function verifyPasswordResetOtp($user, $otp)
    {
        try {
            // Verify OTP
            $isValidOtp = EmailOtp::verifyPasswordResetOtp($user->email, $otp);

            if (!$isValidOtp) {
                return [
                    'success' => false,
                    'message' => 'OTP tidak valid atau sudah kedaluwarsa'
                ];
            }

            Log::info('Password reset OTP verified for: ' . $user->email);

            return [
                'success' => true,
                'message' => 'OTP berhasil diverifikasi'
            ];
        } catch (\Exception $e) {
            Log::error('Password reset OTP verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verifikasi gagal'
            ];
        }
    }

    /**
     * Mark OTP as used
     */
    public function markOtpAsUsed($email, $otp, $type = 'password_reset')
    {
        EmailOtp::where('email', $email)
            ->where('otp', $otp)
            ->where('type', $type)
            ->where('used', false)
            ->update(['used' => true]);

        Log::info("OTP marked as used for: {$email}");
    }
}
