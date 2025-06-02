<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        
        // Set default timezone untuk semua Carbon instances
        \Carbon\Carbon::setTestNow(null);
        
        // Force database timezone jika diperlukan
        if (config('database.default') === 'mysql') {
            \DB::statement("SET time_zone = '+07:00'");
        }
    }
}
