<?php

use Illuminate\Support\Facades\Route;
use Modules\BinaUsaha\App\Http\Controllers\Api\V1\BinaUsahaDataController;

/*
|--------------------------------------------------------------------------
| API Integrasi Data v1 - Modul Bina Usaha
|--------------------------------------------------------------------------
|
| Dimuat oleh RouteServiceProvider modul dengan prefix /api/v1, nama
| "api.v1." dan middleware group "api" + "integrasi".
| Dokumentasi: app/Support/Integrasi/ApiDocumentation.php (/docs/api).
|
*/

Route::prefix('bina-usaha')
    ->name('bina-usaha.')
    ->middleware('ability:bina-usaha.read')
    ->controller(BinaUsahaDataController::class)
    ->group(function () {
        foreach (['hasil-hutan-kayu', 'hasil-hutan-bukan-kayu', 'pbphh', 'realisasi-pnbp'] as $dataset) {
            Route::get($dataset, 'index')->defaults('dataset', $dataset)->name("$dataset.index");
            Route::get("$dataset/deleted", 'deleted')->defaults('dataset', $dataset)->name("$dataset.deleted");
        }
    });
