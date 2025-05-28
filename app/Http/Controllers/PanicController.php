<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanicReport;
use App\Models\User;
use App\Models\RelawanShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Broadcast;

class PanicController extends Controller
{
    // User tekan panic button
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_description' => 'nullable|string',
        ]);

        $panic = PanicReport::create([
            'user_id' => auth()->id(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_description' => $request->location_description,
            'status' => PanicReport::STATUS_PENDING,
        ]);

        // Load relasi user dengan data lengkap termasuk no_telp
        $panic->load('user');

        // Broadcast ke relawan yang sedang bertugas hari ini
        $this->notifyOnDutyRelawan($panic);

        return response()->json([
            'success' => true,
            'panic' => $panic,
            'message' => 'Panic report sent to on-duty relawan'
        ]);
    }

    // Relawan on duty hari ini melihat panic report hari ini
    public function today(Request $request)
    {
        $today = Carbon::now()->toDateString();
        $relawanId = auth()->id();

        // Cek apakah relawan ini on duty hari ini
        $onDuty = RelawanShift::where('relawan_id', $relawanId)
            ->where('shift_date', $today)
            ->exists();

        if (!$onDuty) {
            return response()->json(['message' => 'Not on duty today'], 403);
        }

        $panics = PanicReport::whereDate('created_at', $today)
            ->with(['user:id,name,email,no_telp,nik', 'handler:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($panics);
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

    private function notifyOnDutyRelawan(PanicReport $panic)
    {
        $today = Carbon::now()->toDateString();

        // Dapatkan relawan yang sedang bertugas hari ini
        $onDutyRelawans = RelawanShift::where('shift_date', $today)
            ->with('relawan')
            ->get()
            ->pluck('relawan');

        // Kirim notification ke setiap relawan yang bertugas
        foreach ($onDutyRelawans as $relawan) {
            // Broadcast real-time notification
            broadcast(new \App\Events\PanicAlert($panic, $relawan->id));
        }
    }
}
