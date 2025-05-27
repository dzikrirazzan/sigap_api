<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelawanShift;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminShiftController extends Controller
{
    // Assign shift relawan untuk tanggal tertentu
    public function assign(Request $request)
    {
        $fields = $request->validate([
            'date' => 'required|date',
            'relawan_ids' => 'required|array|min:1|max:4',
            'relawan_ids.*' => 'exists:users,id',
        ]);

        // Hapus shift lama di tanggal itu
        RelawanShift::where('shift_date', $fields['date'])->delete();

        // Insert shift baru
        foreach ($fields['relawan_ids'] as $relawan_id) {
            RelawanShift::create([
                'relawan_id' => $relawan_id,
                'shift_date' => $fields['date'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shift assigned successfully',
            'date' => $fields['date'],
            'relawan_ids' => $fields['relawan_ids'],
        ]);
    }

    // GET: Lihat shift relawan untuk 7 hari ke depan
    public function week(Request $request)
    {
        $start = $request->query('start', now()->toDateString());
        $end = $request->query('end', now()->addDays(6)->toDateString());

        $shifts = RelawanShift::with('relawan')
            ->whereBetween('shift_date', [$start, $end])
            ->orderBy('shift_date')
            ->get()
            ->groupBy('shift_date')
            ->map(function ($items) {
                return $items->map(function ($shift) {
                    return [
                        'relawan_id' => $shift->relawan_id,
                        'relawan_nama' => $shift->relawan->name,
                    ];
                });
            });

        return response()->json([
            'start' => $start,
            'end' => $end,
            'shifts' => $shifts,
        ]);
    }
}
