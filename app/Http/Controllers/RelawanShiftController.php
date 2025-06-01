<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelawanShift;
use App\Models\User;
use App\Models\RelawanShiftPattern;
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

    // Auto assign shift untuk beberapa hari ke depan berdasarkan pattern
    public function autoAssign(Request $request)
    {
        $request->validate([
            'days' => 'integer|min:1|max:30',
            'use_patterns' => 'boolean'
        ]);

        $days = $request->get('days', 7);
        $usePatterns = $request->get('use_patterns', true); // Default menggunakan pattern system
        $results = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->addDays($i);
            $dateString = $date->toDateString();

            // Skip jika sudah ada shift
            $existingCount = RelawanShift::where('shift_date', $dateString)->count();
            if ($existingCount > 0) {
                $results[] = [
                    'date' => $dateString,
                    'day_name' => $date->locale('id')->isoFormat('dddd'),
                    'status' => 'skipped',
                    'message' => 'Shift already exists'
                ];
                continue;
            }

            if ($usePatterns) {
                // Gunakan pattern system
                $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
                
                // Get patterns for this day of week
                $patterns = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->with('relawan')
                    ->get();

                if ($patterns->isEmpty()) {
                    $results[] = [
                        'date' => $dateString,
                        'day_name' => $date->locale('id')->isoFormat('dddd'),
                        'status' => 'skipped',
                        'message' => 'No pattern defined for this day'
                    ];
                    continue;
                }

                // Create shifts from patterns
                $assignedRelawan = [];
                foreach ($patterns as $pattern) {
                    RelawanShift::create([
                        'relawan_id' => $pattern->relawan_id,
                        'shift_date' => $dateString
                    ]);
                    $assignedRelawan[] = $pattern->relawan->name;
                }

                $results[] = [
                    'date' => $dateString,
                    'day_name' => $date->locale('id')->isoFormat('dddd'),
                    'status' => 'success',
                    'assigned_count' => count($assignedRelawan),
                    'relawan' => $assignedRelawan,
                    'method' => 'pattern-based'
                ];
            } else {
                // Gunakan system lama (artisan command)
                \Artisan::call('relawan:assign-daily-shift', [
                    'date' => $dateString
                ]);

                $assignedCount = RelawanShift::where('shift_date', $dateString)->count();

                $results[] = [
                    'date' => $dateString,
                    'day_name' => $date->locale('id')->isoFormat('dddd'),
                    'status' => 'success',
                    'assigned_count' => $assignedCount,
                    'method' => 'legacy-command'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Auto-assigned shifts for {$days} days using " . ($usePatterns ? 'pattern system' : 'legacy system'),
            'results' => $results,
            'summary' => [
                'total_days' => $days,
                'successful' => collect($results)->where('status', 'success')->count(),
                'skipped' => collect($results)->where('status', 'skipped')->count(),
                'method' => $usePatterns ? 'pattern-based' : 'legacy-command'
            ]
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
            'shifts' => $shifts->map(function ($shift) use ($today) {
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
