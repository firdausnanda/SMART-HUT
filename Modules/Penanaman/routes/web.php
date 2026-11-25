<?php

use Illuminate\Support\Facades\Route;
use Modules\Penanaman\App\Http\Controllers\PenanamanAccountController;
use Modules\Penanaman\App\Http\Controllers\PenanamanController;
use Modules\Penanaman\App\Http\Controllers\PenanamanTransferController;
use Modules\Penanaman\App\Http\Controllers\PenanamanTargetController;

Route::middleware('auth')->group(function () {
    Route::prefix('penanaman')->name('penanaman.')->group(function () {
        Route::get('export', [PenanamanTransferController::class, 'export'])->middleware('permission:penanaman.export')->name('export');
        Route::get('import-template', [PenanamanTransferController::class, 'template'])->middleware('permission:penanaman.import')->name('import-template');
        Route::post('import-preview', [PenanamanTransferController::class, 'previewImport'])->middleware('permission:penanaman.import')->name('preview-import');
        Route::get('import-preview/{batch}', [PenanamanTransferController::class, 'showPreview'])->middleware('permission:penanaman.import')->name('show-preview');
        Route::post('import-commit/{batch}', [PenanamanTransferController::class, 'commitImport'])->middleware('permission:penanaman.import')->name('commit-import');
        Route::get('target-value', [PenanamanTargetController::class, 'value'])->middleware('permission:penanaman.target')->name('targets.value');
        Route::get('targets', [PenanamanTargetController::class, 'index'])->middleware('permission:penanaman.target')->name('targets.index');
        Route::put('targets/{regency}/{year}', [PenanamanTargetController::class, 'update'])->whereNumber('year')->middleware('permission:penanaman.target')->name('targets.update');
        Route::get('accounts', [PenanamanAccountController::class, 'index'])->middleware('role:admin|admin_provinsi')->name('accounts.index');
        Route::post('accounts', [PenanamanAccountController::class, 'store'])->middleware('role:admin|admin_provinsi')->name('accounts.store');
        Route::put('accounts/{user}', [PenanamanAccountController::class, 'update'])->middleware('role:admin|admin_provinsi')->name('accounts.update');
        Route::delete('accounts/{user}', [PenanamanAccountController::class, 'destroy'])->middleware('role:admin|admin_provinsi')->name('accounts.destroy');
        Route::post('accounts/{user}/resend', [PenanamanAccountController::class, 'resend'])->middleware('role:admin|admin_provinsi')->name('accounts.resend');
        Route::delete('{penanaman}', [PenanamanController::class, 'destroy'])->whereNumber('penanaman')->middleware('permission:penanaman.delete')->name('destroy');
        Route::post('{penanaman}/submit', [PenanamanController::class, 'submit'])->whereNumber('penanaman')->middleware('permission:penanaman.submit')->name('submit');
        Route::post('{penanaman}/return', [PenanamanController::class, 'returnToDraft'])->whereNumber('penanaman')->middleware('permission:penanaman.return')->name('return');
    });

    Route::resource('penanaman', PenanamanController::class)->only(['create', 'store'])->middleware('permission:penanaman.create');
    Route::resource('penanaman', PenanamanController::class)->only(['index', 'show'])->where(['penanaman' => '[0-9]+'])->middleware('permission:penanaman.view');
    Route::resource('penanaman', PenanamanController::class)->only(['edit', 'update'])->where(['penanaman' => '[0-9]+'])->middleware('permission:penanaman.edit');
});
