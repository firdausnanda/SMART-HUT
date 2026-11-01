<?php

namespace Modules\Perlindungan\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationDataController;
use App\Models\KebakaranHutan;
use App\Models\PengunjungWisata;
use Modules\Perlindungan\App\Http\Resources\Api\V1\KebakaranHutanResource;
use Modules\Perlindungan\App\Http\Resources\Api\V1\PengunjungWisataResource;

/**
 * Endpoint data Perlindungan untuk klien integrasi (ability perlindungan.read).
 */
class PerlindunganDataController extends IntegrationDataController
{
    protected function datasets(): array
    {
        return [
            'kebakaran-hutan' => [
                'model' => KebakaranHutan::class,
                'with' => ['cdk:id,nama', 'province:id,name', 'regency:id,name', 'district:id,name', 'village:id,name', 'pengelolaWisata:id,name'],
                'resource' => KebakaranHutanResource::class,
            ],
            'pengunjung-wisata' => [
                'model' => PengunjungWisata::class,
                'with' => ['cdk:id,nama', 'pengelolaWisata:id,name'],
                'resource' => PengunjungWisataResource::class,
                'regency_column' => null,
            ],
        ];
    }
}
