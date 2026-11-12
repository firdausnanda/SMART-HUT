<?php

namespace Modules\BinaUsaha\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationDataController;
use App\Models\HasilHutanBukanKayu;
use App\Models\HasilHutanKayu;
use App\Models\Pbphh;
use App\Models\RealisasiPnbp;
use Modules\BinaUsaha\App\Http\Resources\Api\V1\HasilHutanBukanKayuResource;
use Modules\BinaUsaha\App\Http\Resources\Api\V1\HasilHutanKayuResource;
use Modules\BinaUsaha\App\Http\Resources\Api\V1\PbphhResource;
use Modules\BinaUsaha\App\Http\Resources\Api\V1\RealisasiPnbpResource;

/**
 * Endpoint data Bina Usaha untuk klien integrasi (ability bina-usaha.read).
 */
class BinaUsahaDataController extends IntegrationDataController
{
    private const WILAYAH = ['cdk:id,nama', 'province:id,name', 'regency:id,name', 'district:id,name'];

    protected function datasets(): array
    {
        return [
            'hasil-hutan-kayu' => [
                'model' => HasilHutanKayu::class,
                'with' => [...self::WILAYAH, 'pengelolaHutan:id,name', 'pengelolaWisata:id,name', 'details.kayu:id,name'],
                'resource' => HasilHutanKayuResource::class,
            ],
            'hasil-hutan-bukan-kayu' => [
                'model' => HasilHutanBukanKayu::class,
                'with' => [...self::WILAYAH, 'pengelolaHutan:id,name', 'pengelolaWisata:id,name', 'details.bukanKayu:id,name'],
                'resource' => HasilHutanBukanKayuResource::class,
            ],
            'pbphh' => [
                'model' => Pbphh::class,
                'with' => [...self::WILAYAH, 'jenisProduksi:id,name'],
                'resource' => PbphhResource::class,
                'period' => false,
            ],
            'realisasi-pnbp' => [
                'model' => RealisasiPnbp::class,
                'with' => ['cdk:id,nama', 'province:id,name', 'regency:id,name', 'pengelola_wisata:id,name'],
                'resource' => RealisasiPnbpResource::class,
            ],
        ];
    }
}
