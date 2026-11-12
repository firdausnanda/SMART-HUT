<?php

namespace Modules\Rhl\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * RHL teknis: target dalam unit bangunan konservasi tanah dan air (KTA),
 * dengan rincian jumlah per jenis bangunan.
 */
class RhlTeknisResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province_rel'),
                $this->whenLoadedModel('regency'),
                $this->whenLoadedModel('district'),
                $this->whenLoadedModel('village'),
            ),
            'target_tahunan_unit' => $this->decimal($this->target_annual),
            'sumber_dana' => $this->fund_source,
            'rincian' => $this->details->map(fn ($detail) => [
                'bangunan_kta' => $this->ref($detail->bangunan_kta),
                'jumlah_unit' => (int) $detail->unit_amount,
            ])->values(),
            ...$this->timestamps(),
        ];
    }
}
