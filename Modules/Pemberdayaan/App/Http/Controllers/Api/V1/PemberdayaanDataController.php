<?php

namespace Modules\Pemberdayaan\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationDataController;
use App\Models\Kups;
use App\Models\NilaiEkonomi;
use App\Models\NilaiTransaksiEkonomi;
use App\Models\PerkembanganKth;
use App\Models\Skps;
use Modules\Pemberdayaan\App\Http\Resources\Api\V1\KupsResource;
use Modules\Pemberdayaan\App\Http\Resources\Api\V1\NilaiEkonomiResource;
use Modules\Pemberdayaan\App\Http\Resources\Api\V1\NilaiTransaksiEkonomiResource;
use Modules\Pemberdayaan\App\Http\Resources\Api\V1\PerkembanganKthResource;
use Modules\Pemberdayaan\App\Http\Resources\Api\V1\SkpsResource;

/**
 * Endpoint data Pemberdayaan untuk klien integrasi (ability pemberdayaan.read).
 */
class PemberdayaanDataController extends IntegrationDataController
{
    private const WILAYAH = ['cdk:id,nama', 'province:id,name', 'regency:id,name', 'district:id,name'];
    private const WILAYAH_REL = ['cdk:id,nama', 'province_rel:id,name', 'regency_rel:id,name', 'district_rel:id,name', 'village_rel:id,name'];

    protected function datasets(): array
    {
        // Rincian harus memuat semua komoditas: global scope Commodity menyembunyikan
        // komoditas khusus nilai transaksi ekonomi, dan komoditas yang sudah dihapus
        // tetap perlu namanya untuk data historis.
        $komoditas = fn ($query) => $query
            ->withoutGlobalScope('not_nilai_transaksi_ekonomi')
            ->withTrashed()
            ->select('id', 'name');

        return [
            'skps' => [
                'model' => Skps::class,
                'with' => [...self::WILAYAH, 'skema:id,name'],
                'resource' => SkpsResource::class,
                'period' => false,
            ],
            'kups' => [
                'model' => Kups::class,
                'with' => self::WILAYAH,
                'resource' => KupsResource::class,
                'period' => false,
            ],
            'perkembangan-kth' => [
                'model' => PerkembanganKth::class,
                'with' => self::WILAYAH_REL,
                'resource' => PerkembanganKthResource::class,
            ],
            'nilai-ekonomi' => [
                'model' => NilaiEkonomi::class,
                'with' => [...self::WILAYAH, 'details', 'details.commodity' => $komoditas],
                'resource' => NilaiEkonomiResource::class,
            ],
            'nilai-transaksi-ekonomi' => [
                'model' => NilaiTransaksiEkonomi::class,
                'with' => [...self::WILAYAH_REL, 'details', 'details.commodity' => $komoditas],
                'resource' => NilaiTransaksiEkonomiResource::class,
            ],
        ];
    }
}
