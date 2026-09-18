<?php

use App\Http\Controllers\KebakaranHutanController;
use App\Http\Controllers\PengunjungWisataController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// PERLINDUNGAN HUTAN
// =========================================================================

// === KEBAKARAN HUTAN ===
Route::controller(KebakaranHutanController::class)->prefix('kebakaran-hutan')->name('kebakaran-hutan.')->group(function () {
    Route::middleware('permission:kebakaran-hutan.edit')->group(function () {
        Route::post('{kebakaran_hutan}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:kebakaran-hutan.export')->name('export');
    Route::get('template', 'template')->middleware('permission:kebakaran-hutan.create')->name('template');
    Route::middleware('permission:kebakaran-hutan.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === PENGUNJUNG WISATA ===
Route::controller(PengunjungWisataController::class)->prefix('pengunjung-wisata')->name('pengunjung-wisata.')->group(function () {
    Route::middleware('permission:pengunjung-wisata.edit')->group(function () {
        Route::post('{pengunjung_wisata}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware('permission:pengunjung-wisata.export')->name('export');
    Route::get('template', 'template')->middleware('permission:pengunjung-wisata.create')->name('template');
    Route::middleware('permission:pengunjung-wisata.import')->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// =========================================================================
// RESOURCE CRUD PERLINDUNGAN
// =========================================================================

$perlindunganResources = [
    ['kebakaran-hutan',  KebakaranHutanController::class,  ['kebakaran-hutan' => 'kebakaran_hutan']],
    ['pengunjung-wisata', PengunjungWisataController::class, ['pengunjung-wisata' => 'pengunjung_wisata']],
];

foreach ($perlindunganResources as [$uri, $ctrl, $params]) {
    $options = $params ? ['parameters' => $params] : [];
    Route::resource($uri, $ctrl, $options)->only(['create', 'store'])->middleware("permission:{$uri}.create");
    Route::resource($uri, $ctrl, $options)->only(['index', 'show'])->middleware("permission:{$uri}.view");
    Route::resource($uri, $ctrl, $options)->only(['edit', 'update'])->middleware("permission:{$uri}.edit");
    Route::resource($uri, $ctrl, $options)->only(['destroy'])->middleware("permission:{$uri}.delete");
}
