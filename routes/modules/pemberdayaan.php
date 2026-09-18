<?php

use App\Http\Controllers\KupsController;
use App\Http\Controllers\NilaiEkonomiController;
use App\Http\Controllers\NilaiTransaksiEkonomiController;
use App\Http\Controllers\PerkembanganKthController;
use App\Http\Controllers\SkpsController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// PEMBERDAYAAN MASYARAKAT
// =========================================================================

// === SKPS ===
Route::controller(SkpsController::class)->prefix('skps')->name('skps.')->group(function () {
    Route::middleware('permission:skps.edit')->group(function () {
        Route::post('{skp}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:skps.export')->name('export');
    Route::get('template', 'template')->middleware('permission:skps.create')->name('template');
    Route::middleware('permission:skps.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === KUPS ===
Route::controller(KupsController::class)->prefix('kups')->name('kups.')->group(function () {
    Route::middleware('permission:kups.edit')->group(function () {
        Route::post('{kup}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:kups.export')->name('export');
    Route::get('template', 'template')->middleware('permission:kups.create')->name('template');
    Route::middleware('permission:kups.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === NILAI EKONOMI ===
Route::controller(NilaiEkonomiController::class)->prefix('nilai-ekonomi')->name('nilai-ekonomi.')->group(function () {
    Route::middleware('permission:nilai-ekonomi.edit')->group(function () {
        Route::post('{nilai_ekonomi}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:nilai-ekonomi.export')->name('export');
    Route::get('template', 'template')->middleware('permission:nilai-ekonomi.create')->name('template');
    Route::middleware('permission:nilai-ekonomi.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === PERKEMBANGAN KTH ===
Route::controller(PerkembanganKthController::class)->prefix('perkembangan-kth')->name('perkembangan-kth.')->group(function () {
    Route::middleware('permission:perkembangan-kth.edit')->group(function () {
        Route::post('{perkembangan_kth}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:perkembangan-kth.export')->name('export');
    Route::get('template', 'template')->middleware('permission:perkembangan-kth.create')->name('template');
    Route::middleware('permission:perkembangan-kth.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === NILAI TRANSAKSI EKONOMI ===
Route::controller(NilaiTransaksiEkonomiController::class)->prefix('nilai-transaksi-ekonomi')->name('nilai-transaksi-ekonomi.')->group(function () {
    Route::middleware('permission:nilai-transaksi-ekonomi.edit')->group(function () {
        Route::post('{nilai_transaksi_ekonomi}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:nilai-transaksi-ekonomi.export')->name('export');
    Route::get('template', 'template')->middleware('permission:nilai-transaksi-ekonomi.create')->name('template');
    Route::middleware('permission:nilai-transaksi-ekonomi.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// =========================================================================
// RESOURCE CRUD PEMBERDAYAAN
// =========================================================================

$pemberdayaanResources = [
    ['skps',                    SkpsController::class,                  null],
    ['kups',                    KupsController::class,                  null],
    ['nilai-ekonomi',           NilaiEkonomiController::class,          null],
    ['perkembangan-kth',        PerkembanganKthController::class,       ['perkembangan-kth' => 'perkembangan_kth']],
    ['nilai-transaksi-ekonomi', NilaiTransaksiEkonomiController::class, ['nilai-transaksi-ekonomi' => 'nilai_transaksi_ekonomi']],
];

foreach ($pemberdayaanResources as [$uri, $ctrl, $params]) {
    $options = $params ? ['parameters' => $params] : [];
    Route::resource($uri, $ctrl, $options)->only(['create', 'store'])->middleware("permission:{$uri}.create");
    Route::resource($uri, $ctrl, $options)->only(['index', 'show'])->middleware("permission:{$uri}.view");
    Route::resource($uri, $ctrl, $options)->only(['edit', 'update'])->middleware("permission:{$uri}.edit");
    Route::resource($uri, $ctrl, $options)->only(['destroy'])->middleware("permission:{$uri}.delete");
}
