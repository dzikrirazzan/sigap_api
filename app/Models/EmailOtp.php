<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'otp',
        'type',
        'expires_at',
        'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Generate a new OTP for email verification
     */
    public static function generateEmailVerificationOtp($email)
    {
        // Delete existing unused OTPs for this email
        self::where('email', $email)
            ->where('type', 'email_verification')
            ->where('used', false)
            ->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'email' => $email,
            'otp' => $otp,
            'type' => 'email_verification',
            'expires_at' => Carbon::now()->addMinutes(10), // 10 minutes expiry
            'used' => false
        ]);
    }

    /**
     * Verify OTP for email verification
     */
    public static function verifyEmailOtp($email, $otp)
    {
        $otpRecord = self::where('email', $email)
            ->where('otp', $otp)
            ->where('type', 'email_verification')
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otpRecord) {
            $otpRecord->update(['used' => true]);
            return true;
        }

        return false;
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired()
    {
        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Check if OTP is valid (not used and not expired)
     */
    public function isValid()
    {
        return !$this->used && !$this->isExpired();
    }
}
