<?php

namespace Modules\BinaUsaha\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Produksi hasil hutan kayu (m³) per lokasi dan bulan, dengan rincian per jenis kayu.
 */
class HasilHutanKayuResource extends IntegrationResource
{
    use FormatsPengelolaHasilHutan;

    public function toArray(Request $request): array
    {
        $rincian = $this->details->map(fn ($detail) => [
            'kayu' => $this->ref($detail->kayu),
            'realisasi_volume_m3' => $this->decimal($detail->volume_realization),
        ])->values();

        return [
            ...$this->common(
                $this->whenLoadedModel('province'),
                $this->whenLoadedModel('regency'),
                $this->whenLoadedModel('district'),
            ),
            'jenis_hutan' => $this->forest_type,
            'pengelola' => $this->pengelola(),
            'target_volume_m3' => $this->decimal($this->volume_target),
            'realisasi_volume_m3' => round($rincian->sum('realisasi_volume_m3'), 2),
            'rincian' => $rincian,
            ...$this->timestamps(),
        ];
    }
}
