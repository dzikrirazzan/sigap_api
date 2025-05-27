<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RelawanShift;
use Carbon\Carbon;

class AssignDailyRelawanShift extends Command
{
    protected $signature = 'relawan:assign-daily-shift {date?}';
    protected $description = 'Assign 4 relawan for daily emergency shift';

    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::today();
        
        // Cek apakah sudah ada shift untuk tanggal ini
        $existingShifts = RelawanShift::where('shift_date', $date->toDateString())->count();
        
        if ($existingShifts > 0) {
            $this->info("Shift for {$date->toDateString()} already exists ({$existingShifts} relawan assigned).");
            return;
        }
        
        // Ambil semua relawan
        $relawans = User::where('role', User::ROLE_RELAWAN)->get();
        
        if ($relawans->count() < 4) {
            $this->error('Not enough relawan available. Need at least 4 relawan.');
            return;
        }
        
        // Ambil shift terakhir untuk rotasi yang adil
        $lastShift = RelawanShift::orderBy('shift_date', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
        
        $selectedRelawans = collect();
        
        if ($lastShift) {
            // Dapatkan relawan yang bertugas terakhir
            $lastRelawanIds = RelawanShift::where('shift_date', $lastShift->shift_date)
                                         ->pluck('relawan_id')
                                         ->toArray();
            
            // Prioritaskan relawan yang belum bertugas baru-baru ini
            $availableRelawans = $relawans->filter(function($relawan) use ($lastRelawanIds) {
                return !in_array($relawan->id, $lastRelawanIds);
            });
            
            // Jika relawan yang tersedia kurang dari 4, ambil dari semua relawan
            if ($availableRelawans->count() < 4) {
                $availableRelawans = $relawans;
            }
            
            $selectedRelawans = $availableRelawans->random(min(4, $availableRelawans->count()));
        } else {
            // Jika ini shift pertama, pilih 4 relawan secara acak
            $selectedRelawans = $relawans->random(min(4, $relawans->count()));
        }
        
        // Buat shift untuk relawan yang dipilih
        foreach ($selectedRelawans as $relawan) {
            RelawanShift::create([
                'relawan_id' => $relawan->id,
                'shift_date' => $date->toDateString(),
            ]);
            
            $this->info("Assigned {$relawan->name} to shift on {$date->toDateString()}");
        }
        
        $this->info("Successfully assigned {$selectedRelawans->count()} relawan for {$date->toDateString()}");
    }
}