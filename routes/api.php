<?php

use App\Http\Controllers\Api\V1\IntegrationClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API Integrasi Data v1
|--------------------------------------------------------------------------
|
| Endpoint baca-saja untuk klien integrasi (SIPETAK, CDK Trenggalek, dll.).
| Dokumentasi: docs/integrasi-data/README.md
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('integrasi')
    ->group(function () {
        Route::get('klien', [IntegrationClientController::class, 'show'])->name('klien');
    });
