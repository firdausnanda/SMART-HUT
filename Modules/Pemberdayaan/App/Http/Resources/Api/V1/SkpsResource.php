<?php

namespace Modules\Pemberdayaan\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * SK Perhutanan Sosial per kelompok. Data izin, bukan laporan berkala.
 */
class SkpsResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province'),
                $this->whenLoadedModel('regency'),
                $this->whenLoadedModel('district'),
                withPeriod: false,
            ),
            'nama_kelompok' => $this->nama_kelompok,
            'skema' => $this->ref($this->whenLoadedModel('skema')),
            'luas_ps_ha' => $this->decimal($this->ps_area),
            'jumlah_kk' => (int) $this->number_of_kk,
            'potensi' => $this->potential,
            ...$this->timestamps(),
        ];
    }
}
