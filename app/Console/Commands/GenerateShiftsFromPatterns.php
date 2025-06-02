<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RelawanShiftPattern;
use App\Models\RelawanShift;
use Carbon\Carbon;

class GenerateShiftsFromPatterns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relawan:generate-shifts 
                            {--start= : Start date (YYYY-MM-DD format)}
                            {--end= : End date (YYYY-MM-DD format)}
                            {--days=7 : Number of days from today (if start/end not provided)}
                            {--overwrite : Overwrite existing shifts}
                            {--dry-run : Show what would be generated without actually creating shifts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate shifts from weekly patterns for specified date range';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting shift generation from patterns...');

        // Parse date parameters
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate($startDate);
        $overwrite = $this->option('overwrite');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No actual shifts will be created');
        }

        $this->info("📅 Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        $this->info("📊 Total days: " . $startDate->diffInDays($endDate) + 1);

        // Show current patterns
        $this->showPatternSummary();

        if (!$this->confirm('Continue with shift generation?')) {
            $this->info('❌ Operation cancelled.');
            return 0;
        }

        // Generate shifts
        $results = $this->generateShifts($startDate, $endDate, $overwrite, $dryRun);

        // Show results
        $this->displayResults($results);

        return 0;
    }

    private function getStartDate()
    {
        $startOption = $this->option('start');

        if ($startOption) {
            try {
                return Carbon::parse($startOption);
            } catch (\Exception $e) {
                $this->error("❌ Invalid start date format: {$startOption}");
                exit(1);
            }
        }

        return Carbon::today();
    }

    private function getEndDate($startDate)
    {
        $endOption = $this->option('end');

        if ($endOption) {
            try {
                $endDate = Carbon::parse($endOption);
                if ($endDate < $startDate) {
                    $this->error('❌ End date cannot be before start date');
                    exit(1);
                }
                return $endDate;
            } catch (\Exception $e) {
                $this->error("❌ Invalid end date format: {$endOption}");
                exit(1);
            }
        }

        $days = (int) $this->option('days');
        return $startDate->copy()->addDays($days - 1);
    }

    private function showPatternSummary()
    {
        $this->info("\n📋 Current Weekly Patterns:");
        $patterns = RelawanShiftPattern::with('relawan')->get()->groupBy('day_of_week');

        $headers = ['Day', 'Relawan Count', 'Relawan Names'];
        $rows = [];

        foreach (RelawanShiftPattern::DAYS as $dayKey => $dayName) {
            $dayPatterns = $patterns->get($dayKey, collect());
            $relawanNames = $dayPatterns->pluck('relawan.name')->join(', ');

            $rows[] = [
                $dayName,
                $dayPatterns->count(),
                $relawanNames ?: '-'
            ];
        }

        $this->table($headers, $rows);
    }

    private function generateShifts($startDate, $endDate, $overwrite, $dryRun)
    {
        $results = [];
        $current = $startDate->copy();

        $progressBar = $this->output->createProgressBar($startDate->diffInDays($endDate) + 1);
        $progressBar->start();

        while ($current <= $endDate) {
            $dayOfWeek = strtolower($current->format('l'));
            $dateString = $current->toDateString();

            // Check existing shifts
            $existingShifts = RelawanShift::where('shift_date', $dateString)->count();

            if ($existingShifts > 0 && !$overwrite) {
                $results[] = [
                    'date' => $dateString,
                    'day' => RelawanShiftPattern::DAYS[$dayOfWeek],
                    'status' => 'skipped',
                    'message' => 'Shifts already exist',
                    'assigned_count' => 0
                ];
                $current->addDay();
                $progressBar->advance();
                continue;
            }

            // Get patterns for this day
            $patterns = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->with('relawan')
                ->get();

            if ($patterns->isEmpty()) {
                $results[] = [
                    'date' => $dateString,
                    'day' => RelawanShiftPattern::DAYS[$dayOfWeek],
                    'status' => 'skipped',
                    'message' => 'No pattern defined',
                    'assigned_count' => 0
                ];
                $current->addDay();
                $progressBar->advance();
                continue;
            }

            if (!$dryRun) {
                // Delete existing shifts if overwrite
                if ($overwrite && $existingShifts > 0) {
                    RelawanShift::where('shift_date', $dateString)->delete();
                }

                // Create new shifts
                foreach ($patterns as $pattern) {
                    RelawanShift::create([
                        'relawan_id' => $pattern->relawan_id,
                        'shift_date' => $dateString
                    ]);
                }
            }

            $results[] = [
                'date' => $dateString,
                'day' => RelawanShiftPattern::DAYS[$dayOfWeek],
                'status' => 'success',
                'message' => $dryRun ? 'Would create shifts' : 'Shifts created',
                'assigned_count' => $patterns->count(),
                'relawan' => $patterns->pluck('relawan.name')->toArray()
            ];

            $current->addDay();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');

        return $results;
    }

    private function displayResults($results)
    {
        $successful = collect($results)->where('status', 'success');
        $skipped = collect($results)->where('status', 'skipped');

        $this->info("\n✅ Generation completed!");
        $this->info("📊 Summary:");
        $this->info("   • Successful: {$successful->count()}");
        $this->info("   • Skipped: {$skipped->count()}");
        $this->info("   • Total Relawan Assigned: {$successful->sum('assigned_count')}");

        if ($successful->isNotEmpty()) {
            $this->info("\n📅 Successfully Generated Shifts:");
            $headers = ['Date', 'Day', 'Relawan Count', 'Relawan Names'];
            $rows = $successful->map(function ($result) {
                return [
                    $result['date'],
                    $result['day'],
                    $result['assigned_count'],
                    implode(', ', $result['relawan'] ?? [])
                ];
            })->toArray();

            $this->table($headers, $rows);
        }

        if ($skipped->isNotEmpty()) {
            $this->warn("\n⚠️  Skipped Days:");
            $headers = ['Date', 'Day', 'Reason'];
            $rows = $skipped->map(function ($result) {
                return [
                    $result['date'],
                    $result['day'],
                    $result['message']
                ];
            })->toArray();

            $this->table($headers, $rows);
        }
    }
}
