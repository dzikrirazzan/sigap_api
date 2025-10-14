<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelawanShift;
use App\Models\RelawanShiftPattern;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class RelawanShiftController extends Controller
{
    // ✅ Generate shifts from weekly patterns - Auto loops every week
    public function generateFromPatterns(Request $request)
    {
        $request->validate([
            'days' => 'integer|min:1|max:30'
        ]);

        $days = $request->get('days', 7);

        // Call the simplified auto-generation command
        Artisan::call('shifts:auto-generate', [
            '--days' => $days
        ]);

        $output = Artisan::output();

        return response()->json([
            'success' => true,
            'message' => "Generated shifts for {$days} days using weekly patterns",
            'command_output' => $output,
            'note' => 'Weekly patterns automatically loop. Admin can modify patterns for each day of the week.'
        ]);
    }

    // ✅ Admin view current shifts and weekly patterns
    public function index(Request $request)
    {
        // Get actual shifts for the next 7 days
        $actualShifts = RelawanShift::with('relawan:id,name,email,no_telp')
            ->whereBetween('shift_date', [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays(7)->toDateString()
            ])
            ->orderBy('shift_date', 'asc')
            ->get()
            ->groupBy('shift_date');

        // Get weekly patterns for reference
        $weeklyPatterns = RelawanShiftPattern::with('relawan:id,name,email')
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get()
            ->groupBy('day_of_week');

        return response()->json([
            'success' => true,
            'actual_shifts' => $actualShifts,
            'weekly_patterns' => $weeklyPatterns,
            'note' => 'Weekly patterns loop automatically. Modify patterns to change future shifts.'
        ]);
    }

    // ✅ Admin assigns relawan to specific day of week (Monday-Sunday)
    public function setDayPattern(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'relawan_ids' => 'required|array|min:1',
            'relawan_ids.*' => 'exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $relawanIds = $request->relawan_ids;

        // Validate all users are relawan
        $relawans = User::whereIn('id', $relawanIds)
            ->where('role', User::ROLE_RELAWAN)
            ->get();

        if ($relawans->count() !== count($relawanIds)) {
            return response()->json(['message' => 'Some users are not relawan'], 400);
        }

        // ✅ Delete old patterns for this day and insert new ones
        RelawanShiftPattern::where('day_of_week', $dayOfWeek)->delete();

        // Insert new patterns
        $newPatterns = [];
        foreach ($relawanIds as $relawanId) {
            $newPatterns[] = RelawanShiftPattern::create([
                'day_of_week' => $dayOfWeek,
                'relawan_id' => $relawanId,
                'is_active' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Updated {$dayOfWeek} pattern with " . count($relawanIds) . " relawan",
            'day_of_week' => $dayOfWeek,
            'relawan_assigned' => $relawans->pluck('name'),
            'patterns_created' => count($newPatterns)
        ]);
    }

    // ✅ Admin deletes specific relawan from specific day
    public function removeRelawanFromDay(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'relawan_id' => 'required|exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $relawanId = $request->relawan_id;

        // Find and delete the specific pattern
        $pattern = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $relawanId)
            ->first();

        if (!$pattern) {
            return response()->json([
                'message' => "Relawan not found in {$dayOfWeek} pattern"
            ], 404);
        }

        $relawanName = $pattern->relawan->name;
        $pattern->delete();

        return response()->json([
            'success' => true,
            'message' => "Removed {$relawanName} from {$dayOfWeek} pattern",
            'day_of_week' => $dayOfWeek,
            'removed_relawan' => $relawanName
        ]);
    }

    // ✅ Admin adds relawan to specific day (without removing others)
    public function addRelawanToDay(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'relawan_id' => 'required|exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $relawanId = $request->relawan_id;

        // Validate user is relawan
        $relawan = User::where('id', $relawanId)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        if (!$relawan) {
            return response()->json(['message' => 'User is not a relawan'], 400);
        }

        // Check if already exists
        $existing = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $relawanId)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => "{$relawan->name} is already assigned to {$dayOfWeek}"
            ], 400);
        }

        // Add to pattern
        RelawanShiftPattern::create([
            'day_of_week' => $dayOfWeek,
            'relawan_id' => $relawanId,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => "Added {$relawan->name} to {$dayOfWeek} pattern",
            'day_of_week' => $dayOfWeek,
            'added_relawan' => $relawan->name
        ]);
    }

    // ✅ View current weekly patterns (Monday-Sunday setup)
    public function getWeeklyPatterns()
    {
        $patterns = RelawanShiftPattern::with('relawan:id,name,email')
            ->where('is_active', true)
            ->get()
            ->groupBy('day_of_week');

        $weeklySchedule = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            $dayPatterns = $patterns->get($day, collect());
            $weeklySchedule[$day] = [
                'day_name' => RelawanShiftPattern::DAYS[$day],
                'relawan_count' => $dayPatterns->count(),
                'relawan' => $dayPatterns->map(function ($pattern) {
                    return [
                        'id' => $pattern->relawan->id,
                        'name' => $pattern->relawan->name,
                        'email' => $pattern->relawan->email
                    ];
                })
            ];
        }

        return response()->json([
            'success' => true,
            'weekly_patterns' => $weeklySchedule,
            'note' => 'These patterns repeat automatically every week'
        ]);
    }

    // Get all available relawan for assignments
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

    // ✅ Admin delete all shifts for specific date (if needed for manual override)
    public function deleteShiftsByDate($date)
    {
        $deletedCount = RelawanShift::where('shift_date', $date)->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} shifts for {$date}",
            'note' => 'Run generate-from-patterns to recreate shifts from weekly patterns'
        ]);
    }

    // ✅ Admin replaces specific relawan on specific day with another relawan
    public function replaceRelawanOnDay(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'old_relawan_id' => 'required|exists:users,id',
            'new_relawan_id' => 'required|exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $oldRelawanId = $request->old_relawan_id;
        $newRelawanId = $request->new_relawan_id;

        // Validate both users are relawan
        $oldRelawan = User::where('id', $oldRelawanId)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        $newRelawan = User::where('id', $newRelawanId)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        if (!$oldRelawan || !$newRelawan) {
            return response()->json(['message' => 'Both users must be relawan'], 400);
        }

        // Check if old relawan exists in pattern
        $oldPattern = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $oldRelawanId)
            ->first();

        if (!$oldPattern) {
            return response()->json([
                'message' => "{$oldRelawan->name} is not assigned to {$dayOfWeek}"
            ], 400);
        }

        // Check if new relawan already exists in pattern
        $existingNewPattern = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $newRelawanId)
            ->first();

        if ($existingNewPattern) {
            return response()->json([
                'message' => "{$newRelawan->name} is already assigned to {$dayOfWeek}"
            ], 400);
        }

        // Replace: Delete old and create new
        $oldPattern->delete();

        RelawanShiftPattern::create([
            'day_of_week' => $dayOfWeek,
            'relawan_id' => $newRelawanId,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => "Replaced {$oldRelawan->name} with {$newRelawan->name} on {$dayOfWeek}",
            'day_of_week' => $dayOfWeek,
            'old_relawan' => $oldRelawan->name,
            'new_relawan' => $newRelawan->name
        ]);
    }

    // ✅ Admin updates specific relawan assignment on specific day
    public function updateRelawanOnDay(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'old_relawan_id' => 'required|exists:users,id',
            'new_relawan_id' => 'required|exists:users,id'
        ]);

        $dayOfWeek = $request->day_of_week;
        $oldRelawanId = $request->old_relawan_id;
        $newRelawanId = $request->new_relawan_id;

        // Validate both users are relawan
        $oldRelawan = User::where('id', $oldRelawanId)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        $newRelawan = User::where('id', $newRelawanId)
            ->where('role', User::ROLE_RELAWAN)
            ->first();

        if (!$oldRelawan || !$newRelawan) {
            return response()->json(['message' => 'Both users must be relawan'], 400);
        }

        // Find the pattern to update
        $pattern = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $oldRelawanId)
            ->first();

        if (!$pattern) {
            return response()->json([
                'message' => "{$oldRelawan->name} is not assigned to {$dayOfWeek}"
            ], 400);
        }

        // Check if new relawan already exists in pattern
        $existingNewPattern = RelawanShiftPattern::where('day_of_week', $dayOfWeek)
            ->where('relawan_id', $newRelawanId)
            ->first();

        if ($existingNewPattern) {
            return response()->json([
                'message' => "{$newRelawan->name} is already assigned to {$dayOfWeek}"
            ], 400);
        }

        // Update the pattern
        $pattern->update([
            'relawan_id' => $newRelawanId
        ]);

        return response()->json([
            'success' => true,
            'message' => "Updated assignment on {$dayOfWeek}: {$oldRelawan->name} → {$newRelawan->name}",
            'day_of_week' => $dayOfWeek,
            'old_relawan' => $oldRelawan->name,
            'new_relawan' => $newRelawan->name,
            'pattern_id' => $pattern->id
        ]);
    }
}
