<?php

namespace Modules\Rhl\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationDataController;
use App\Models\PenghijauanLingkungan;
use App\Models\ReboisasiPS;
use App\Models\RehabLahan;
use App\Models\RehabManggrove;
use App\Models\RhlTeknis;
use Modules\Rhl\App\Http\Resources\Api\V1\ReboisasiPsResource;
use Modules\Rhl\App\Http\Resources\Api\V1\RhlLuasResource;
use Modules\Rhl\App\Http\Resources\Api\V1\RhlTeknisResource;

/**
 * Endpoint data RHL untuk klien integrasi (ability rhl.read).
 */
class RhlDataController extends IntegrationDataController
{
    private const WILAYAH = ['cdk:id,nama', 'province_rel:id,name', 'regency_rel:id,name', 'district_rel:id,name', 'village_rel:id,name'];

    protected function datasets(): array
    {
        return [
            'rehab-lahan' => [
                'model' => RehabLahan::class,
                'with' => self::WILAYAH,
                'resource' => RhlLuasResource::class,
            ],
            'penghijauan-lingkungan' => [
                'model' => PenghijauanLingkungan::class,
                'with' => self::WILAYAH,
                'resource' => RhlLuasResource::class,
            ],
            'rehab-mangrove' => [
                'model' => RehabManggrove::class,
                'with' => self::WILAYAH,
                'resource' => RhlLuasResource::class,
            ],
            'reboisasi-ps' => [
                'model' => ReboisasiPS::class,
                'with' => [...self::WILAYAH, 'pengelola:id,name'],
                'resource' => ReboisasiPsResource::class,
            ],
            'rhl-teknis' => [
                'model' => RhlTeknis::class,
                'with' => ['cdk:id,nama', 'province_rel:id,name', 'regency:id,name', 'district:id,name', 'village:id,name', 'details.bangunan_kta:id,name'],
                'resource' => RhlTeknisResource::class,
            ],
        ];
    }
}
