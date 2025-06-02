<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanicReport;
use App\Models\User;
use App\Models\RelawanShift;
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
            $onDuty = RelawanShift::where('relawan_id', $userId)
                ->where('shift_date', $today)
                ->exists();

            if (!$onDuty) {
                return response()->json(['message' => 'Not on duty today'], 403);
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

    // Relawan mengambil/handle panic report
    public function handle(Request $request, $panicId)
    {
        $relawanId = auth()->id();
        $today = Carbon::now()->toDateString();

        // Cek apakah relawan ini on duty hari ini
        $onDuty = RelawanShift::where('relawan_id', $relawanId)
            ->where('shift_date', $today)
            ->exists();

        if (!$onDuty) {
            return response()->json(['message' => 'Not on duty today'], 403);
        }

        $panic = PanicReport::findOrFail($panicId);

        if ($panic->status !== PanicReport::STATUS_PENDING) {
            return response()->json(['message' => 'Panic report already handled'], 400);
        }

        $panic->update([
            'status' => PanicReport::STATUS_HANDLING,
            'handled_by' => $relawanId,
            'handled_at' => now(),
        ]);

        $panic->load(['user:id,name,email,no_telp,nik', 'handler:id,name']);

        return response()->json([
            'success' => true,
            'panic' => $panic,
            'message' => 'Panic report is now being handled'
        ]);
    }

    // Relawan menyelesaikan panic report
    public function resolve(Request $request, $panicId)
    {
        $relawanId = auth()->id();

        $panic = PanicReport::where('id', $panicId)
            ->where('handled_by', $relawanId)
            ->firstOrFail();

        if ($panic->status !== PanicReport::STATUS_HANDLING) {
            return response()->json(['message' => 'Can only resolve reports that are being handled'], 400);
        }

        $panic->update([
            'status' => PanicReport::STATUS_RESOLVED,
        ]);

        $panic->load(['user:id,name,email,no_telp,nik', 'handler:id,name']);

        return response()->json([
            'success' => true,
            'panic' => $panic,
            'message' => 'Panic report resolved'
        ]);
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

    // Get relawan yang sedang bertugas hari ini
    public function getTodayRelawan()
    {
        $today = Carbon::now()->toDateString();

        $relawans = RelawanShift::where('shift_date', $today)
            ->with('relawan:id,name,email,no_telp')
            ->get()
            ->pluck('relawan');

        return response()->json([
            'date' => $today,
            'relawan_on_duty' => $relawans
        ]);
    }

    // Relawan cek shift mereka sendiri
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

        $shifts = RelawanShift::where('relawan_id', $relawanId)
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