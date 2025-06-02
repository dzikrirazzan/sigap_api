<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RelawanShiftPattern;

class RelawanShiftPatternSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua relawan yang ada
        $relawans = User::where('role', User::ROLE_RELAWAN)->get();

        if ($relawans->isEmpty()) {
            $this->command->info('No relawan found. Please create relawan users first.');
            return;
        }

        // Hapus patterns yang sudah ada
        RelawanShiftPattern::truncate();

        // Buat pattern contoh untuk setiap hari
        $patterns = [
            'monday' => $relawans->take(3)->pluck('id')->toArray(), // 3 relawan untuk Senin
            'tuesday' => $relawans->skip(1)->take(3)->pluck('id')->toArray(), // 3 relawan untuk Selasa
            'wednesday' => $relawans->take(4)->pluck('id')->toArray(), // 4 relawan untuk Rabu
            'thursday' => $relawans->skip(2)->take(2)->pluck('id')->toArray(), // 2 relawan untuk Kamis
            'friday' => $relawans->take(4)->pluck('id')->toArray(), // 4 relawan untuk Jumat
            'saturday' => $relawans->skip(1)->take(2)->pluck('id')->toArray(), // 2 relawan untuk Sabtu
            'sunday' => $relawans->take(3)->pluck('id')->toArray(), // 3 relawan untuk Minggu
        ];

        $totalCreated = 0;

        foreach ($patterns as $dayOfWeek => $relawanIds) {
            foreach ($relawanIds as $relawanId) {
                RelawanShiftPattern::create([
                    'day_of_week' => $dayOfWeek,
                    'relawan_id' => $relawanId,
                    'is_active' => true
                ]);
                $totalCreated++;
            }

            $dayName = RelawanShiftPattern::DAYS[$dayOfWeek];
            $this->command->info("Created patterns for {$dayName}: " . count($relawanIds) . " relawan");
        }

        $this->command->info("Total shift patterns created: {$totalCreated}");

        // Tampilkan summary
        $this->command->info("\n=== SHIFT PATTERN SUMMARY ===");
        foreach (RelawanShiftPattern::DAYS as $dayKey => $dayName) {
            $count = RelawanShiftPattern::where('day_of_week', $dayKey)->count();
            $relawanNames = RelawanShiftPattern::where('day_of_week', $dayKey)
                ->with('relawan')
                ->get()
                ->pluck('relawan.name')
                ->join(', ');

            $this->command->info("{$dayName}: {$count} relawan ({$relawanNames})");
        }
    }
}
