<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelawanShiftPattern;
use App\Models\User;
use Carbon\Carbon;

class RelawanShiftPatternController extends Controller
{
    // Admin melihat semua pattern yang ada
    public function index()
    {
        $patterns = RelawanShiftPattern::with('relawan:id,name,email')
            ->orderBy('day_of_week')
            ->orderBy('relawan_id')
            ->get()
            ->groupBy('day_of_week');

        $groupedPatterns = [];
        foreach (RelawanShiftPattern::DAYS as $dayKey => $dayName) {
            $dayPatterns = $patterns->get($dayKey, collect());
            $groupedPatterns[$dayKey] = [
                'day_name' => $dayName,
                'day_key' => $dayKey,
                'relawan_count' => $dayPatterns->count(),
                'relawan' => $dayPatterns->map(function ($pattern) {
                    return [
                        'id' => $pattern->id,
                        'relawan' => $pattern->relawan,
                        'is_active' => $pattern->is_active,
                        'created_at' => $pattern->created_at
                    ];
                })
            ];
        }

        return response()->json([
            'patterns' => $groupedPatterns,
            'summary' => [
                'total_patterns' => RelawanShiftPattern::count(),
                'active_patterns' => RelawanShiftPattern::where('is_active', true)->count(),
                'inactive_patterns' => RelawanShiftPattern::where('is_active', false)->count()
            ]
        ]);
    }

    // Admin set pattern untuk hari tertentu (ganti semua relawan untuk hari itu)
    public function setDayPattern(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:' . implode(',', array_keys(RelawanShiftPattern::DAYS)),
            'relawan_ids' => 'required|array|min:1|max:6',
            'relawan_ids.*' => 'exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $relawanIds = $request->relawan_ids;

        // Cek apakah semua ID adalah relawan
        $relawans = User::whereIn('id', $relawanIds)
            ->where('role', User::ROLE_RELAWAN)
            ->get();

        if ($relawans->count() !== count($relawanIds)) {
            return response()->json(['message' => 'Some users are not relawan'], 400);
        }

        // Hapus pattern yang sudah ada untuk hari ini
        RelawanShiftPattern::where('day_of_week', $dayOfWeek)->delete();

        // Buat pattern baru
        $patterns = [];
        foreach ($relawanIds as $relawanId) {
            $patterns[] = RelawanShiftPattern::create([
                'day_of_week' => $dayOfWeek,
                'relawan_id' => $relawanId,
                'is_active' => true
            ]);
        }

        $dayName = RelawanShiftPattern::DAYS[$dayOfWeek];

        return response()->json([
            'success' => true,
            'message' => "Pattern for {$dayName} updated successfully",
            'day' => $dayName,
            'day_key' => $dayOfWeek,
            'patterns' => RelawanShiftPattern::where('day_of_week', $dayOfWeek)
                ->with('relawan:id,name,email')
                ->get()
        ]);
    }

    // Admin tambah relawan ke pattern hari tertentu
    public function addRelawanToDay(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:' . implode(',', array_keys(RelawanShiftPattern::DAYS)),
            'relawan_id' => 'required|exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $relawanId = $request->relawan_id;

        // Cek apakah user adalah relawan
        $relawan = User::where('id', $relawanId)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        if (!$relawan) {
            return response()->json(['message' => 'User is not a relawan'], 400);
        }

        // Cek apakah sudah ada pattern untuk relawan ini di hari ini
        $existingPattern = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $relawanId)
            ->first();

        if ($existingPattern) {
            return response()->json([
                'message' => "Relawan {$relawan->name} already has pattern for " . RelawanShiftPattern::DAYS[$dayOfWeek]
            ], 400);
        }

