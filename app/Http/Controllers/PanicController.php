<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanicReport;
use App\Models\User;
use App\Models\RelawanShift;
use App\Models\RelawanShiftPattern;
use Carbon\Carbon;

class PanicController extends Controller
{
    // User tekan panic button - optimized for speed with minimal required data
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $panic = PanicReport::create([
            'user_id' => auth()->id(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => PanicReport::STATUS_PENDING,
        ]);

        // Load relasi user dengan data lengkap termasuk no_telp
        $panic->load('user');

        return response()->json([
            'success' => true,
            'panic' => $panic,
            'message' => 'Emergency alert sent successfully. Help is on the way!',
            'required_fields_only' => 'Only latitude and longitude are required for fastest response'
        ]);
    }

    // Relawan on duty hari ini melihat panic report hari ini
    // Admin juga bisa melihat semua panic report hari ini
    public function today(Request $request)
    {
        $today = Carbon::now()->toDateString();
        $user = auth()->user();
        $userId = auth()->id();

        // Jika user adalah admin, berikan akses penuh ke semua panic reports hari ini
        if ($user->role === 'admin') {
            $panics = PanicReport::whereDate('created_at', $today)
                ->with(['user:id,name,email,no_telp,nik', 'handler:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'user_type' => 'admin',
                'today' => $today,
                'total_reports' => $panics->count(),
                'data' => $panics
            ]);
        }

        // Jika user adalah relawan, cek apakah sedang bertugas hari ini
        if ($user->role === 'relawan') {
            $onDuty = $this->isRelawanOnDutyToday($userId);

            if (!$onDuty) {
                return response()->json([
                    'message' => 'Access denied. You are not assigned for duty today.',
                    'hint' => 'Only relawan assigned through weekly patterns can access panic reports.'
                ], 403);
            }

            $panics = PanicReport::whereDate('created_at', $today)
                ->with(['user:id,name,email,no_telp,nik', 'handler:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'user_type' => 'relawan',
                'today' => $today,
                'total_reports' => $panics->count(),
                'data' => $panics
            ]);
        }

        // Jika bukan admin atau relawan
        return response()->json(['message' => 'Unauthorized. Admin or Relawan access required.'], 403);
    }

