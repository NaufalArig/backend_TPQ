<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AbsensiSantriController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KategoriKeuanganController;
use App\Http\Controllers\KeuanganPembangunanController;
use App\Http\Controllers\KeuanganSppController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\ActivityLogController;

// PUBLIC ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // NOTIFICATION
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // ROLE: ADMIN & TEACHER
    Route::middleware('role:admin,teacher')->group(function () {
        Route::apiResource('santri', SantriController::class);

        Route::get('/absensi-santri', [AbsensiSantriController::class, 'index']);
        Route::post('/absensi-santri', [AbsensiSantriController::class, 'store']);
        Route::get('/absensi-santri-riwayat', [AbsensiSantriController::class, 'riwayat']);
    });

    // ROLE: ADMIN
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('guru', GuruController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('kelas', KelasController::class);
        Route::apiResource('kategori-keuangan', KategoriKeuanganController::class);

        Route::apiResource('asset-categories', AssetCategoryController::class);
        Route::apiResource('assets', AssetController::class);
        
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show']);
    });

    // ROLE: ADMIN & TREASURER
    Route::middleware('role:admin,treasurer')->group(function () {
        Route::apiResource('keuangan-spp', KeuanganSppController::class);
        Route::apiResource('keuangan-pembangunan', KeuanganPembangunanController::class);

        Route::get('/laporan/keuangan', [LaporanController::class, 'preview']);
        Route::get('/laporan/keuangan/download', [LaporanController::class, 'download']);
    });

    // SEMENTARA UNTUK FRONTEND LAMA
    Route::get('/dashboard-stats', function () {
        return response()->json([
            'total_santri' => \App\Models\Santri::count(),
            'total_guru' => \App\Models\Guru::count(),
            'total_user' => \App\Models\User::count(),
        ]);
    });
});
