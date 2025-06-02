<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force timezone ke Jakarta untuk semua PHP operations
        date_default_timezone_set('Asia/Jakarta');

        // Set Carbon timezone globally
        \Carbon\Carbon::setLocale('id');

        // Set default timezone untuk semua Carbon instances menggunakan method yang benar
        \Illuminate\Support\Carbon::macro('getDefaultTimezone', function () {
            return 'Asia/Jakarta';
        });

        // Force database timezone jika diperlukan
        if (config('database.default') === 'mysql') {
            try {
                DB::statement("SET time_zone = '+07:00'");
            } catch (\Exception $e) {
                // Skip jika database belum tersedia
            }
        }
    }
}
