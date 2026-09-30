<?php

use App\Http\Middleware\CheckDashboardAccess;
use Illuminate\Support\Facades\Route;
use Modules\Dashboard\App\Http\Controllers\DashboardController;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['verified', CheckDashboardAccess::class])
        ->name('dashboard');

    Route::get('/public/dashboard', [DashboardController::class, 'publicDashboard'])->name('public.dashboard');
    Route::get('/public/dashboard-yoy', [DashboardController::class, 'publicYoYDashboard'])->name('public.dashboard-yoy');
    Route::get('/dashboard/export-rehab-lahan', [DashboardController::class, 'exportRehabLahan'])->name('dashboard.export-rehab-lahan');
});
