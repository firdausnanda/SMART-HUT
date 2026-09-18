<?php

use App\Http\Controllers\HasilHutanBukanKayuController;
use App\Http\Controllers\HasilHutanKayuController;
use App\Http\Controllers\PbphhController;
use App\Http\Controllers\RealisasiPnbpController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// BINA USAHA KEHUTANAN
// =========================================================================

// === HASIL HUTAN KAYU ===
Route::controller(HasilHutanKayuController::class)->prefix('hasil-hutan-kayu')->name('hasil-hutan-kayu.')->group(function () {
    Route::middleware('permission:hasil-hutan-kayu.edit')->group(function () {
        Route::post('{hasil_hutan_kayu}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:hasil-hutan-kayu.export')->name('export');
    Route::get('template', 'template')->middleware('permission:hasil-hutan-kayu.create')->name('template');
    Route::middleware('permission:hasil-hutan-kayu.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === HASIL HUTAN BUKAN KAYU ===
Route::controller(HasilHutanBukanKayuController::class)->prefix('hasil-hutan-bukan-kayu')->name('hasil-hutan-bukan-kayu.')->group(function () {
    Route::middleware('permission:hasil-hutan-bukan-kayu.edit')->group(function () {
        Route::post('{hasil_hutan_bukan_kayu}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:hasil-hutan-bukan-kayu.export')->name('export');
    Route::get('template', 'template')->middleware('permission:hasil-hutan-bukan-kayu.create')->name('template');
    Route::middleware('permission:hasil-hutan-bukan-kayu.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === PBPHH ===
Route::controller(PbphhController::class)->prefix('pbphh')->name('pbphh.')->group(function () {
    Route::middleware('permission:pbphh.edit')->group(function () {
        Route::post('{pbphh}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:pbphh.export')->name('export');
    Route::get('template', 'template')->middleware('permission:pbphh.create')->name('template');
    Route::middleware('permission:pbphh.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === REALISASI PNBP ===
Route::controller(RealisasiPnbpController::class)->prefix('realisasi-pnbp')->name('realisasi-pnbp.')->group(function () {
    Route::middleware('permission:realisasi-pnbp.edit')->group(function () {
        Route::post('{realisasi_pnbp}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:realisasi-pnbp.export')->name('export');
    Route::get('template', 'template')->middleware('permission:realisasi-pnbp.create')->name('template');
    Route::middleware('permission:realisasi-pnbp.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// =========================================================================
// RESOURCE CRUD BINA USAHA
// =========================================================================

$binaUsahaResources = [
    ['hasil-hutan-kayu',       HasilHutanKayuController::class,      ['hasil-hutan-kayu' => 'hasil_hutan_kayu']],
    ['hasil-hutan-bukan-kayu', HasilHutanBukanKayuController::class, ['hasil-hutan-bukan-kayu' => 'hasil_hutan_bukan_kayu']],
    ['pbphh',                  PbphhController::class,               null],
    ['realisasi-pnbp',         RealisasiPnbpController::class,       ['realisasi-pnbp' => 'realisasi_pnbp']],
];

foreach ($binaUsahaResources as [$uri, $ctrl, $params]) {
    $options = $params ? ['parameters' => $params] : [];
    Route::resource($uri, $ctrl, $options)->only(['create', 'store'])->middleware("permission:{$uri}.create");
    Route::resource($uri, $ctrl, $options)->only(['index', 'show'])->middleware("permission:{$uri}.view");
    Route::resource($uri, $ctrl, $options)->only(['edit', 'update'])->middleware("permission:{$uri}.edit");
    Route::resource($uri, $ctrl, $options)->only(['destroy'])->middleware("permission:{$uri}.delete");
}
