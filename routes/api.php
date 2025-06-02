<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PanicController;
use App\Http\Controllers\RelawanShiftController;
use App\Http\Controllers\AdminShiftController;
use App\Http\Controllers\RelawanShiftPatternController;

// Route publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/refresh', [AuthController::class, 'refreshToken']);

// Route terproteksi (untuk semua user terautentikasi)
Route::middleware('auth:sanctum')->group(function () {
    // Profil user yang login
    Route::get('/profile', [AuthController::class, 'profile']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // User bisa melihat dan mengupdate profil mereka sendiri
    Route::get('/users/{id}', [AuthController::class, 'show']);
    Route::put('/users/{id}', [AuthController::class, 'update']);

    // Upload foto (endpoint baru)
    Route::post('/upload-photo', [ReportController::class, 'uploadPhoto']);

    // ====== ROUTE UNTUK LAPORAN FOTO (BARU) ======
    // CRUD Laporan Foto
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/{reportId}', [ReportController::class, 'show']);
    Route::put('/reports/{reportId}', [ReportController::class, 'update']);
    Route::delete('/reports/{reportId}', [ReportController::class, 'destroy']);

    // Mendapatkan tipe masalah (untuk dropdown)
    Route::get('/problem-types', [ReportController::class, 'getProblemTypes']);
    // ============================================

    // Route khusus Relawan
    Route::middleware('relawan')->prefix('relawan')->group(function () {
        // Tambahkan rute spesifik untuk relawan di sini
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Relawan Dashboard']);
        });
        // Relawan cek shift sendiri
        Route::get('/shifts/me', [RelawanShiftController::class, 'myShifts']);
    });

    // Route khusus Admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Manajemen User (hanya admin)
        Route::get('/users', [AuthController::class, 'getAllUsers']); // Regular users
        Route::get('/relawan', [AuthController::class, 'getAllRelawan']); // Relawan users
        Route::get('/all-users', [AuthController::class, 'index']); // All users

        // Register khusus admin dan relawan
        Route::post('/register-relawan', [AuthController::class, 'registerRelawan']);
        Route::post('/register-admin', [AuthController::class, 'registerAdmin']);

        // Delete user
        Route::delete('/users/{id}', [AuthController::class, 'destroy']);

        // Admin Dashboard
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Admin Dashboard']);
        });

        // Manajemen Shift Relawan
        Route::get('/shifts', [RelawanShiftController::class, 'index']);
        Route::post('/shifts', [RelawanShiftController::class, 'store']);
        Route::delete('/shifts/{date}', [RelawanShiftController::class, 'destroy']);
        Route::get('/relawans', [RelawanShiftController::class, 'getRelawans']);
        Route::post('/shifts/auto-assign', [RelawanShiftController::class, 'autoAssign']); // Updated to support patterns

        // Admin assign shift relawan
        Route::post('/assign-shift', [AdminShiftController::class, 'assign']);

        // Admin get shift relawan per minggu
        Route::get('/shifts/week', [AdminShiftController::class, 'week']);

        // Manajemen Shift Pattern (Pola Mingguan)
        Route::prefix('shift-patterns')->group(function () {
            // CRUD shift patterns
            Route::get('/', [RelawanShiftPatternController::class, 'index']); // Get all patterns grouped by day
            Route::post('/', [RelawanShiftPatternController::class, 'store']); // Create/update patterns for specific day
            Route::delete('/{id}', [RelawanShiftPatternController::class, 'destroy']); // Delete specific pattern
            Route::delete('/day/{dayOfWeek}', [RelawanShiftPatternController::class, 'destroyDay']); // Delete all patterns for a day

            // Pattern utilities
            Route::get('/relawan', [RelawanShiftPatternController::class, 'getAvailableRelawan']); // Get available relawan
            Route::post('/copy', [RelawanShiftPatternController::class, 'copyPattern']); // Copy pattern between days
            Route::post('/generate-shifts', [RelawanShiftPatternController::class, 'generateShifts']); // Generate actual shifts from patterns
        });
    });

    // Panic Button
    Route::middleware('auth:sanctum')->post('/panic', [PanicController::class, 'store']);
    Route::middleware('auth:sanctum')->get('/panic-today', [PanicController::class, 'today']); // Admin dan Relawan bisa akses
    Route::middleware(['auth:sanctum', 'relawan'])->post('/panic/{panicId}/handle', [PanicController::class, 'handle']);
    Route::middleware(['auth:sanctum', 'relawan'])->post('/panic/{panicId}/resolve', [PanicController::class, 'resolve']);
    Route::middleware(['auth:sanctum', 'admin'])->get('/panic/admin', [PanicController::class, 'adminIndex']);
    Route::middleware(['auth:sanctum', 'admin'])->get('/panic/relawan-today', [PanicController::class, 'getTodayRelawan']);
});
