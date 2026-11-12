<?php

use Illuminate\Support\Facades\Route;
use Modules\Pemberdayaan\App\Http\Controllers\Api\V1\PemberdayaanDataController;
use Modules\Pemberdayaan\App\Http\Controllers\Api\V1\PemberdayaanRekapController;

/*
|--------------------------------------------------------------------------
| API Integrasi Data v1 - Modul Pemberdayaan
|--------------------------------------------------------------------------
|
| Dimuat oleh RouteServiceProvider modul dengan prefix /api/v1, nama
| "api.v1." dan middleware group "api" + "integrasi".
| Dokumentasi: app/Support/Integrasi/ApiDocumentation.php (/docs/api).
|
*/

Route::prefix('pemberdayaan')
    ->name('pemberdayaan.')
    ->middleware('ability:pemberdayaan.read')
    ->controller(PemberdayaanDataController::class)
    ->group(function () {
        Route::get('rekap', [PemberdayaanRekapController::class, 'show'])->name('rekap');

        foreach (['skps', 'kups', 'perkembangan-kth', 'nilai-ekonomi', 'nilai-transaksi-ekonomi'] as $dataset) {
            Route::get($dataset, 'index')->defaults('dataset', $dataset)->name("$dataset.index");
            Route::get("$dataset/deleted", 'deleted')->defaults('dataset', $dataset)->name("$dataset.deleted");
        }
    });
