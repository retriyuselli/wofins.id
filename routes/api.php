<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FinanceController;
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
