<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelawanShiftPattern;
use App\Models\User;
use App\Models\RelawanShift;
use Carbon\Carbon;

class RelawanShiftPatternController extends Controller
{
    // Get all shift patterns (Admin)
    public function index()
    {
        $patterns = RelawanShiftPattern::with('relawan:id,name,email,no_telp')
            ->orderBy('day_of_week')
            ->orderBy('relawan_id')
            ->get();

        // Group by day of week
        $groupedPatterns = $patterns->groupBy('day_of_week');

        // Format response
        $formattedPatterns = [];
        foreach (RelawanShiftPattern::DAYS as $dayKey => $dayName) {
            $formattedPatterns[$dayKey] = [
                'day_name' => $dayName,
                'day_key' => $dayKey,
                'relawan' => $groupedPatterns->get($dayKey, collect())->map(function ($pattern) {
                    return [
                        'id' => $pattern->id,
                        'relawan_id' => $pattern->relawan_id,
                        'relawan' => $pattern->relawan,
                        'is_active' => $pattern->is_active,
                        'created_at' => $pattern->created_at,
                    ];
                })
            ];
        }

        return response()->json([
            'success' => true,
            'patterns' => $formattedPatterns,
            'total_patterns' => $patterns->count()
        ]);
    }

    // Create/Update shift pattern for specific day (Admin)
    public function store(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'relawan_ids' => 'required|array|min:1|max:4',
            'relawan_ids.*' => 'exists:users,id'
        ]);

        // Validate that all users are relawan
        $relawans = User::whereIn('id', $request->relawan_ids)
            ->where('role', User::ROLE_RELAWAN)
            ->get();

        if ($relawans->count() !== count($request->relawan_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some users are not relawan'
            ], 400);
        }

        // Delete existing patterns for this day
        RelawanShiftPattern::where('day_of_week', $request->day_of_week)->delete();

        // Create new patterns
        $patterns = [];
        foreach ($request->relawan_ids as $relawanId) {
            $patterns[] = RelawanShiftPattern::create([
                'day_of_week' => $request->day_of_week,
                'relawan_id' => $relawanId,
                'is_active' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shift pattern updated successfully',
            'day' => RelawanShiftPattern::DAYS[$request->day_of_week],
            'patterns' => RelawanShiftPattern::where('day_of_week', $request->day_of_week)
                ->with('relawan:id,name,email,no_telp')
                ->get()
        ]);
    }

    // Delete all patterns for specific day (Admin)
    public function destroyDay($dayOfWeek)
    {
        if (!array_key_exists($dayOfWeek, RelawanShiftPattern::DAYS)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid day of week'
            ], 400);
        }

        $deletedCount = RelawanShiftPattern::where('day_of_week', $dayOfWeek)->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} patterns for " . RelawanShiftPattern::DAYS[$dayOfWeek]
        ]);
    }

    // Delete specific pattern (Admin)
    public function destroy($id)
    {
        $pattern = RelawanShiftPattern::findOrFail($id);
        $dayName = $pattern->day_name;
        $relawanName = $pattern->relawan->name;
        
        $pattern->delete();

        return response()->json([
            'success' => true,
            'message' => "Removed {$relawanName} from {$dayName} shift"
        ]);
    }

    // Generate shifts from patterns for date range (Admin)
    public function generateShifts(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'overwrite' => 'boolean'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $overwrite = $request->get('overwrite', false);

        $results = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayOfWeek = strtolower($current->format('l')); // monday, tuesday, etc.
            $dateString = $current->toDateString();

            // Check if shifts already exist
            $existingShifts = RelawanShift::where('shift_date', $dateString)->count();
            
            if ($existingShifts > 0 && !$overwrite) {
                $results[] = [
                    'date' => $dateString,
                    'day' => RelawanShiftPattern::DAYS[$dayOfWeek],
                    'status' => 'skipped',
                    'message' => 'Shifts already exist'
                ];
                $current->addDay();
                continue;
            }

            // Get patterns for this day of week
            $patterns = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->get();

            if ($patterns->isEmpty()) {
                $results[] = [
                    'date' => $dateString,
                    'day' => RelawanShiftPattern::DAYS[$dayOfWeek],
                    'status' => 'skipped',
                    'message' => 'No pattern defined for this day'
                ];
                $current->addDay();
                continue;
            }

            // Delete existing shifts if overwrite is true
            if ($overwrite && $existingShifts > 0) {
                RelawanShift::where('shift_date', $dateString)->delete();
            }

            // Create shifts from patterns
            $shifts = [];
            foreach ($patterns as $pattern) {
                $shifts[] = RelawanShift::create([
                    'relawan_id' => $pattern->relawan_id,
                    'shift_date' => $dateString
                ]);
            }

            $results[] = [
                'date' => $dateString,
                'day' => RelawanShiftPattern::DAYS[$dayOfWeek],
                'status' => 'success',
                'assigned_count' => count($shifts),
                'relawan' => $patterns->pluck('relawan.name')->toArray()
            ];

            $current->addDay();
        }

        return response()->json([
            'success' => true,
            'message' => 'Shift generation completed',
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $startDate->diffInDays($endDate) + 1
            ],
            'results' => $results,
            'summary' => [
                'successful' => collect($results)->where('status', 'success')->count(),
                'skipped' => collect($results)->where('status', 'skipped')->count(),
            ]
        ]);
    }

    // Get available relawan for dropdown (Admin)
    public function getAvailableRelawan()
    {
        $relawans = User::where('role', User::ROLE_RELAWAN)
            ->select('id', 'name', 'email', 'no_telp')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'relawan' => $relawans
        ]);
    }

    // Copy pattern from one day to another (Admin)
    public function copyPattern(Request $request)
    {
        $request->validate([
            'from_day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'to_day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'overwrite' => 'boolean'
        ]);

        $fromDay = $request->from_day;
        $toDay = $request->to_day;
        $overwrite = $request->get('overwrite', false);

        if ($fromDay === $toDay) {
            return response()->json([
                'success' => false,
                'message' => 'Source and destination day cannot be the same'
            ], 400);
        }

        // Check if destination day has patterns
        $existingPatterns = RelawanShiftPattern::where('day_of_week', $toDay)->count();
        if ($existingPatterns > 0 && !$overwrite) {
            return response()->json([
                'success' => false,
                'message' => 'Destination day already has patterns. Use overwrite=true to replace.'
            ], 400);
        }

        // Get source patterns
        $sourcePatterns = RelawanShiftPattern::where('day_of_week', $fromDay)->get();
        
        if ($sourcePatterns->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No patterns found for source day'
            ], 400);
        }

        // Delete existing patterns if overwrite
        if ($overwrite) {
            RelawanShiftPattern::where('day_of_week', $toDay)->delete();
        }

        // Copy patterns
        $newPatterns = [];
        foreach ($sourcePatterns as $pattern) {
            $newPatterns[] = RelawanShiftPattern::create([
                'day_of_week' => $toDay,
                'relawan_id' => $pattern->relawan_id,
                'is_active' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pattern copied successfully',
            'from_day' => RelawanShiftPattern::DAYS[$fromDay],
            'to_day' => RelawanShiftPattern::DAYS[$toDay],
            'copied_count' => count($newPatterns),
            'patterns' => RelawanShiftPattern::where('day_of_week', $toDay)
                ->with('relawan:id,name,email,no_telp')
                ->get()
        ]);
    }
}
