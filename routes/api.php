<?php

use App\Http\Controllers\Api\V1\AbsensiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Wofins iOS / mobile
|--------------------------------------------------------------------------
|
| Prefix otomatis: /api
| Versioning: /api/v1/...
|
*/

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        Route::get('/me', [MeController::class, 'show'])->name('api.v1.me');
        Route::patch('/me', [MeController::class, 'update'])->name('api.v1.me.update');
        Route::post('/me/avatar', [MeController::class, 'updateAvatar'])->name('api.v1.me.avatar');
        Route::put('/me/password', [MeController::class, 'updatePassword'])
            ->middleware('throttle:5,1')
            ->name('api.v1.me.password');

        Route::get('/me/compensation', [MeController::class, 'compensation'])
            ->name('api.v1.me.compensation');
        Route::get('/me/leave-balances', [MeController::class, 'leaveBalances'])
            ->name('api.v1.me.leave-balances');
        Route::get('/me/schedule', [MeController::class, 'schedule'])
            ->name('api.v1.me.schedule');
        Route::get('/me/leave-types', [MeController::class, 'leaveTypes'])
            ->name('api.v1.me.leave-types');

        Route::get('/me/leave-requests', [LeaveRequestController::class, 'index'])
            ->name('api.v1.me.leave-requests.index');
        Route::post('/me/leave-requests', [LeaveRequestController::class, 'store'])
            ->name('api.v1.me.leave-requests.store');
        Route::get('/me/leave-requests/{id}', [LeaveRequestController::class, 'show'])
            ->whereNumber('id')
            ->name('api.v1.me.leave-requests.show');

        Route::prefix('absensi')->group(function () {
            Route::get('/hari-ini', [AbsensiController::class, 'hariIni'])
                ->name('api.v1.absensi.hari-ini');
            Route::get('/lokasi', [AbsensiController::class, 'lokasi'])
                ->name('api.v1.absensi.lokasi');
            Route::get('/cek-lokasi', [AbsensiController::class, 'cekLokasi'])
                ->name('api.v1.absensi.cek-lokasi');
            Route::post('/masuk', [AbsensiController::class, 'masuk'])
                ->middleware('throttle:20,1')
                ->name('api.v1.absensi.masuk');
            Route::post('/pulang', [AbsensiController::class, 'pulang'])
                ->middleware('throttle:20,1')
                ->name('api.v1.absensi.pulang');
            Route::get('/riwayat', [AbsensiController::class, 'riwayat'])
                ->name('api.v1.absensi.riwayat');
            Route::get('/ringkasan', [AbsensiController::class, 'ringkasan'])
                ->name('api.v1.absensi.ringkasan');
        });

        Route::prefix('finance')->group(function () {
            Route::get('/dashboard', [FinanceController::class, 'dashboard'])
                ->name('api.v1.finance.dashboard');
            Route::get('/projects', [FinanceController::class, 'projects'])
                ->name('api.v1.finance.projects');
            Route::get('/projects/{id}', [FinanceController::class, 'projectShow'])
                ->whereNumber('id')
                ->name('api.v1.finance.projects.show');
            Route::get('/transactions', [FinanceController::class, 'transactions'])
                ->name('api.v1.finance.transactions');
            Route::get('/reports/summary', [FinanceController::class, 'reportSummary'])
                ->name('api.v1.finance.reports.summary');
            Route::get('/piutangs', [FinanceController::class, 'piutangs'])
                ->name('api.v1.finance.piutangs');
            Route::get('/piutangs/{id}', [FinanceController::class, 'piutangShow'])
                ->whereNumber('id')
                ->name('api.v1.finance.piutangs.show');
        });
    });
});
