<?php

// Debug script untuk melihat masalah email di production
// Jalankan via: php artisan tinker < debug-email-production.php

use App\Models\User;
use App\Models\EmailOtp;
use App\Mail\EmailVerificationOtp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

echo "🔍 SIGAP EMAIL DEBUG - PRODUCTION" . PHP_EOL;
echo "=================================" . PHP_EOL . PHP_EOL;

// 1. Cek konfigurasi email
echo "📧 MAIL CONFIGURATION:" . PHP_EOL;
echo "MAIL_MAILER: " . config('mail.default') . PHP_EOL;
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . PHP_EOL;
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . PHP_EOL;
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . PHP_EOL;
echo "MAIL_PASSWORD: " . (config('mail.mailers.smtp.password') ? '***SET***' : 'NOT SET') . PHP_EOL;
echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . PHP_EOL;
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . PHP_EOL;
echo "MAIL_FROM_NAME: " . config('mail.from.name') . PHP_EOL;
echo "QUEUE_CONNECTION: " . config('queue.default') . PHP_EOL;
echo PHP_EOL;

// 2. Test database connection
echo "💾 DATABASE CONNECTION:" . PHP_EOL;
try {
    $userCount = User::count();
    echo "✅ Database connected. Users: " . $userCount . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . PHP_EOL;
}

try {
    $otpCount = EmailOtp::count();
    echo "✅ EmailOTP table accessible. Records: " . $otpCount . PHP_EOL;
} catch (Exception $e) {
    echo "❌ EmailOTP table error: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// 3. Test OTP generation
echo "🔢 OTP GENERATION TEST:" . PHP_EOL;
try {
    $testEmail = '7ddsm@punkproof.com';
    $otp = EmailOtp::generateEmailVerificationOtp($testEmail);
    echo "✅ OTP generated successfully" . PHP_EOL;
    echo "   Email: " . $otp->email . PHP_EOL;
    echo "   OTP: " . $otp->otp . PHP_EOL;
    echo "   Expires: " . $otp->expires_at . PHP_EOL;
} catch (Exception $e) {
    echo "❌ OTP generation failed: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// 4. Test user creation
echo "👤 USER TEST:" . PHP_EOL;
try {
    $testUser = new User();
    $testUser->name = 'Debug Test User';
    $testUser->email = '7ddsm@punkproof.com';
    echo "✅ User object created successfully" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ User creation failed: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// 5. Test email template
echo "📄 EMAIL TEMPLATE TEST:" . PHP_EOL;
try {
    $testOtp = '123456';
    $mailable = new EmailVerificationOtp($testUser, $testOtp);
    echo "✅ EmailVerificationOtp mailable created successfully" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Mailable creation failed: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// 6. Test actual email sending
echo "📨 EMAIL SENDING TEST:" . PHP_EOL;
try {
    // Enable mail logging
    Config::set('mail.log_channel', 'stack');
    
    Log::info('Starting email test...');
    
    Mail::to('7ddsm@punkproof.com')->send(new EmailVerificationOtp($testUser, '123456'));
    
    echo "✅ Email sent successfully (no exception thrown)" . PHP_EOL;
    Log::info('Email sent successfully');
    
} catch (Exception $e) {
    echo "❌ Email sending failed: " . $e->getMessage() . PHP_EOL;
    Log::error('Email sending failed: ' . $e->getMessage());
}
echo PHP_EOL;

// 7. Test dengan email alternatif
echo "📧 ALTERNATIVE EMAIL TEST:" . PHP_EOL;
try {
    Mail::to('dzikrirazzan09@gmail.com')->send(new EmailVerificationOtp($testUser, '654321'));
    echo "✅ Alternative email sent successfully" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Alternative email failed: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

echo "🎯 DEBUG COMPLETE!" . PHP_EOL;
echo "Check Laravel logs for more details: storage/logs/laravel.log" . PHP_EOL;