    // Relawan & Admin update status panic report (handling/resolved/cancelled)
    public function updateStatus(Request $request, $panicId)
    {
        $userId = auth()->id();
        $user = auth()->user();
        $today = Carbon::now()->toDateString();

        // Admin bisa update ke semua status, relawan hanya handling/resolved
        $allowedStatuses = $user->role === 'admin'
            ? ['handling', 'resolved', 'cancelled']
            : ['handling', 'resolved'];

        // Validasi input
        $request->validate([
            'status' => 'required|in:' . implode(',', $allowedStatuses)
        ]);

        $newStatus = $request->status;

        // Untuk relawan: cek apakah on duty hari ini
        if ($user->role === 'relawan') {
            $onDuty = $this->isRelawanOnDutyToday($userId);

            if (!$onDuty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You are not assigned for duty today.',
                    'hint' => 'Only relawan assigned through weekly patterns can update panic status.'
                ], 403);
            }
        }

        $panic = PanicReport::findOrFail($panicId);

        // Logic berdasarkan status yang diminta
        if ($newStatus === 'handling') {
            // Handle panic (pending → handling)
            if ($panic->status !== PanicReport::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only handle pending panic reports'
                ], 400);
            }

            $panic->update([
                'status' => PanicReport::STATUS_HANDLING,
                'handled_by' => $userId,
                'handled_at' => now(),
            ]);

            $message = 'Panic report is now being handled';
        } elseif ($newStatus === 'resolved') {
            // Resolve panic (handling → resolved)
            // Cek apakah ini user yang handle atau masih pending
            if ($panic->status === PanicReport::STATUS_PENDING) {
                // Langsung dari pending ke resolved (skip handling)
                $panic->update([
                    'status' => PanicReport::STATUS_RESOLVED,
                    'handled_by' => $userId,
                    'handled_at' => now(),
                ]);
                $message = 'Panic report resolved directly';
            } elseif ($panic->status === PanicReport::STATUS_HANDLING) {
                // Untuk relawan: hanya yang handle yang bisa resolve
                // Untuk admin: bisa resolve siapa saja
                if ($user->role === 'relawan' && $panic->handled_by !== $userId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Can only resolve panic reports that you are handling'
                    ], 403);
                }

                $panic->update([
                    'status' => PanicReport::STATUS_RESOLVED,
                ]);
                $message = 'Panic report resolved';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only resolve pending or handling panic reports'
                ], 400);
            }
        } elseif ($newStatus === 'cancelled') {
            // Hanya admin yang bisa cancel
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admin can cancel panic reports'
                ], 403);
            }

            $panic->update([
                'status' => PanicReport::STATUS_CANCELLED,
            ]);
            $message = 'Panic report cancelled by admin';
        }

        $panic->load(['user:id,name,email,no_telp,nik', 'handler:id,name']);

        return response()->json([
            'success' => true,
            'panic' => $panic,
            'message' => $message,
            'action' => "Status updated to {$newStatus}",
            'updated_by' => $user->role
        ]);
    }

    // Admin menghapus panic report
    public function destroy($panicId)
    {
        $user = auth()->user();

        // Hanya admin yang bisa delete
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can delete panic reports'
            ], 403);
        }

        try {
            $panic = PanicReport::findOrFail($panicId);

            // Simpan data untuk response sebelum dihapus
            $panicData = [
                'id' => $panic->id,
                'user_name' => $panic->user->name ?? 'Unknown',
                'status' => $panic->status,
                'created_at' => $panic->created_at
            ];

            $panic->delete();

            return response()->json([
                'success' => true,
                'message' => 'Panic report deleted successfully',
                'deleted_panic' => $panicData,
                'deleted_by' => $user->name
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Panic report not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete panic report: ' . $e->getMessage()
            ], 500);
        }
    }

    // Admin melihat semua panic reports
    public function adminIndex(Request $request)
    {
        $query = PanicReport::with(['user:id,name,email,no_telp,nik', 'handler:id,name']);

        // Filter berdasarkan tanggal jika ada
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter berdasarkan status jika ada
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $panics = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($panics);
    }

    // Get relawan yang sedang bertugas hari ini (berdasarkan pattern + actual shifts)
    public function getTodayRelawan()
    {
        $today = Carbon::now()->toDateString();
        $dayOfWeek = strtolower(Carbon::now()->format('l')); // monday, tuesday, etc.

        // Get dari actual shifts
        $actualShifts = RelawanShift::where('shift_date', $today)
            ->with('relawan:id,name,email,no_telp')
            ->get()
            ->pluck('relawan');

        // Get dari patterns untuk hari ini
        $patternRelawans = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->with('relawan:id,name,email,no_telp')
            ->get()
            ->pluck('relawan');

        // Gabungkan dan remove duplicates
        $allRelawans = $actualShifts->merge($patternRelawans)->unique('id')->values();

        return response()->json([
            'date' => $today,
            'day_of_week' => $dayOfWeek,
            'relawan_on_duty' => $allRelawans,
            'total_on_duty' => $allRelawans->count(),
            'source' => [
                'from_actual_shifts' => $actualShifts->count(),
                'from_patterns' => $patternRelawans->count()
            ]
        ]);
    }

    // Relawan cek shift mereka sendiri - berdasarkan pattern dan actual shifts
    public function getMyShifts(Request $request)
    {
        $relawanId = auth()->id();
        $user = auth()->user();

        // Pastikan yang akses adalah relawan
        if ($user->role !== User::ROLE_RELAWAN) {
            return response()->json(['message' => 'Access denied. Only relawan can access this endpoint.'], 403);
        }

        // Default ambil shift 1 minggu ke depan dan ke belakang
        $startDate = $request->get('start_date', Carbon::now()->subDays(7)->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->addDays(7)->toDateString());

        // Get actual shifts
        $actualShifts = RelawanShift::where('relawan_id', $relawanId)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->orderBy('shift_date', 'desc')
            ->get();

        // Get patterns untuk relawan ini
        $patterns = RelawanShiftPattern::where('relawan_id', $relawanId)
            ->where('is_active', true)
            ->get();

        $today = Carbon::now()->toDateString();
        $isOnDutyToday = $this->isRelawanOnDutyToday($relawanId);

        return response()->json([
            'relawan' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'is_on_duty_today' => $isOnDutyToday,
            'actual_shifts' => $actualShifts->map(function ($shift) use ($today) {
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
            'weekly_patterns' => $patterns->map(function ($pattern) {
                return [
                    'id' => $pattern->id,
                    'day_of_week' => $pattern->day_of_week,
                    'day_name' => RelawanShiftPattern::DAYS[$pattern->day_of_week] ?? ucfirst($pattern->day_of_week),
                    'is_active' => $pattern->is_active,
                    'created_at' => $pattern->created_at
                ];
            }),
            'summary' => [
                'total_actual_shifts' => $actualShifts->count(),
                'total_weekly_patterns' => $patterns->count(),
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]
        ]);
    }

    /**
     * Helper method untuk check apakah relawan on duty hari ini
     * Kombinasi dari pattern dan actual shift assignments
     */
    private function isRelawanOnDutyToday($relawanId)
    {
        $today = Carbon::now()->toDateString();
        $dayOfWeek = strtolower(Carbon::now()->format('l')); // monday, tuesday, etc.

        // Check 1: Ada shift aktual untuk hari ini
        $hasActualShift = RelawanShift::where('relawan_id', $relawanId)
            ->where('shift_date', $today)
            ->exists();

        if ($hasActualShift) {
            return true;
        }

        // Check 2: Pattern untuk hari ini (jika tidak ada shift aktual)
        $hasPatternForToday = RelawanShiftPattern::where('relawan_id', $relawanId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->exists();

        return $hasPatternForToday;
    }
}
