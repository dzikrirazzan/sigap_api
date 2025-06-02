<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PanicController;
use App\Http\Controllers\RelawanShiftController;

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
    
    // ====== RELAWAN ROUTES ======
    Route::middleware('relawan')->group(function () {
        Route::post('/panic/{panicId}/handle', [PanicController::class, 'handle']);
        Route::post('/panic/{panicId}/resolve', [PanicController::class, 'resolve']);
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
        Route::get('/relawan-on-duty', [PanicController::class, 'getTodayRelawan']);

        // Shift Management - Simple daily assignment
        Route::get('/shifts', [RelawanShiftController::class, 'index']);
        Route::post('/shifts', [RelawanShiftController::class, 'store']);
        Route::delete('/shifts/{date}', [RelawanShiftController::class, 'destroy']);
        Route::get('/relawans', [RelawanShiftController::class, 'getRelawans']);
    });
});
