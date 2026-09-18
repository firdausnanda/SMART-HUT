<?php

use App\Http\Controllers\BangunanKtaController;
use App\Http\Controllers\BukanKayuController;
use App\Http\Controllers\CommodityController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\JenisProduksiController;
use App\Http\Controllers\KayuController;
use App\Http\Controllers\PengelolaPsController;
use App\Http\Controllers\PengelolaWisataController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\RegencyController;
use App\Http\Controllers\SkemaPerhutananSosialController;
use App\Http\Controllers\SumberDanaController;
use App\Http\Controllers\VillageController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// MASTER DATA
// =========================================================================

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
