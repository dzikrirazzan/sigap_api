<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimezoneTestController extends Controller
{
    public function testTimezone()
    {
        try {
            // Test Laravel config
            $appTimezone = config('app.timezone');
            
            // Test PHP timezone
            $phpTimezone = date_default_timezone_get();
            
            // Test Carbon timezone
            $carbonNow = Carbon::now();
            $carbonJakarta = Carbon::now('Asia/Jakarta');
            
            // Test database timezone
            $dbTimezone = DB::select('SELECT @@session.time_zone as session_tz, @@global.time_zone as global_tz, NOW() as db_time')[0];
            
            // Test dengan create model
            $testData = [
                'user_id' => 1,
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'location_description' => 'Test timezone',
                'status' => 'pending'
            ];
            
            // Create panic report untuk test
            $panicReport = \App\Models\PanicReport::create($testData);
            
            return response()->json([
                'app_timezone' => $appTimezone,
                'php_timezone' => $phpTimezone,
                'carbon_now' => $carbonNow->format('Y-m-d H:i:s T'),
                'carbon_jakarta' => $carbonJakarta->format('Y-m-d H:i:s T'),
                'database_info' => $dbTimezone,
                'created_panic' => [
                    'id' => $panicReport->id,
                    'created_at' => $panicReport->created_at,
                    'created_at_formatted' => $panicReport->created_at->format('Y-m-d H:i:s T'),
                ],
                'server_time' => now()->format('Y-m-d H:i:s T'),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'app_timezone' => config('app.timezone'),
                'php_timezone' => date_default_timezone_get(),
                'server_time' => date('Y-m-d H:i:s T'),
            ]);
        }
    }
}