        $pattern = RelawanShiftPattern::create([
            'day_of_week' => $dayOfWeek,
            'relawan_id' => $relawanId,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => "Added {$relawan->name} to " . RelawanShiftPattern::DAYS[$dayOfWeek] . " pattern",
            'pattern' => $pattern->load('relawan:id,name,email')
        ]);
    }

    // Admin hapus relawan dari pattern hari tertentu
    public function removeRelawanFromDay(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:' . implode(',', array_keys(RelawanShiftPattern::DAYS)),
            'relawan_id' => 'required|exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $relawanId = $request->relawan_id;

        $pattern = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $relawanId)
            ->first();

        if (!$pattern) {
            return response()->json([
                'message' => 'Pattern not found for this relawan on this day'
            ], 404);
        }

        $relawanName = $pattern->relawan->name;
        $dayName = RelawanShiftPattern::DAYS[$dayOfWeek];

        $pattern->delete();

        return response()->json([
            'success' => true,
            'message' => "Removed {$relawanName} from {$dayName} pattern",
            'deleted_pattern' => [
                'relawan_name' => $relawanName,
                'day' => $dayName,
                'day_key' => $dayOfWeek
            ]
        ]);
    }

    // Admin toggle active/inactive pattern individual
    public function togglePattern($patternId)
    {
        $pattern = RelawanShiftPattern::with('relawan:id,name')->findOrFail($patternId);

        $pattern->update([
            'is_active' => !$pattern->is_active
        ]);

        $status = $pattern->is_active ? 'activated' : 'deactivated';
        $dayName = RelawanShiftPattern::DAYS[$pattern->day_of_week];

        return response()->json([
            'success' => true,
            'message' => "Pattern {$status} for {$pattern->relawan->name} on {$dayName}",
            'pattern' => $pattern
        ]);
    }

    // Admin clear semua pattern untuk hari tertentu
    public function clearDayPattern($dayOfWeek)
    {
        if (!array_key_exists($dayOfWeek, RelawanShiftPattern::DAYS)) {
            return response()->json(['message' => 'Invalid day of week'], 400);
        }

        $deletedCount = RelawanShiftPattern::where('day_of_week', $dayOfWeek)->delete();
        $dayName = RelawanShiftPattern::DAYS[$dayOfWeek];

        return response()->json([
            'success' => true,
            'message' => "Cleared all patterns for {$dayName}",
            'deleted_count' => $deletedCount,
            'day' => $dayName
        ]);
    }

    // Admin lihat pattern untuk hari tertentu
    public function showDayPattern($dayOfWeek)
    {
        if (!array_key_exists($dayOfWeek, RelawanShiftPattern::DAYS)) {
            return response()->json(['message' => 'Invalid day of week'], 400);
        }

        $patterns = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->with('relawan:id,name,email,no_telp')
            ->orderBy('relawan_id')
            ->get();

        $dayName = RelawanShiftPattern::DAYS[$dayOfWeek];

        return response()->json([
            'day' => $dayName,
            'day_key' => $dayOfWeek,
            'relawan_count' => $patterns->count(),
            'active_count' => $patterns->where('is_active', true)->count(),
            'patterns' => $patterns
        ]);
    }

    // Admin bulk update - set pattern untuk semua hari sekaligus
    public function bulkSetPatterns(Request $request)
    {
        $request->validate([
            'patterns' => 'required|array',
            'patterns.*.day_of_week' => 'required|in:' . implode(',', array_keys(RelawanShiftPattern::DAYS)),
            'patterns.*.relawan_ids' => 'required|array|min:1',
            'patterns.*.relawan_ids.*' => 'exists:users,id'
        ]);

        $results = [];
        $totalCreated = 0;

        foreach ($request->patterns as $dayPattern) {
            $dayOfWeek = $dayPattern['day_of_week'];
            $relawanIds = $dayPattern['relawan_ids'];

            // Validasi semua relawan
            $relawans = User::whereIn('id', $relawanIds)
                ->where('role', User::ROLE_RELAWAN)
                ->get();

            if ($relawans->count() !== count($relawanIds)) {
                return response()->json([
                    'message' => "Some users are not relawan for day: " . RelawanShiftPattern::DAYS[$dayOfWeek]
                ], 400);
            }

            // Hapus pattern lama
            $deletedCount = RelawanShiftPattern::where('day_of_week', $dayOfWeek)->delete();

            // Buat pattern baru
            $dayPatterns = [];
            foreach ($relawanIds as $relawanId) {
                $dayPatterns[] = RelawanShiftPattern::create([
                    'day_of_week' => $dayOfWeek,
                    'relawan_id' => $relawanId,
                    'is_active' => true
                ]);
                $totalCreated++;
            }

            $results[] = [
                'day' => RelawanShiftPattern::DAYS[$dayOfWeek],
                'day_key' => $dayOfWeek,
                'deleted_count' => $deletedCount,
                'created_count' => count($dayPatterns),
                'relawan_names' => $relawans->pluck('name')->toArray()
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk pattern update completed",
            'total_patterns_created' => $totalCreated,
            'results' => $results
        ]);
    }
}
