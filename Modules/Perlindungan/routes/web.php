<?php

use Illuminate\Support\Facades\Route;
use Modules\Perlindungan\App\Http\Controllers\KebakaranHutanController;
use Modules\Perlindungan\App\Http\Controllers\PengunjungWisataController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::controller(KebakaranHutanController::class)->prefix('kebakaran-hutan')->name('kebakaran-hutan.')->group(function () {
        Route::middleware('permission:kebakaran-hutan.edit|kebakaran-hutan.approve|kebakaran-hutan.delete')->group(function () {
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

    $options = ['parameters' => ['kebakaran-hutan' => 'kebakaran_hutan']];
    Route::resource('kebakaran-hutan', KebakaranHutanController::class, $options)
        ->only(['create', 'store'])->middleware('permission:kebakaran-hutan.create');
    Route::resource('kebakaran-hutan', KebakaranHutanController::class, $options)
        ->only(['index', 'show'])->middleware('permission:kebakaran-hutan.view');
    Route::resource('kebakaran-hutan', KebakaranHutanController::class, $options)
        ->only(['edit', 'update'])->middleware('permission:kebakaran-hutan.edit');
    Route::resource('kebakaran-hutan', KebakaranHutanController::class, $options)
        ->only(['destroy'])->middleware('permission:kebakaran-hutan.delete');

    Route::controller(PengunjungWisataController::class)->prefix('pengunjung-wisata')->name('pengunjung-wisata.')->group(function () {
        Route::middleware('permission:pengunjung-wisata.edit|pengunjung-wisata.approve|pengunjung-wisata.delete')->group(function () {
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

    $options = ['parameters' => ['pengunjung-wisata' => 'pengunjung_wisata']];
    Route::resource('pengunjung-wisata', PengunjungWisataController::class, $options)
        ->only(['create', 'store'])->middleware('permission:pengunjung-wisata.create');
    Route::resource('pengunjung-wisata', PengunjungWisataController::class, $options)
        ->only(['index', 'show'])->middleware('permission:pengunjung-wisata.view');
    Route::resource('pengunjung-wisata', PengunjungWisataController::class, $options)
        ->only(['edit', 'update'])->middleware('permission:pengunjung-wisata.edit');
    Route::resource('pengunjung-wisata', PengunjungWisataController::class, $options)
        ->only(['destroy'])->middleware('permission:pengunjung-wisata.delete');
});
