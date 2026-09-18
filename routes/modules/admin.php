<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CdkController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// USER MANAGEMENT
// =========================================================================

Route::get('users/export', [UserController::class, 'export'])->middleware('permission:users.export')->name('users.export');
Route::get('users/template', [UserController::class, 'exportTemplate'])->middleware('permission:users.create')->name('users.template');
Route::post('users/import', [UserController::class, 'import'])->middleware('permission:users.create')->name('users.import');

Route::resource('users', UserController::class)->only(['create', 'store'])->middleware('permission:users.create');
Route::resource('users', UserController::class)->only(['index', 'show'])->middleware('permission:users.view');
Route::resource('users', UserController::class)->only(['edit', 'update'])->middleware('permission:users.edit');
Route::resource('users', UserController::class)->only(['destroy'])->middleware('permission:users.delete');

// =========================================================================
// CDK MANAGEMENT
// =========================================================================

Route::resource('cdks', CdkController::class)->only(['index'])->middleware('permission:cdks.view');
Route::resource('cdks', CdkController::class)->only(['store'])->middleware('permission:cdks.create');
Route::resource('cdks', CdkController::class)->only(['update'])->middleware('permission:cdks.edit');
Route::resource('cdks', CdkController::class)->only(['destroy'])->middleware('permission:cdks.delete');

// =========================================================================
// ACTIVITY LOG
// =========================================================================

Route::resource('activity-log', ActivityLogController::class)->only(['index'])->middleware('permission:activity-log.view');

// =========================================================================
// BACKUP MANAGEMENT
// =========================================================================

Route::middleware('permission:backups.manage')->group(function () {
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download')->where('filename', '.*');
    Route::delete('backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy')->where('filename', '.*');
});
