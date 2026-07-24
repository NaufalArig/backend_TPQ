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
use App\Http\Controllers\DataExchangeController;
use App\Http\Controllers\DatabaseBackupController;

// PUBLIC ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword']);

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // NOTIFICATION
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    });

    // DATA EXCHANGE
    Route::get('/data-exchange/{module}/export', [DataExchangeController::class, 'export']);
    Route::post('/data-exchange/{module}/import', [DataExchangeController::class, 'import']);
    Route::get('/data-exchange/{module}/template', [DataExchangeController::class, 'template']);

    // ROLE: ADMIN & TEACHER
    Route::middleware('role:admin,teacher')->group(function () {
        Route::apiResource('santri', SantriController::class);
        Route::post('/santri/{id}/activate', [SantriController::class, 'activate']);
        Route::post('/santri/{id}/graduate', [SantriController::class, 'graduate']);

        Route::get('/kelas', [KelasController::class, 'index']);
        Route::get('/kelas/{kelas}/available-santri', [KelasController::class, 'availableSantri']);
        Route::post('/kelas/{kelas}/assign-santri', [KelasController::class, 'assignSantri']);
        Route::get('/kelas/{kelas}', [KelasController::class, 'show']);

        Route::get('/absensi-santri', [AbsensiSantriController::class, 'index']);
        Route::post('/absensi-santri', [AbsensiSantriController::class, 'store']);
        Route::get('/absensi-santri-riwayat', [AbsensiSantriController::class, 'riwayat']);
    });

    // ROLE: ADMIN
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('guru', GuruController::class);
        Route::apiResource('users', UserController::class);

        // Admin yang boleh tambah/edit/hapus kelas.
        Route::post('/kelas', [KelasController::class, 'store']);
        Route::put('/kelas/{kelas}', [KelasController::class, 'update']);
        Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy']);

        Route::apiResource('kategori-keuangan', KategoriKeuanganController::class);

        Route::apiResource('asset-categories', AssetCategoryController::class);
        Route::apiResource('assets', AssetController::class);

        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show']);

        Route::get('/database-backups', [DatabaseBackupController::class, 'index']);
        Route::post('/database-backups', [DatabaseBackupController::class, 'store']);
        Route::post('/database-backups/restore', [DatabaseBackupController::class, 'restore']);
        Route::get('/database-backups/{fileName}/download', [DatabaseBackupController::class, 'download']);
        Route::post('/database-backups/{fileName}/restore', [DatabaseBackupController::class, 'restoreExisting']);
        Route::delete('/database-backups/{fileName}', [DatabaseBackupController::class, 'destroy']);
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
