<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AbsensiSantriController;

//PUBLIC ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

//PROTECTED ROUTES (AUTH)
Route::middleware('auth:sanctum')->group(function () {

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());

    // DASHBOARD (SEMUA ROLE)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // NOTIFICATION (SEMUA ROLE)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    //ROLE: ADMIN & GURU
    Route::middleware('role:admin,guru')->group(function () {
        Route::apiResource('santri', SantriController::class);

        Route::get('/absensi-santri', [AbsensiSantriController::class, 'index']);
        Route::post('/absensi-santri', [AbsensiSantriController::class, 'store']);
    });

    //ROLE: ADMIN
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('guru', GuruController::class);
        Route::apiResource('users', UserController::class);
    });

    //ROLE: ADMIN & BENDAHARA
    Route::middleware('role:admin,bendahara')->group(function () {
        Route::apiResource('keuangan', KeuanganController::class);

        Route::get('/laporan/keuangan', [LaporanController::class, 'preview']);
        Route::get('/laporan/keuangan/download', [LaporanController::class, 'download']);
    });
});

Route::middleware('auth:sanctum')->get('/dashboard-stats', function () {
    return response()->json([
        'total_santri' => \App\Models\Santri::count(),
        'total_guru' => \App\Models\Guru::count(),
        'total_user' => \App\Models\User::count(),
    ]);
});
