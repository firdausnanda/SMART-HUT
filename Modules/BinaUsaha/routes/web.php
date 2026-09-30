<?php

use Modules\BinaUsaha\App\Http\Controllers\HasilHutanBukanKayuController;
use Modules\BinaUsaha\App\Http\Controllers\HasilHutanKayuController;
use Modules\BinaUsaha\App\Http\Controllers\PbphhController;
use Modules\BinaUsaha\App\Http\Controllers\RealisasiPnbpController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

// =========================================================================
// BINA USAHA KEHUTANAN
// =========================================================================

$forestPermissionPrefixes = ['produksi-hutan-negara', 'produksi-perhutanan-sosial', 'produksi-hutan-rakyat'];
$forestPermissionMiddleware = static fn (string $action): string => 'permission:' . implode('|', array_map(
    static fn (string $prefix): string => "{$prefix}.{$action}",
    $forestPermissionPrefixes
));
$forestWorkflowPermissions = [];
foreach ($forestPermissionPrefixes as $prefix) {
    foreach (['edit', 'approve', 'delete'] as $action) {
        $forestWorkflowPermissions[] = "{$prefix}.{$action}";
    }
}
$forestWorkflowMiddleware = 'permission:' . implode('|', $forestWorkflowPermissions);

// === HASIL HUTAN KAYU ===
Route::controller(HasilHutanKayuController::class)->prefix('hasil-hutan-kayu')->name('hasil-hutan-kayu.')->group(function () use ($forestWorkflowMiddleware, $forestPermissionMiddleware) {
    Route::middleware($forestWorkflowMiddleware)->group(function () {
        Route::post('{hasil_hutan_kayu}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware($forestPermissionMiddleware('export'))->name('export');
    Route::get('template', 'template')->middleware($forestPermissionMiddleware('create'))->name('template');
    Route::middleware($forestPermissionMiddleware('import'))->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === HASIL HUTAN BUKAN KAYU ===
Route::controller(HasilHutanBukanKayuController::class)->prefix('hasil-hutan-bukan-kayu')->name('hasil-hutan-bukan-kayu.')->group(function () use ($forestWorkflowMiddleware, $forestPermissionMiddleware) {
    Route::middleware($forestWorkflowMiddleware)->group(function () {
        Route::post('{hasil_hutan_bukan_kayu}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
    });
    Route::get('export', 'export')->middleware($forestPermissionMiddleware('export'))->name('export');
    Route::get('template', 'template')->middleware($forestPermissionMiddleware('create'))->name('template');
    Route::middleware($forestPermissionMiddleware('import'))->group(function () {
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');
        Route::post('import', 'import')->name('import');
    });
});

// === PBPHH ===
Route::controller(PbphhController::class)->prefix('pbphh')->name('pbphh.')->group(function () {
    Route::middleware('permission:pbphh.edit|pbphh.approve|pbphh.delete')->group(function () {
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
    Route::middleware('permission:realisasi-pnbp.edit|realisasi-pnbp.approve|realisasi-pnbp.delete')->group(function () {
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
    $permissionMiddleware = in_array($uri, ['hasil-hutan-kayu', 'hasil-hutan-bukan-kayu'], true)
        ? $forestPermissionMiddleware
        : static fn (string $action): string => "permission:{$uri}.{$action}";
    Route::resource($uri, $ctrl, $options)->only(['create', 'store'])->middleware($permissionMiddleware('create'));
    Route::resource($uri, $ctrl, $options)->only(['index', 'show'])->middleware($permissionMiddleware('view'));
    Route::resource($uri, $ctrl, $options)->only(['edit', 'update'])->middleware($permissionMiddleware('edit'));
    Route::resource($uri, $ctrl, $options)->only(['destroy'])->middleware($permissionMiddleware('delete'));
}
});
