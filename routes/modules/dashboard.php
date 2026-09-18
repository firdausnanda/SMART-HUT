<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckDashboardAccess;
use Illuminate\Support\Facades\Route;

// =========================================================================
// DASHBOARD
// =========================================================================

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['verified', CheckDashboardAccess::class])
    ->name('dashboard');

Route::get('/public/dashboard', [DashboardController::class, 'publicDashboard'])->name('public.dashboard');
Route::get('/public/dashboard-yoy', [DashboardController::class, 'publicYoYDashboard'])->name('public.dashboard-yoy');
Route::get('/dashboard/export-rehab-lahan', [DashboardController::class, 'exportRehabLahan'])->name('dashboard.export-rehab-lahan');

// =========================================================================
// PROFILE
// =========================================================================

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// =========================================================================
// LOCATION HELPERS (dropdown dependencies)
// =========================================================================

Route::get('/locations/regencies/{provinceId}', [LocationController::class, 'getRegencies'])->name('locations.regencies');
Route::get('/locations/districts/{regencyId}', [LocationController::class, 'getDistricts'])->name('locations.districts');
Route::get('/locations/villages/{districtId}', [LocationController::class, 'getVillages'])->name('locations.villages');
