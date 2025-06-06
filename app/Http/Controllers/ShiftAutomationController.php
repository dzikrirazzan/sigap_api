<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class ShiftAutomationController extends Controller
{
    const AUTOMATION_CONFIG_KEY = 'shift_automation_enabled';
    const LAST_GENERATION_KEY = 'last_shift_generation';

    /**
     * Get automation status and configuration
     */
    public function getStatus()
    {
        $isEnabled = Cache::get(self::AUTOMATION_CONFIG_KEY, true); // Default enabled
        $lastGeneration = Cache::get(self::LAST_GENERATION_KEY);
        
        return response()->json([
            'automation_enabled' => $isEnabled,
            'last_automatic_generation' => $lastGeneration,
            'last_generation_formatted' => $lastGeneration ? Carbon::parse($lastGeneration)->format('d M Y, H:i') : null,
            'schedule_info' => [
                'frequency' => 'Daily',
                'time' => '02:00 WIB',
                'advance_days' => 7,
                'description' => 'Automatically generates shifts 7 days in advance every day at 2 AM'
            ],
            'manual_generation' => [
                'available' => true,
                'endpoint' => '/admin/shifts/generate-from-patterns',
                'description' => 'Manual generation is always available regardless of automation setting'
            ]
        ]);
    }

    /**
     * Enable or disable automation
     */
    public function toggleAutomation(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $enabled = $request->enabled;
        
        Cache::put(self::AUTOMATION_CONFIG_KEY, $enabled, now()->addYears(1));
        
        return response()->json([
            'success' => true,
            'message' => $enabled ? 'Shift automation enabled' : 'Shift automation disabled',
            'automation_enabled' => $enabled,
            'note' => $enabled 
                ? 'Shifts will be automatically generated daily at 2 AM' 
                : 'Automatic generation disabled. You can still generate shifts manually.'
        ]);
    }

    /**
     * Force immediate generation (for testing or emergency)
     */
    public function forceGeneration(Request $request)
    {
        $request->validate([
            'days' => 'integer|min:1|max:30',
            'reason' => 'string|max:255'
        ]);

        $days = $request->get('days', 7);
        $reason = $request->get('reason', 'Manual force generation');

        // Log the force generation
        $logEntry = [
            'timestamp' => now()->toISOString(),
            'type' => 'force_generation',
            'days' => $days,
            'reason' => $reason,
            'user' => auth()->user()->name ?? 'System'
        ];

        // Run the generation
        Artisan::call('shifts:auto-generate', [
            '--days' => $days
        ]);

        $output = Artisan::output();

        // Update last generation timestamp
        Cache::put(self::LAST_GENERATION_KEY, now()->toISOString(), now()->addYears(1));

        return response()->json([
            'success' => true,
            'message' => "Force generation completed for {$days} days",
            'parameters' => [
                'days' => $days,
                'reason' => $reason,
                'generated_at' => now()->format('d M Y, H:i:s')
            ],
            'command_output' => $output,
            'log_entry' => $logEntry
        ]);
    }

    /**
     * Get automation logs
     */
    public function getLogs(Request $request)
    {
        $logFile = storage_path('logs/shift-generation.log');
        
        if (!file_exists($logFile)) {
            return response()->json([
                'logs' => [],
                'message' => 'No generation logs found'
            ]);
        }

        $lines = file($logFile);
        $recentLines = array_slice($lines, -50); // Last 50 lines
        
        return response()->json([
            'logs' => array_map('trim', $recentLines),
            'total_lines' => count($lines),
            'showing_recent' => count($recentLines),
            'log_file_size' => $this->formatBytes(filesize($logFile)),
            'last_modified' => Carbon::createFromTimestamp(filemtime($logFile))->format('d M Y, H:i:s')
        ]);
    }

    /**
     * Test automation (using simplified system)
     */
    public function testAutomation()
    {
        // Use the simplified auto-generate command for testing
        Artisan::call('shifts:auto-generate', [
            '--days' => 7
        ]);

        $output = Artisan::output();

        return response()->json([
            'success' => true,
            'message' => 'Automation test completed using simplified system',
            'test_results' => $output,
            'note' => 'System automatically skips existing shifts and generates only new ones'
        ]);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}
