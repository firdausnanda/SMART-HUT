<?php

use App\Http\Controllers\BezettingJabatanController;
use App\Http\Controllers\DemografiPegawaiController;
use App\Http\Controllers\ProyeksiGajiController;
use App\Http\Controllers\RekapBulananController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// KEPEGAWAIAN
// =========================================================================

// --- Demografi Pegawai ---
Route::get('demografi-pegawai/export', [DemografiPegawaiController::class, 'export'])->middleware('permission:demografi-pegawai.export')->name('demografi-pegawai.export');
Route::get('demografi-pegawai/template', [DemografiPegawaiController::class, 'template'])->middleware('permission:demografi-pegawai.create')->name('demografi-pegawai.template');
Route::post('demografi-pegawai/import', [DemografiPegawaiController::class, 'import'])->middleware('permission:demografi-pegawai.create')->name('demografi-pegawai.import');
Route::post('demografi-pegawai/bulk-delete', [DemografiPegawaiController::class, 'bulkDelete'])->middleware('permission:demografi-pegawai.delete')->name('demografi-pegawai.bulk-delete');
Route::post('demografi-pegawai/bulk-restore', [DemografiPegawaiController::class, 'bulkRestore'])->middleware('permission:demografi-pegawai.edit')->name('demografi-pegawai.bulk-restore');
Route::post('demografi-pegawai/{id}/restore', [DemografiPegawaiController::class, 'restore'])->middleware('permission:demografi-pegawai.edit')->name('demografi-pegawai.restore');

Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['create', 'store'])->middleware('permission:demografi-pegawai.create');
    Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['index'])->middleware('permission:demografi-pegawai.view');
Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['edit', 'update'])->middleware('permission:demografi-pegawai.edit');
Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['destroy'])->middleware('permission:demografi-pegawai.delete');

Route::post('demografi-pegawai/{pegawai}/riwayat-kgb', [DemografiPegawaiController::class, 'storeKgb'])->middleware('permission:demografi-pegawai.create')->name('demografi-pegawai.kgb.store');
Route::put('demografi-pegawai/riwayat-kgb/{riwayat_kgb}', [DemografiPegawaiController::class, 'updateKgb'])->middleware('permission:demografi-pegawai.edit')->name('demografi-pegawai.kgb.update');
Route::delete('demografi-pegawai/riwayat-kgb/{riwayat_kgb}', [DemografiPegawaiController::class, 'destroyKgb'])->middleware('permission:demografi-pegawai.delete')->name('demografi-pegawai.kgb.destroy');

// --- Bezetting Jabatan ---
Route::middleware('permission:bezetting-jabatan.edit')->group(function () {
    Route::post('bezetting-jabatan/{bezetting_jabatan}/single-workflow-action', [BezettingJabatanController::class, 'singleWorkflowAction'])->name('bezetting-jabatan.single-workflow-action');
    Route::post('bezetting-jabatan/bulk-workflow-action', [BezettingJabatanController::class, 'bulkWorkflowAction'])->name('bezetting-jabatan.bulk-workflow-action');
});

Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['create', 'store'])->middleware('permission:bezetting-jabatan.create');
    Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['index', 'show'])->middleware('permission:bezetting-jabatan.view');
Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['edit', 'update'])->middleware('permission:bezetting-jabatan.edit');
Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['destroy'])->middleware('permission:bezetting-jabatan.delete');

// --- Proyeksi Gaji ---
Route::get('proyeksi-gaji/export', [ProyeksiGajiController::class, 'export'])->middleware('permission:proyeksi-gaji.export')->name('proyeksi-gaji.export');
Route::get('proyeksi-gaji', [ProyeksiGajiController::class, 'index'])->middleware('permission:proyeksi-gaji.view')->name('proyeksi-gaji.index');

// --- Rekap Kepegawaian Bulanan ---
Route::prefix('rekap-bulanan')->name('rekap-bulanan.')->group(function () {
    Route::get('/', [RekapBulananController::class, 'index'])->middleware('permission:rekap-bulanan.view')->name('index');
    Route::get('/{year}/{month}', [RekapBulananController::class, 'show'])->middleware('permission:rekap-bulanan.view')->name('show');
    Route::get('/{year}/{month}/pegawai', [RekapBulananController::class, 'showPegawai'])->middleware('permission:rekap-bulanan.view')->name('show-pegawai');
    Route::post('/generate', [RekapBulananController::class, 'generate'])->middleware('permission:rekap-bulanan.create')->name('generate');
    Route::get('/export/{year}/{month}', [RekapBulananController::class, 'export'])->middleware('permission:rekap-bulanan.export')->name('export');
    Route::get('/export-bezetting/{year}/{month}', [RekapBulananController::class, 'exportBezetting'])->middleware('permission:rekap-bulanan.export')->name('export-bezetting');
    Route::middleware('permission:rekap-bulanan.edit')->group(function () {
        Route::post('/{id}/single-workflow-action', [RekapBulananController::class, 'singleWorkflowAction'])->name('single-workflow-action');
        Route::post('/bulk-workflow-action', [RekapBulananController::class, 'bulkWorkflowAction'])->name('bulk-workflow-action');
    });
    Route::delete('/{id}', [RekapBulananController::class, 'destroy'])->middleware('permission:rekap-bulanan.delete')->name('destroy');
});
