<?php

namespace Modules\BinaUsaha\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Target dan realisasi PNBP sektor kehutanan (rupiah) per kabupaten, pengelola, dan bulan.
 */
class RealisasiPnbpResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province'),
                $this->whenLoadedModel('regency'),
            ),
            'jenis_hasil_hutan' => $this->types_of_forest_products,
            'pengelola' => $this->ref($this->whenLoadedModel('pengelola_wisata')),
            'target_pnbp_rp' => $this->parseInteger($this->pnbp_target),
            'realisasi_pnbp_rp' => $this->parseInteger($this->pnbp_realization),
            ...$this->timestamps(),
        ];
    }
}
