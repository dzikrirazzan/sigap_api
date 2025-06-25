<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\EmailOtp;
use App\Mail\EmailVerificationOtp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DebugEmail extends Command
{
    protected $signature = 'debug:email {email=7ddsm@punkproof.com}';
    protected $description = 'Debug email configuration and sending';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('🔍 SIGAP EMAIL DEBUG - PRODUCTION');
        $this->info('=================================');
        $this->newLine();

        // 1. Cek konfigurasi email
        $this->info('📧 MAIL CONFIGURATION:');
        $this->line('MAIL_MAILER: ' . config('mail.default'));
        $this->line('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->line('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->line('MAIL_PASSWORD: ' . (config('mail.mailers.smtp.password') ? '***SET***' : 'NOT SET'));
        $this->line('MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption'));
        $this->line('MAIL_FROM_ADDRESS: ' . config('mail.from.address'));
        $this->line('MAIL_FROM_NAME: ' . config('mail.from.name'));
        $this->line('QUEUE_CONNECTION: ' . config('queue.default'));
        $this->newLine();

        // 2. Test database connection
        $this->info('💾 DATABASE CONNECTION:');
        try {
            $userCount = User::count();
            $this->line("✅ Database connected. Users: {$userCount}");
        } catch (\Exception $e) {
            $this->error("❌ Database error: " . $e->getMessage());
        }

        try {
            $otpCount = EmailOtp::count();
            $this->line("✅ EmailOTP table accessible. Records: {$otpCount}");
        } catch (\Exception $e) {
            $this->error("❌ EmailOTP table error: " . $e->getMessage());
        }
        $this->newLine();

        // 3. Test OTP generation
        $this->info('🔢 OTP GENERATION TEST:');
        try {
            $otp = EmailOtp::generateEmailVerificationOtp($email);
            $this->line('✅ OTP generated successfully');
            $this->line("   Email: {$otp->email}");
            $this->line("   OTP: {$otp->otp}");
            $this->line("   Expires: {$otp->expires_at}");
        } catch (\Exception $e) {
            $this->error("❌ OTP generation failed: " . $e->getMessage());
        }
        $this->newLine();

        // 4. Test user creation
        $this->info('👤 USER TEST:');
        try {
            $testUser = new User();
            $testUser->name = 'Debug Test User';
            $testUser->email = $email;
            $this->line('✅ User object created successfully');
        } catch (\Exception $e) {
            $this->error("❌ User creation failed: " . $e->getMessage());
        }
        $this->newLine();

        // 5. Test email template
        $this->info('📄 EMAIL TEMPLATE TEST:');
        try {
            $testOtp = '123456';
            $mailable = new EmailVerificationOtp($testUser, $testOtp);
            $this->line('✅ EmailVerificationOtp mailable created successfully');
        } catch (\Exception $e) {
            $this->error("❌ Mailable creation failed: " . $e->getMessage());
        }
        $this->newLine();

        // 6. Test actual email sending
        $this->info('📨 EMAIL SENDING TEST:');
        try {
            $this->line('Attempting to send email...');
            
            Mail::to($email)->send(new EmailVerificationOtp($testUser, '123456'));
            
            $this->line('✅ Email sent successfully (no exception thrown)');
            
        } catch (\Exception $e) {
            $this->error("❌ Email sending failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
        $this->newLine();

        // 7. Test SMTP connection
        $this->info('🔌 SMTP CONNECTION TEST:');
        try {
            $this->testSmtpConnection();
        } catch (\Exception $e) {
            $this->error("❌ SMTP test failed: " . $e->getMessage());
        }
        
        $this->newLine();
        $this->info('🎯 DEBUG COMPLETE!');
        $this->line('Check Laravel logs for more details: storage/logs/laravel.log');
    }
    
    private function testSmtpConnection()
    {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        
        $this->line("Testing connection to {$host}:{$port}...");
        
        $connection = @fsockopen($host, $port, $errno, $errstr, 30);
        
        if ($connection) {
            $this->line('✅ SMTP connection successful');
            fclose($connection);
        } else {
            $this->error("❌ SMTP connection failed: {$errstr} ({$errno})");
        }
    }
}
