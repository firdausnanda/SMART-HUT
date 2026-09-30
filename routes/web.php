<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CdkController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index']);

Route::middleware('auth')->group(function () {
    // User management
    Route::get('users/export', [UserController::class, 'export'])->middleware('permission:users.export')->name('users.export');
    Route::get('users/template', [UserController::class, 'exportTemplate'])->middleware('permission:users.create')->name('users.template');
    Route::post('users/import', [UserController::class, 'import'])->middleware('permission:users.create')->name('users.import');

    Route::resource('users', UserController::class)->only(['create', 'store'])->middleware('permission:users.create');
    Route::resource('users', UserController::class)->only(['index', 'show'])->middleware('permission:users.view');
    Route::resource('users', UserController::class)->only(['edit', 'update'])->middleware('permission:users.edit');
    Route::resource('users', UserController::class)->only(['destroy'])->middleware('permission:users.delete');

    // CDK management
    Route::resource('cdks', CdkController::class)->only(['index'])->middleware('permission:cdks.view');
    Route::resource('cdks', CdkController::class)->only(['store'])->middleware('permission:cdks.create');
    Route::resource('cdks', CdkController::class)->only(['update'])->middleware('permission:cdks.edit');
    Route::resource('cdks', CdkController::class)->only(['destroy'])->middleware('permission:cdks.delete');

    Route::resource('activity-log', ActivityLogController::class)->only(['index'])->middleware('permission:activity-log.view');

    // Backup management
    Route::middleware('permission:backups.manage')->group(function () {
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'create'])->name('backups.create');
        Route::get('backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download')->where('filename', '.*');
        Route::delete('backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy')->where('filename', '.*');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/locations/regencies/{provinceId}', [LocationController::class, 'getRegencies'])->name('locations.regencies');
    Route::get('/locations/districts/{regencyId}', [LocationController::class, 'getDistricts'])->name('locations.districts');
    Route::get('/locations/villages/{districtId}', [LocationController::class, 'getVillages'])->name('locations.villages');

    Route::impersonate();
});

// Import status polling endpoint (used by frontend progress bar)
Route::middleware('auth')->get('/import-status/{batch}', function (\App\Models\ImportBatch $batch) {
    if ($batch->user_id !== auth()->id()) abort(403);
    return response()->json([
        'status'          => $batch->status,
        'imported_count'  => $batch->imported_count,
        'error_message'   => $batch->error_message,
    ]);
})->name('import.status');

require __DIR__ . '/auth.php';
