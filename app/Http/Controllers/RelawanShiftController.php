<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelawanShift;
use App\Models\User;
use Carbon\Carbon;

class RelawanShiftController extends Controller
{
    // Admin melihat jadwal shift relawan
    public function index(Request $request)
    {
        $query = RelawanShift::with('relawan:id,name,email,no_telp');

        // Filter berdasarkan tanggal jika ada
        if ($request->has('date')) {
            $query->where('shift_date', $request->date);
        } else {
            // Default tampilkan shift 7 hari ke depan
            $query->whereBetween('shift_date', [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays(7)->toDateString()
            ]);
        }

        $shifts = $query->orderBy('shift_date', 'desc')->get();

        // Group by date
        $groupedShifts = $shifts->groupBy('shift_date');

        return response()->json($groupedShifts);
    }

    // Admin membuat shift manual untuk tanggal tertentu
    public function store(Request $request)
    {
        $request->validate([
            'shift_date' => 'required|date|after_or_equal:today',
            'relawan_ids' => 'required|array|min:1|max:4',
            'relawan_ids.*' => 'exists:users,id'
        ]);

        // Cek apakah semua ID adalah relawan
        $relawans = User::whereIn('id', $request->relawan_ids)
            ->where('role', User::ROLE_RELAWAN)
            ->get();

        if ($relawans->count() !== count($request->relawan_ids)) {
            return response()->json(['message' => 'Some users are not relawan'], 400);
        }

        // Hapus shift yang sudah ada untuk tanggal ini
        RelawanShift::where('shift_date', $request->shift_date)->delete();

        // Buat shift baru
        $shifts = [];
        foreach ($request->relawan_ids as $relawanId) {
            $shifts[] = RelawanShift::create([
                'relawan_id' => $relawanId,
                'shift_date' => $request->shift_date,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shift created successfully',
            'shifts' => RelawanShift::where('shift_date', $request->shift_date)
                ->with('relawan:id,name,email,no_telp')
                ->get()
        ]);
    }

    // Admin hapus shift untuk tanggal tertentu
    public function destroy($date)
    {
        $deletedCount = RelawanShift::where('shift_date', $date)->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} shifts for {$date}"
        ]);
    }

    // Get semua relawan untuk dropdown
    public function getRelawans()
    {
        $relawans = User::where('role', User::ROLE_RELAWAN)
            ->select('id', 'name', 'email', 'no_telp')
            ->orderBy('name')
            ->get();

        return response()->json($relawans);
    }

    // Auto assign shift untuk beberapa hari ke depan
    public function autoAssign(Request $request)
    {
        $request->validate([
            'days' => 'integer|min:1|max:30'
        ]);

        $days = $request->get('days', 7);
        $results = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->addDays($i);

            // Skip jika sudah ada shift
            $existingCount = RelawanShift::where('shift_date', $date->toDateString())->count();
            if ($existingCount > 0) {
                $results[] = [
                    'date' => $date->toDateString(),
                    'status' => 'skipped',
                    'message' => 'Shift already exists'
                ];
                continue;
            }

            // Panggil artisan command untuk assign shift
            \Artisan::call('relawan:assign-daily-shift', [
                'date' => $date->toDateString()
            ]);

            $assignedCount = RelawanShift::where('shift_date', $date->toDateString())->count();

            $results[] = [
                'date' => $date->toDateString(),
                'status' => 'success',
                'assigned_count' => $assignedCount
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Auto-assigned shifts for {$days} days",
            'results' => $results
        ]);
    }

    // GET: Relawan cek shift sendiri 
    public function myShifts(Request $request)
    {
        $user = $request->user();
        
        // Pastikan yang akses adalah relawan
        if ($user->role !== User::ROLE_RELAWAN) {
            return response()->json(['message' => 'Access denied. Only relawan can access this endpoint.'], 403);
        }

        // Default ambil shift 2 minggu (1 minggu ke belakang dan 1 minggu ke depan)
        $startDate = $request->query('start_date', Carbon::now()->subDays(7)->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->addDays(7)->toDateString());

        $shifts = $user->relawanShifts()
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->orderBy('shift_date', 'desc')
            ->get();

        $today = Carbon::now()->toDateString();
        $isOnDutyToday = $shifts->where('shift_date', $today)->isNotEmpty();

        return response()->json([
            'relawan' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'is_on_duty_today' => $isOnDutyToday,
            'shifts' => $shifts->map(function($shift) use ($today) {
                return [
                    'id' => $shift->id,
                    'shift_date' => $shift->shift_date,
                    'is_today' => $shift->shift_date === $today,
                    'is_past' => $shift->shift_date < $today,
                    'day_name' => Carbon::parse($shift->shift_date)->locale('id')->isoFormat('dddd'),
                    'date_formatted' => Carbon::parse($shift->shift_date)->locale('id')->isoFormat('D MMMM YYYY'),
                    'created_at' => $shift->created_at
                ];
            }),
            'total_shifts' => $shifts->count(),
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }
}