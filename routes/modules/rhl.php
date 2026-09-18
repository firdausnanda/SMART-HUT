<?php

use App\Http\Controllers\PenghijauanLingkunganController;
use App\Http\Controllers\RehabLahanController;
use App\Http\Controllers\RehabManggroveController;
use App\Http\Controllers\ReboisasiPsController;
use App\Http\Controllers\RhlTeknisController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// RHL REHABILITASI HUTAN DAN LAHAN
// =========================================================================

// === REHAB LAHAN ===
Route::controller(RehabLahanController::class)->prefix('rehab-lahan')->name('rehab-lahan.')->group(function () {
    Route::middleware('permission:rehab-lahan.edit')->group(function () {
        Route::post('{rehab_lahan}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:rehab-lahan.export')->name('export');
    Route::get('template', 'template')->middleware('permission:rehab-lahan.create')->name('template');
    Route::middleware('permission:rehab-lahan.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === PENGHIJAUAN LINGKUNGAN ===
Route::controller(PenghijauanLingkunganController::class)->prefix('penghijauan-lingkungan')->name('penghijauan-lingkungan.')->group(function () {
    Route::middleware('permission:penghijauan-lingkungan.edit')->group(function () {
        Route::post('{penghijauan_lingkungan}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:penghijauan-lingkungan.export')->name('export');
    Route::get('template', 'template')->middleware('permission:penghijauan-lingkungan.create')->name('template');
    Route::middleware('permission:penghijauan-lingkungan.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === REHAB MANGGROVE ===
Route::controller(RehabManggroveController::class)->prefix('rehab-manggrove')->name('rehab-manggrove.')->group(function () {
    Route::middleware('permission:rehab-manggrove.edit')->group(function () {
        Route::post('{rehab_manggrove}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:rehab-manggrove.export')->name('export');
    Route::get('template', 'template')->middleware('permission:rehab-manggrove.create')->name('template');
    Route::middleware('permission:rehab-manggrove.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === RHL TEKNIS ===
Route::controller(RhlTeknisController::class)->prefix('rhl-teknis')->name('rhl-teknis.')->group(function () {
    Route::middleware('permission:rhl-teknis.edit')->group(function () {
        Route::post('{rhl_teknis}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:rhl-teknis.export')->name('export');
    Route::get('template', 'template')->middleware('permission:rhl-teknis.create')->name('template');
    Route::middleware('permission:rhl-teknis.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === REBOISASI PS ===
Route::controller(ReboisasiPsController::class)->prefix('reboisasi-ps')->name('reboisasi-ps.')->group(function () {
    Route::middleware('permission:reboisasi-ps.edit')->group(function () {
        Route::post('{reboisasi_ps}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:reboisasi-ps.export')->name('export');
    Route::get('template', 'template')->middleware('permission:reboisasi-ps.create')->name('template');
    Route::middleware('permission:reboisasi-ps.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// =========================================================================
// RESOURCE CRUD â€” RHL
// =========================================================================

$rhlResources = [
    ['rehab-lahan',           RehabLahanController::class,           null],
    ['rehab-manggrove',        RehabManggroveController::class,        null],
    ['rhl-teknis',             RhlTeknisController::class,             ['rhl-teknis' => 'rhl_teknis']],
    ['reboisasi-ps',           ReboisasiPsController::class,           ['reboisasi-ps' => 'reboisasi_ps']],
    ['penghijauan-lingkungan', PenghijauanLingkunganController::class, null],
];

foreach ($rhlResources as [$uri, $ctrl, $params]) {
    $options = $params ? ['parameters' => $params] : [];
    Route::resource($uri, $ctrl, $options)->only(['create', 'store'])->middleware("permission:{$uri}.create");
    Route::resource($uri, $ctrl, $options)->only(['index', 'show'])->middleware("permission:{$uri}.view");
    Route::resource($uri, $ctrl, $options)->only(['edit', 'update'])->middleware("permission:{$uri}.edit");
    Route::resource($uri, $ctrl, $options)->only(['destroy'])->middleware("permission:{$uri}.delete");
}
