<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\PanicReport;
use Faker\Factory as Faker;

class GenerateTestPanicReports extends Command
{
    protected $signature = 'test:panic-reports {count=10} {--user-id=}';
    protected $description = 'Generate test panic reports for testing';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $userId = $this->option('user-id');

        if (!$userId) {
            // Cari user dengan role 'user'
            $user = User::where('role', User::ROLE_USER)->first();
            if (!$user) {
                $this->error('No user found. Please create a user first or specify --user-id');
                return;
            }
            $userId = $user->id;
            $this->info("Using user: {$user->name} (ID: {$userId})");
        } else {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return;
            }
            $this->info("Using user: {$user->name} (ID: {$userId})");
        }

        $faker = Faker::create('id_ID');

        $this->info("Generating {$count} panic reports...");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            // Jakarta area coordinates
            $latitude = $faker->latitude(-6.4, -6.0);
            $longitude = $faker->longitude(106.5, 107.0);

            $locations = [
                'Jl. Sudirman',
                'Jl. Thamrin',
                'Jl. Gatot Subroto',
                'Jl. Kuningan',
                'Jl. HR Rasuna Said',
                'Jl. Casablanca',
                'Jl. TB Simatupang',
                'Jl. Kemang',
                'Jl. Fatmawati',
                'Jl. Warung Buncit'
            ];

            $emergencyTypes = [
                'Kecelakaan lalu lintas',
                'Kebakaran',
                'Pencurian',
                'Kekerasan',
                'Darurat medis',
                'Bencana alam',
                'Gangguan keamanan',
                'Situasi mencurigakan'
            ];

            PanicReport::create([
                'user_id' => $userId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_description' => $faker->randomElement($locations) . ' - ' . $faker->randomElement($emergencyTypes),
                'status' => $faker->randomElement([
                    PanicReport::STATUS_PENDING,
                    PanicReport::STATUS_HANDLING,
                    PanicReport::STATUS_RESOLVED
                ]),
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully generated {$count} panic reports!");

        // Show some stats
        $totalPanics = PanicReport::count();
        $todayPanics = PanicReport::whereDate('created_at', today())->count();
        $pendingPanics = PanicReport::where('status', PanicReport::STATUS_PENDING)->count();

        $this->newLine();
        $this->info("Stats:");
        $this->line("Total panic reports: {$totalPanics}");
        $this->line("Today's panic reports: {$todayPanics}");
        $this->line("Pending panic reports: {$pendingPanics}");
    }
}