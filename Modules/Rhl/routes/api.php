<?php

use Illuminate\Support\Facades\Route;
use Modules\Rhl\App\Http\Controllers\Api\V1\RhlDataController;

/*
|--------------------------------------------------------------------------
| API Integrasi Data v1 - Modul RHL
|--------------------------------------------------------------------------
|
| Dimuat oleh RouteServiceProvider modul dengan prefix /api/v1, nama
| "api.v1." dan middleware group "api" + "integrasi".
| Dokumentasi: app/Support/Integrasi/ApiDocumentation.php (/docs/api).
|
*/

Route::prefix('rhl')
    ->name('rhl.')
    ->middleware('ability:rhl.read')
    ->controller(RhlDataController::class)
    ->group(function () {
        foreach (['rehab-lahan', 'penghijauan-lingkungan', 'rehab-mangrove', 'reboisasi-ps', 'rhl-teknis'] as $dataset) {
            Route::get($dataset, 'index')->defaults('dataset', $dataset)->name("$dataset.index");
            Route::get("$dataset/deleted", 'deleted')->defaults('dataset', $dataset)->name("$dataset.deleted");
        }
    });
