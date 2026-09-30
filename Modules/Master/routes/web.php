<?php

use Illuminate\Support\Facades\Route;
use Modules\Master\App\Http\Controllers\BangunanKtaController;
use Modules\Master\App\Http\Controllers\BukanKayuController;
use Modules\Master\App\Http\Controllers\CommodityController;
use Modules\Master\App\Http\Controllers\DistrictController;
use Modules\Master\App\Http\Controllers\JenisProduksiController;
use Modules\Master\App\Http\Controllers\KayuController;
use Modules\Master\App\Http\Controllers\PengelolaPsController;
use Modules\Master\App\Http\Controllers\PengelolaWisataController;
use Modules\Master\App\Http\Controllers\ProvinceController;
use Modules\Master\App\Http\Controllers\RegencyController;
use Modules\Master\App\Http\Controllers\SkemaPerhutananSosialController;
use Modules\Master\App\Http\Controllers\SumberDanaController;
use Modules\Master\App\Http\Controllers\VillageController;
Route::middleware('auth')->group(function () {
    // Laravel generates some established parameters such as {bangunan_ktum}
    // and {pengelola_wisatum}; keep the resource options unchanged.
    $masterResources = [
        ['provinces',               ProvinceController::class,               null],
        ['regencies',               RegencyController::class,                null],
        ['districts',               DistrictController::class,               null],
        ['villages',                VillageController::class,                null],
        ['bangunan-kta',            BangunanKtaController::class,            null],
        ['sumber-dana',             SumberDanaController::class,             null],
        ['commodities',             CommodityController::class,              null],
        ['bukan-kayu',              BukanKayuController::class,              null],
        ['kayu',                    KayuController::class,                   null],
        ['jenis-produksi',          JenisProduksiController::class,          null],
        ['pengelola-wisata',        PengelolaWisataController::class,        null],
        ['pengelola-ps',            PengelolaPsController::class,            null],
        ['skema-perhutanan-sosial', SkemaPerhutananSosialController::class,  null],
    ];

    foreach ($masterResources as [$uri, $ctrl, $params]) {
        $options = $params ? ['parameters' => $params] : [];
        Route::resource($uri, $ctrl, $options)->only(['create', 'store'])->middleware("permission:{$uri}.create");
        Route::resource($uri, $ctrl, $options)->only(['index'])->middleware("permission:{$uri}.view");
        Route::resource($uri, $ctrl, $options)->only(['edit', 'update'])->middleware("permission:{$uri}.edit");
        Route::resource($uri, $ctrl, $options)->only(['destroy'])->middleware("permission:{$uri}.delete");
    }
});
