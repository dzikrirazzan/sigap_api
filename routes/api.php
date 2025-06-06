<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PanicController;
use App\Http\Controllers\RelawanShiftController;
use App\Http\Controllers\RelawanShiftPatternController;
use App\Http\Controllers\ShiftAutomationController;

// Route publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/refresh', [AuthController::class, 'refreshToken']);

// Route terproteksi (untuk semua user terautentikasi)
Route::middleware('auth:sanctum')->group(function () {
    // Profil user yang login
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // User bisa melihat dan mengupdate profil mereka sendiri
    Route::get('/users/{id}', [AuthController::class, 'show']);
    Route::put('/users/{id}', [AuthController::class, 'update']);

    // Upload foto
    Route::post('/upload-photo', [ReportController::class, 'uploadPhoto']);

    // ====== LAPORAN FOTO ======
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/{reportId}', [ReportController::class, 'show']);
    Route::put('/reports/{reportId}', [ReportController::class, 'update']);
    Route::delete('/reports/{reportId}', [ReportController::class, 'destroy']);
    Route::get('/problem-types', [ReportController::class, 'getProblemTypes']);

    // ====== PANIC BUTTON ======
    Route::post('/panic', [PanicController::class, 'store']);
    Route::get('/panic/today', [PanicController::class, 'today']);

    // ====== SHARED ROUTES (RELAWAN & ADMIN) ======
    Route::middleware(['auth:sanctum'])->group(function () {
        // Update panic status - accessible by both relawan and admin
        Route::put('/panic/{panicId}/status', [PanicController::class, 'updateStatus']);
    });

    // ====== RELAWAN ROUTES ======
    Route::middleware('relawan')->group(function () {
        Route::get('/relawan/my-shifts', [PanicController::class, 'getMyShifts']);
    });

    // ====== ADMIN ROUTES ======
    Route::middleware('admin')->prefix('admin')->group(function () {
        // User Management
        Route::get('/users', [AuthController::class, 'getAllUsers']);
        Route::get('/relawan', [AuthController::class, 'getAllRelawan']);
        Route::post('/register-relawan', [AuthController::class, 'registerRelawan']);
        Route::post('/register-admin', [AuthController::class, 'registerAdmin']);
        Route::delete('/users/{id}', [AuthController::class, 'destroy']);

        // Panic Management
        Route::get('/panic', [PanicController::class, 'adminIndex']);
        Route::delete('/panic/{panicId}', [PanicController::class, 'destroy']);
        Route::get('/relawan-on-duty', [PanicController::class, 'getTodayRelawan']);

        // ✅ Weekly Pattern Shift Management - Simple & Automatic
        Route::get('/shifts', [RelawanShiftController::class, 'index']);
        Route::get('/shifts/weekly-patterns', [RelawanShiftController::class, 'getWeeklyPatterns']);
        Route::post('/shifts/generate-from-patterns', [RelawanShiftController::class, 'generateFromPatterns']);
        Route::delete('/shifts/date/{date}', [RelawanShiftController::class, 'deleteShiftsByDate']);
        Route::get('/relawans', [RelawanShiftController::class, 'getRelawans']);

        // ✅ Weekly Pattern Management (Monday-Sunday assignments)
        Route::post('/shifts/set-day-pattern', [RelawanShiftController::class, 'setDayPattern']);
        Route::post('/shifts/add-relawan-to-day', [RelawanShiftController::class, 'addRelawanToDay']);
        Route::delete('/shifts/remove-relawan-from-day', [RelawanShiftController::class, 'removeRelawanFromDay']);

        // Shift Automation Management
        Route::prefix('automation')->group(function () {
            Route::get('/status', [ShiftAutomationController::class, 'getStatus']);
            Route::post('/toggle', [ShiftAutomationController::class, 'toggleAutomation']);
            Route::post('/force-generation', [ShiftAutomationController::class, 'forceGeneration']);
            Route::get('/logs', [ShiftAutomationController::class, 'getLogs']);
            Route::post('/test', [ShiftAutomationController::class, 'testAutomation']);
        });
    });
});
