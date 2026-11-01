<?php

namespace Modules\Pemberdayaan\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Kelompok Usaha Perhutanan Sosial. Data kelembagaan, bukan laporan berkala.
 */
class KupsResource extends IntegrationResource
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
            'nama_kups' => $this->nama_kups,
            'kelas' => $this->category,
            'komoditas' => $this->commodity,
            ...$this->timestamps(),
        ];
    }
}
