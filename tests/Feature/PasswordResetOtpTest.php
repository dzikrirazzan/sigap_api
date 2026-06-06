<?php

namespace Tests\Feature;

use App\Models\EmailOtp;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    public function test_password_reset_otp_can_be_checked_before_it_is_consumed(): void
    {
        $email = 'password-reset-otp-' . uniqid() . '@example.test';
        $otp = EmailOtp::generatePasswordResetOtp($email);

        try {
            $this->assertTrue(EmailOtp::verifyPasswordResetOtp($email, $otp->otp, false));
            $this->assertFalse($otp->fresh()->used);

            $this->assertTrue(EmailOtp::verifyPasswordResetOtp($email, $otp->otp));
            $this->assertTrue($otp->fresh()->used);
        } finally {
            EmailOtp::where('email', $email)->delete();
        }
    }
}
