<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelawanShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class RelawanShiftController extends Controller
{
    // Generate shifts dari patterns untuk beberapa hari ke depan
    public function generateFromPatterns(Request $request)
    {
        $request->validate([
            'days' => 'integer|min:1|max:30',
            'start_date' => 'date|after_or_equal:today',
            'overwrite' => 'boolean'
        ]);

        $days = $request->get('days', 7);
        $startDate = $request->get('start_date', Carbon::today()->toDateString());
        $overwrite = $request->get('overwrite', false);

        // Call the artisan command
        Artisan::call('relawan:generate-shifts', [
            '--start' => $startDate,
            '--days' => $days,
            '--overwrite' => $overwrite
        ]);

        $output = Artisan::output();

        return response()->json([
            'success' => true,
            'message' => "Generated shifts from patterns for {$days} days starting from {$startDate}",
            'parameters' => [
                'days' => $days,
                'start_date' => $startDate,
                'overwrite' => $overwrite
            ],
            'command_output' => $output
        ]);
    }

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

        return response()->json([
            'success' => true,
            'shifts' => $groupedShifts,
            'note' => 'Shifts are now managed through weekly patterns. Use Pattern Management endpoints to set recurring schedules.'
        ]);
    }

    // Admin update shift individual - ganti relawan di shift tertentu
    public function updateShift(Request $request, $shiftId)
    {
        $request->validate([
            'relawan_id' => 'required|exists:users,id'
        ]);

        // Cek apakah relawan_id yang baru adalah relawan
        $relawan = User::where('id', $request->relawan_id)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        if (!$relawan) {
            return response()->json(['message' => 'User is not a relawan'], 400);
        }

        $shift = RelawanShift::findOrFail($shiftId);
        $oldRelawan = $shift->relawan->name;

        $shift->update([
            'relawan_id' => $request->relawan_id
        ]);

        return response()->json([
            'success' => true,
            'message' => "Shift updated successfully. Changed from {$oldRelawan} to {$relawan->name}",
            'shift' => $shift->load('relawan:id,name,email,no_telp')
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

    // Admin hapus shift berdasarkan nama relawan dan tanggal
    public function destroyByRelawan(Request $request)
    {
        $request->validate([
            'relawan_name' => 'required|string',
            'shift_date' => 'required|date'
        ]);

        // Cari relawan berdasarkan nama
        $relawan = User::where('role', User::ROLE_RELAWAN)
            ->where('name', 'LIKE', '%' . $request->relawan_name . '%')
            ->first();

        if (!$relawan) {
            return response()->json(['message' => 'Relawan not found'], 404);
        }

        // Hapus shift berdasarkan relawan dan tanggal
        $deletedShift = RelawanShift::where('relawan_id', $relawan->id)
            ->where('shift_date', $request->shift_date)
            ->first();

        if (!$deletedShift) {
            return response()->json([
                'message' => "No shift found for {$relawan->name} on {$request->shift_date}"
            ], 404);
        }

        $deletedShift->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted shift for {$relawan->name} on {$request->shift_date}",
            'deleted_shift' => [
                'id' => $deletedShift->id,
                'relawan_name' => $relawan->name,
                'shift_date' => $deletedShift->shift_date
            ]
        ]);
    }

    // Admin hapus shift berdasarkan ID relawan dan tanggal (alternative)
    public function destroyByRelawanId(Request $request)
    {
        $request->validate([
            'relawan_id' => 'required|exists:users,id',
            'shift_date' => 'required|date'
        ]);

        // Pastikan user adalah relawan
        $relawan = User::where('id', $request->relawan_id)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        if (!$relawan) {
            return response()->json(['message' => 'User is not a relawan'], 400);
        }

        $deletedShift = RelawanShift::where('relawan_id', $request->relawan_id)
            ->where('shift_date', $request->shift_date)
            ->first();

        if (!$deletedShift) {
            return response()->json([
                'message' => "No shift found for {$relawan->name} on {$request->shift_date}"
            ], 404);
        }

        $deletedShift->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted shift for {$relawan->name} on {$request->shift_date}",
            'deleted_shift' => [
                'id' => $deletedShift->id,
                'relawan_name' => $relawan->name,
                'shift_date' => $deletedShift->shift_date
            ]
        ]);
    }

    // Get semua relawan untuk dropdown
    public function getRelawans()
    {
        $relawans = User::where('role', User::ROLE_RELAWAN)
            ->select('id', 'name', 'email', 'no_telp')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'relawans' => $relawans,
            'total' => $relawans->count()
        ]);
    }
}
