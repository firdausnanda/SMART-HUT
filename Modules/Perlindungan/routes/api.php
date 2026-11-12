<?php

use Illuminate\Support\Facades\Route;
use Modules\Perlindungan\App\Http\Controllers\Api\V1\PerlindunganDataController;

/*
|--------------------------------------------------------------------------
| API Integrasi Data v1 - Modul Perlindungan
|--------------------------------------------------------------------------
|
| Dimuat oleh RouteServiceProvider modul dengan prefix /api/v1, nama
| "api.v1." dan middleware group "api" + "integrasi".
| Dokumentasi: app/Support/Integrasi/ApiDocumentation.php (/docs/api).
|
*/

Route::prefix('perlindungan')
    ->name('perlindungan.')
    ->middleware('ability:perlindungan.read')
    ->controller(PerlindunganDataController::class)
    ->group(function () {
        foreach (['kebakaran-hutan', 'pengunjung-wisata'] as $dataset) {
            Route::get($dataset, 'index')->defaults('dataset', $dataset)->name("$dataset.index");
            Route::get("$dataset/deleted", 'deleted')->defaults('dataset', $dataset)->name("$dataset.deleted");
        }
    });
