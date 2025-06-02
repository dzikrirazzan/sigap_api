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
        // Force timezone ke Jakarta
        date_default_timezone_set('Asia/Jakarta');
        
        // Set Carbon timezone
        \Carbon\Carbon::setLocale('id');
        \Carbon\Carbon::now()->setTimezone('Asia/Jakarta');
    }
}
