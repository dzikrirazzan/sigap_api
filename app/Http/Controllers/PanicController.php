<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanicReport;
use App\Models\User;
use App\Models\RelawanShift;
use App\Models\RelawanShiftPattern;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PanicController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    // User tekan panic button - langsung ke relawan yang sedang jaga
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'nullable|string|max:500',
        ]);

        $userId = auth()->id();

        // Cek apakah user sudah punya panic report aktif hari ini
        $existingPanic = PanicReport::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->where('status', '!=', 'resolved')
            ->first();

        if ($existingPanic) {
            return response()->json([
                'message' => 'You already have an active panic report today. Please wait for resolution.',
                'existing_panic' => $existingPanic
            ], 409);
        }

        // Dapatkan relawan yang sedang jaga hari ini
        $onDutyRelawans = $this->getTodayOnDutyRelawans();

        if ($onDutyRelawans->isEmpty()) {
            return response()->json([
                'message' => 'No emergency responders are currently on duty. Please contact emergency services directly.',
                'emergency_numbers' => [
                    'police' => '110',
                    'fire' => '113', 
                    'ambulance' => '118'
                ]
            ], 503);
        }

        // Buat panic report
        $panic = PanicReport::create([
            'user_id' => $userId,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'description' => $request->description,
            'status' => PanicReport::STATUS_PENDING,
        ]);

        // Load relasi user
        $panic->load('user');

        // Send emergency WhatsApp notifications to on-duty relawan
        try {
            $result = $this->whatsAppService->sendEmergencyAlert($panic, $onDutyRelawans);
            
            if (!$result['success']) {
                Log::warning('WhatsApp notification failed but panic report created', [
                    'panic_id' => $panic->id,
                    'error' => $result['message']
                ]);
            } else {
                Log::info('Emergency WhatsApp alert sent successfully', [
                    'panic_id' => $panic->id,
                    'volunteers_count' => $onDutyRelawans->count()
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the panic report creation
            Log::error('Failed to send emergency WhatsApp notifications', [
                'panic_id' => $panic->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'panic' => $panic,
            'message' => 'Emergency alert sent via WhatsApp! Responders on duty have been notified.',
            'assigned_relawan' => $onDutyRelawans->map(function ($relawan) {
                return [
                    'id' => $relawan->id,
                    'name' => $relawan->name,
                    'phone' => $relawan->no_telp
                ];
            }),
            'total_responders' => $onDutyRelawans->count(),
            'notification_method' => 'WhatsApp via Fonnte'
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
        $oldStatus = $panic->status; // Save old status for email notification

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

        // Status update completed - no email notification needed
        
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
    // Improved version: Shows weekly schedule with better UX
    public function getMyShifts(Request $request)
    {
        $relawanId = auth()->id();
        $user = auth()->user();

        // Pastikan yang akses adalah relawan
        if ($user->role !== User::ROLE_RELAWAN) {
            return response()->json(['message' => 'Access denied. Only relawan can access this endpoint.'], 403);
        }

        // Get week parameter (default: current week)
        // User can specify week offset: 0 = current week, -1 = last week, 1 = next week
        $weekOffset = (int) $request->get('week', 0);
        
        // Calculate start and end of the target week (Monday to Sunday)
        $targetWeek = Carbon::now()->addWeeks($weekOffset);
        $startOfWeek = $targetWeek->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $targetWeek->copy()->endOfWeek(Carbon::SUNDAY);
        
        $startDate = $startOfWeek->toDateString();
        $endDate = $endOfWeek->toDateString();

        // Get actual shifts for the week
        $actualShifts = RelawanShift::where('relawan_id', $relawanId)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->get()
            ->keyBy('shift_date');

        // Get active patterns untuk relawan ini
        $patterns = RelawanShiftPattern::where('relawan_id', $relawanId)
            ->where('is_active', true)
            ->get()
            ->keyBy('day_of_week');

        // Build weekly schedule (Monday to Sunday)
        $weeklySchedule = [];
        $today = Carbon::now()->toDateString();
        $todayDayOfWeek = strtolower(Carbon::now()->format('l'));
        
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $dateString = $currentDay->toDateString();
            $dayOfWeek = strtolower($currentDay->format('l'));
            
            // Check if relawan has shift on this day
            $hasActualShift = isset($actualShifts[$dateString]);
            $hasPattern = isset($patterns[$dayOfWeek]);
            $isScheduled = $hasActualShift || $hasPattern;
            
            // Determine shift source
            $shiftSource = null;
            $shiftId = null;
            if ($hasActualShift) {
                $shiftSource = 'actual_shift';
                $shiftId = $actualShifts[$dateString]->id;
            } elseif ($hasPattern) {
                $shiftSource = 'weekly_pattern';
                $shiftId = $patterns[$dayOfWeek]->id;
            }
            
            $weeklySchedule[] = [
                'date' => $dateString,
                'day_of_week' => $dayOfWeek,
                'day_name' => $currentDay->locale('id')->isoFormat('dddd'),
                'date_formatted' => $currentDay->locale('id')->isoFormat('D MMM YYYY'),
                'is_today' => $dateString === $today,
                'is_past' => $dateString < $today,
                'is_future' => $dateString > $today,
                'is_scheduled' => $isScheduled,
                'shift_source' => $shiftSource,
                'shift_id' => $shiftId,
                'has_actual_shift' => $hasActualShift,
                'has_pattern' => $hasPattern
            ];
        }

        // Calculate summary
        $scheduledDays = collect($weeklySchedule)->where('is_scheduled', true);
        $todaySchedule = collect($weeklySchedule)->firstWhere('is_today', true);
        $isOnDutyToday = $todaySchedule ? $todaySchedule['is_scheduled'] : false;
        
        // Get upcoming shifts (next 7 days from today)
        $upcomingShifts = collect($weeklySchedule)
            ->where('is_future', true)
            ->where('is_scheduled', true)
            ->take(3) // Show next 3 upcoming shifts
            ->values();

        return response()->json([
            'relawan' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'week_info' => [
                'week_offset' => $weekOffset,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'week_label' => $this->getWeekLabel($weekOffset),
                'period_formatted' => $startOfWeek->locale('id')->isoFormat('D MMM') . ' - ' . $endOfWeek->locale('id')->isoFormat('D MMM YYYY')
            ],
            'today_status' => [
                'date' => $today,
                'is_on_duty' => $isOnDutyToday,
                'day_name' => Carbon::now()->locale('id')->isoFormat('dddd'),
                'shift_source' => $todaySchedule ? $todaySchedule['shift_source'] : null
            ],
            'weekly_schedule' => $weeklySchedule,
            'upcoming_shifts' => $upcomingShifts,
            'summary' => [
                'total_scheduled_days' => $scheduledDays->count(),
                'days_with_actual_shifts' => $scheduledDays->where('has_actual_shift', true)->count(),
                'days_with_patterns_only' => $scheduledDays->where('has_actual_shift', false)->where('has_pattern', true)->count(),
                'work_days_this_week' => $scheduledDays->count() . '/7 hari'
            ],
            'navigation' => [
                'previous_week' => $weekOffset - 1,
                'current_week' => 0,
                'next_week' => $weekOffset + 1
            ]
        ]);
    }

    /**
     * Helper method to get week label
     */
    private function getWeekLabel($weekOffset)
    {
        switch ($weekOffset) {
            case -1:
                return 'Minggu Lalu';
            case 0:
                return 'Minggu Ini';
            case 1:
                return 'Minggu Depan';
            default:
                return $weekOffset > 0 
                    ? $weekOffset . ' minggu ke depan'
                    : abs($weekOffset) . ' minggu yang lalu';
        }
    }

    /**
     * Helper method untuk mendapatkan relawan yang sedang jaga hari ini
     * Priority: Actual Shift > Weekly Pattern
     */
    private function getTodayOnDutyRelawans()
    {
        $today = Carbon::now()->toDateString();
        $dayOfWeek = strtolower(Carbon::now()->format('l')); // monday, tuesday, etc.

        // Priority 1: Cek actual shifts untuk hari ini
        $actualShifts = RelawanShift::where('shift_date', $today)
            ->with('relawan:id,name,email,no_telp')
            ->get()
            ->pluck('relawan')
            ->filter(); // Remove null values

        if ($actualShifts->isNotEmpty()) {
            return $actualShifts;
        }

        // Priority 2: Gunakan pattern untuk hari ini jika tidak ada actual shift
        $patterns = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->with('relawan:id,name,email,no_telp')
            ->get();

        return $patterns->pluck('relawan')->filter();
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

    /**
     * Test emergency email notification (Admin only)
     */
    public function testEmergencyEmail(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can test emergency notifications'
            ], 403);
        }

        $request->validate([
            'test_email' => 'nullable|email',
        ]);

        try {
            // Create a fake panic report for testing
            $testPanic = new PanicReport([
                'id' => 999999,
                'user_id' => $user->id,
                'latitude' => -7.2575,
                'longitude' => 110.8378,
                'description' => 'Test emergency alert - ini adalah simulasi untuk menguji sistem notifikasi email.',
                'status' => PanicReport::STATUS_PENDING,
                'created_at' => now(),
            ]);

            // Set fake user relation
            $testPanic->setRelation('user', $user);

            if ($request->test_email) {
                // Send to specific email
                $testRelawan = (object) [
                    'id' => 999999,
                    'name' => 'Test Relawan',
                    'email' => $request->test_email
                ];

                Mail::to($testRelawan->email)
                    ->send(new EmergencyAlert($testPanic, $testRelawan));

                return response()->json([
                    'success' => true,
                    'message' => "Test emergency email sent to {$request->test_email}",
                    'test_data' => [
                        'panic_id' => $testPanic->id,
                        'recipient' => $testRelawan->email,
                        'sent_at' => now()->toISOString()
                    ]
                ]);
            } else {
                // Send to all on-duty relawan
                $result = $this->emergencyEmailService->sendEmergencyAlert($testPanic);

                return response()->json([
                    'success' => $result,
                    'message' => $result ? 'Test emergency emails sent to on-duty relawan' : 'Failed to send test emails',
                    'test_data' => [
                        'panic_id' => $testPanic->id,
                        'sent_at' => now()->toISOString()
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test emergency email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}