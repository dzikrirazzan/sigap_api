<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RelawanShift;
use App\Models\RelawanShiftPattern;
use Carbon\Carbon;

class AutoGenerateShifts extends Command
{
    protected $signature = 'shifts:auto-generate {--days=7}';
    protected $description = 'Automatically generate shifts from weekly patterns';

    public function handle()
    {
        $days = $this->option('days');
        $startDate = Carbon::tomorrow(); // Mulai dari besok

        $this->info("🤖 Auto-generating shifts for {$days} days starting from {$startDate->format('Y-m-d')}");

        $generated = 0;
        $skipped = 0;

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateString = $currentDate->toDateString();
            $dayOfWeek = strtolower($currentDate->format('l')); // monday, tuesday, etc.

            // Skip jika sudah ada shift untuk tanggal ini
            $existingShiftsCount = RelawanShift::where('shift_date', $dateString)->count();
            if ($existingShiftsCount > 0) {
                $this->line("   • {$dateString} ({$dayOfWeek}): Skipped - {$existingShiftsCount} shifts already exist");
                $skipped++;
                continue;
            }

            // Ambil pattern untuk hari ini
            $patterns = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->with('relawan:id,name')
                ->get();

            if ($patterns->isEmpty()) {
                $this->line("   • {$dateString} ({$dayOfWeek}): Skipped - No pattern defined");
                $skipped++;
                continue;
            }

            // Generate shifts berdasarkan pattern
            $shiftsCreated = 0;
            foreach ($patterns as $pattern) {
                RelawanShift::create([
                    'relawan_id' => $pattern->relawan_id,
                    'shift_date' => $dateString
                ]);
                $shiftsCreated++;
            }

            $relawanNames = $patterns->pluck('relawan.name')->implode(', ');
            $this->info("   ✅ {$dateString} ({$dayOfWeek}): Created {$shiftsCreated} shifts - {$relawanNames}");
            $generated++;
        }

        $this->newLine();
        $this->info("📊 Generation Summary:");
        $this->info("   • Generated: {$generated} days");
        $this->info("   • Skipped: {$skipped} days");
        $this->info("   • Total shifts created: " . ($generated * RelawanShiftPattern::where('is_active', true)->count()));

        return Command::SUCCESS;
    }
}
